<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Parsian;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Contracts\SupportsSettlement;
use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Event\CallbackReceived;
use Eram\Pardakht\Event\PaymentFailed;
use Eram\Pardakht\Event\PaymentSettled;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PurchaseInitiated;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\AbstractSoapGateway;
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Http\Logger;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\RedirectResponse;
use Eram\Pardakht\Http\SoapClientFactory;
use Eram\Pardakht\Transaction\Transaction;
use Eram\Pardakht\Transaction\TransactionId;
use Eram\Pardakht\Transaction\TransactionStatus;

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
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
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
            cardNumber: $this->nullIfEmpty($cardNumber),
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
