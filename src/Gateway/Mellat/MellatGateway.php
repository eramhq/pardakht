<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Mellat;

use EramDev\Pardakht\Contracts\SupportsSettlement;
use EramDev\Pardakht\Contracts\TransactionInterface;
use EramDev\Pardakht\Event\CallbackReceived;
use EramDev\Pardakht\Event\PaymentFailed;
use EramDev\Pardakht\Event\PaymentSettled;
use EramDev\Pardakht\Event\PaymentVerified;
use EramDev\Pardakht\Event\PurchaseInitiated;
use EramDev\Pardakht\Exception\GatewayException;
use EramDev\Pardakht\Exception\VerificationException;
use EramDev\Pardakht\Gateway\AbstractSoapGateway;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Http\RedirectResponse;
use EramDev\Pardakht\Http\SoapClientFactory;
use EramDev\Pardakht\Money\Amount;
use EramDev\Pardakht\Transaction\Transaction;
use EramDev\Pardakht\Transaction\TransactionId;
use EramDev\Pardakht\Transaction\TransactionStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Mellat Bank (Behpardakht) payment gateway.
 *
 * Flow: bpPayRequest → redirect → callback → bpVerifyRequest → bpSettleRequest
 */
final class MellatGateway extends AbstractSoapGateway implements SupportsSettlement
{
    private const WSDL_URL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    private const GATEWAY_URL = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';

    public function __construct(
        private readonly MellatConfig $config,
        ?SoapClientFactory $soapFactory = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        parent::__construct($soapFactory, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'mellat';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $result = $this->callSoap('bpPayRequest', [
            'terminalId' => $this->config->terminalId,
            'userName' => $this->config->username,
            'userPassword' => $this->config->password,
            'orderId' => (int) $request->getOrderId(),
            'amount' => $request->getAmount()->inRials(),
            'localDate' => \date('Ymd'),
            'localTime' => \date('His'),
            'additionalData' => $request->getDescription(),
            'callBackUrl' => $request->getCallbackUrl(),
            'payerId' => 0,
        ]);

        $resultParts = \explode(',', (string) $result->return);
        $resCode = (int) $resultParts[0];

        if ($resCode !== 0) {
            $error = MellatErrorCode::tryFrom($resCode);
            $message = $error?->message() ?? "Unknown error code: {$resCode}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resCode));

            throw new GatewayException($message, $this->getName(), $resCode);
        }

        $refId = $resultParts[1];

        return RedirectResponse::post(
            self::GATEWAY_URL,
            $refId,
            ['RefId' => $refId],
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $resCode = (int) ($callbackData['ResCode'] ?? -1);

        if ($resCode !== 0) {
            $error = MellatErrorCode::tryFrom($resCode);
            $message = $error?->message() ?? "Payment failed with code: {$resCode}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resCode));

            throw new VerificationException($message, $this->getName(), $resCode);
        }

        $refId = (string) ($callbackData['RefId'] ?? '');
        $saleReferenceId = (string) ($callbackData['SaleReferenceId'] ?? '');
        $saleOrderId = (string) ($callbackData['SaleOrderId'] ?? '');
        $cardHolderPan = (string) ($callbackData['CardHolderPan'] ?? '');

        $result = $this->callSoap('bpVerifyRequest', [
            'terminalId' => $this->config->terminalId,
            'userName' => $this->config->username,
            'userPassword' => $this->config->password,
            'orderId' => (int) $saleOrderId,
            'saleOrderId' => (int) $saleOrderId,
            'saleReferenceId' => (int) $saleReferenceId,
        ]);

        $verifyResCode = (int) $result->return;

        if ($verifyResCode !== 0) {
            $error = MellatErrorCode::tryFrom($verifyResCode);
            $message = $error?->message() ?? "Verification failed with code: {$verifyResCode}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $verifyResCode));

            throw new VerificationException($message, $this->getName(), $verifyResCode);
        }

        $transaction = new Transaction(
            id: new TransactionId($saleOrderId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($callbackData['FinalAmount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $refId,
            trackingCode: $saleReferenceId,
            cardNumber: $cardHolderPan !== '' ? $cardHolderPan : null,
            extra: [
                'SaleOrderId' => $saleOrderId,
                'SaleReferenceId' => $saleReferenceId,
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    public function settle(TransactionInterface $transaction): TransactionInterface
    {
        $extra = $transaction->getExtra();

        $result = $this->callSoap('bpSettleRequest', [
            'terminalId' => $this->config->terminalId,
            'userName' => $this->config->username,
            'userPassword' => $this->config->password,
            'orderId' => (int) ($extra['SaleOrderId'] ?? $transaction->getId()->value()),
            'saleOrderId' => (int) ($extra['SaleOrderId'] ?? $transaction->getId()->value()),
            'saleReferenceId' => (int) ($extra['SaleReferenceId'] ?? $transaction->getTrackingCode()),
        ]);

        $settleResCode = (int) $result->return;

        if ($settleResCode !== 0) {
            $error = MellatErrorCode::tryFrom($settleResCode);
            $message = $error?->message() ?? "Settlement failed with code: {$settleResCode}";

            throw new GatewayException($message, $this->getName(), $settleResCode);
        }

        $settled = $transaction->withStatus(TransactionStatus::Settled);

        $this->dispatch(new PaymentSettled($this->getName(), $settled));

        return $settled;
    }

    protected function getWsdlUrl(): string
    {
        return self::WSDL_URL;
    }
}
