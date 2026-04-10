<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Parsian;

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
 * Parsian Bank (Pec) payment gateway.
 *
 * Flow: SalePaymentRequest → redirect → callback → ConfirmPayment
 */
final class ParsianGateway extends AbstractSoapGateway implements SupportsSettlement
{
    private const SALE_WSDL = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL';
    private const CONFIRM_WSDL = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL';
    private const GATEWAY_URL = 'https://pec.shaparak.ir/NewIPG/?Token=';

    private ?\SoapClient $confirmClient = null;

    public function __construct(
        private readonly ParsianConfig $config,
        ?SoapClientFactory $soapFactory = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        parent::__construct($soapFactory, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'parsian';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $result = $this->callSoap('SalePaymentRequest', [
            'requestData' => [
                'LoginAccount' => $this->config->pin,
                'Amount' => $request->getAmount()->inRials(),
                'OrderId' => (int) $request->getOrderId(),
                'CallBackUrl' => $request->getCallbackUrl(),
                'AdditionalData' => $request->getDescription(),
            ],
        ]);

        $status = (int) ($result->SalePaymentRequestResult->Status ?? -1);
        $token = (string) ($result->SalePaymentRequestResult->Token ?? '');

        if ($status !== 0 || $token === '' || $token === '0') {
            $error = ParsianErrorCode::tryFrom($status);
            $message = $error?->message() ?? "Sale request failed with code: {$status}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $status));

            throw new GatewayException($message, $this->getName(), $status);
        }

        return RedirectResponse::redirect(
            self::GATEWAY_URL . $token,
            $token,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $status = (int) ($callbackData['status'] ?? -1);
        $token = (string) ($callbackData['Token'] ?? '');
        $orderId = (string) ($callbackData['OrderId'] ?? '');
        $rrn = (string) ($callbackData['RRN'] ?? '');
        $cardNumber = (string) ($callbackData['HashCardNumber'] ?? '');
        $amount = (int) ($callbackData['Amount'] ?? 0);

        if ($status !== 0) {
            $error = ParsianErrorCode::tryFrom($status);
            $message = $error?->message() ?? "Payment failed with code: {$status}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $status));

            throw new VerificationException($message, $this->getName(), $status);
        }

        $transaction = new Transaction(
            id: new TransactionId($orderId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($amount),
            status: TransactionStatus::Verified,
            referenceId: $token,
            trackingCode: $rrn,
            cardNumber: $cardNumber !== '' ? $cardNumber : null,
            extra: ['Token' => $token, 'RRN' => $rrn],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    public function settle(TransactionInterface $transaction): TransactionInterface
    {
        $token = $transaction->getExtra()['Token'] ?? $transaction->getReferenceId();

        $this->confirmClient ??= $this->soapFactory->create(self::CONFIRM_WSDL);
        $confirmClient = $this->confirmClient;

        $result = $confirmClient->__soapCall('ConfirmPayment', [[
            'requestData' => [
                'LoginAccount' => $this->config->pin,
                'Token' => (int) $token,
            ],
        ]]);

        $status = (int) ($result->ConfirmPaymentResult->Status ?? -1);
        $rrn = (string) ($result->ConfirmPaymentResult->RRN ?? '');

        if ($status !== 0) {
            $error = ParsianErrorCode::tryFrom($status);
            $message = $error?->message() ?? "Confirm failed with code: {$status}";

            throw new GatewayException($message, $this->getName(), $status);
        }

        $settled = $transaction->withStatus(TransactionStatus::Settled);

        $this->dispatch(new PaymentSettled($this->getName(), $settled));

        return $settled;
    }

    protected function getWsdlUrl(): string
    {
        return self::SALE_WSDL;
    }
}
