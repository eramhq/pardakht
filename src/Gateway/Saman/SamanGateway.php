<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Saman;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Event\CallbackReceived;
use Eram\Pardakht\Event\PaymentFailed;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PurchaseInitiated;
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
 * Saman Bank (Sep) payment gateway.
 *
 * Flow: POST form redirect → callback → SOAP verifyTransaction
 */
final class SamanGateway extends AbstractSoapGateway
{
    private const WSDL_URL = 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction?wsdl';
    private const GATEWAY_URL = 'https://sep.shaparak.ir/OnlinePG/OnlinePG';

    public function __construct(
        private readonly SamanConfig $config,
        ?SoapClientFactory $soapFactory = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($soapFactory, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'saman';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        return RedirectResponse::post(
            self::GATEWAY_URL,
            $request->getOrderId(),
            [
                'MID' => $this->config->merchantId,
                'Amount' => (string) $request->getAmount()->inRials(),
                'ResNum' => $request->getOrderId(),
                'RedirectURL' => $request->getCallbackUrl(),
                'CellNumber' => $request->getMobile() ?? '',
            ],
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $state = (string) ($callbackData['State'] ?? '');
        $refNum = (string) ($callbackData['RefNum'] ?? '');
        $resNum = (string) ($callbackData['ResNum'] ?? '');
        $traceNo = (string) ($callbackData['TraceNo'] ?? '');
        $securePan = (string) ($callbackData['SecurePan'] ?? '');

        if ($state !== 'OK') {
            $stateCode = (int) ($callbackData['StateCode'] ?? -1);
            $error = SamanErrorCode::tryFrom($stateCode);
            $message = $error?->message() ?? "Payment failed with state: {$state}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $stateCode));

            throw new VerificationException($message, $this->getName(), $stateCode);
        }

        $result = $this->callSoap('verifyTransaction', [
            $refNum,
            $this->config->merchantId,
        ]);

        $amount = (int) $result;

        if ($amount <= 0) {
            $error = SamanErrorCode::tryFrom($amount);
            $message = $error?->message() ?? "Verification failed with code: {$amount}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $amount));

            throw new VerificationException($message, $this->getName(), $amount);
        }

        $transaction = new Transaction(
            id: new TransactionId($resNum),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($amount),
            status: TransactionStatus::Verified,
            referenceId: $refNum,
            trackingCode: $traceNo,
            cardNumber: $this->nullIfEmpty($securePan),
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    protected function getWsdlUrl(): string
    {
        return self::WSDL_URL;
    }
}
