<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Sadad;

use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Event\CallbackReceived;
use Eram\Pardakht\Event\PaymentFailed;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PurchaseInitiated;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\AbstractGateway;
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\Logger;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\RedirectResponse;
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Transaction\Transaction;
use Eram\Pardakht\Transaction\TransactionId;
use Eram\Pardakht\Transaction\TransactionStatus;

/**
 * Sadad (Bank Melli) payment gateway.
 *
 * Uses DES/3DES signing for request authentication.
 * Flow: RequestToken → redirect → callback → Verify
 */
final class SadadGateway extends AbstractGateway
{
    private const REQUEST_URL = 'https://sadad.shaparak.ir/api/v0/Request/PaymentRequest';
    private const VERIFY_URL = 'https://sadad.shaparak.ir/api/v0/Advice/Verify';
    private const GATEWAY_URL = 'https://sadad.shaparak.ir/Purchase';

    public function __construct(
        private readonly SadadConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'sadad';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $orderId = $request->getOrderId();
        $amount = $request->getAmount()->inRials();
        $localDate = \date('m/d/Y g:i:s a');

        $signData = $this->sign("{$this->config->terminalId};{$orderId};{$amount}");

        $data = $this->postJson(self::REQUEST_URL, [
            'MerchantId' => $this->config->merchantId,
            'TerminalId' => $this->config->terminalId,
            'Amount' => $amount,
            'OrderId' => (int) $orderId,
            'LocalDateTime' => $localDate,
            'ReturnUrl' => $request->getCallbackUrl(),
            'SignData' => $signData,
            'AdditionalData' => $request->getDescription(),
        ]);

        $resCode = (int) ($data['ResCode'] ?? -1);

        if ($resCode !== 0) {
            $error = SadadErrorCode::tryFrom($resCode);
            $message = $error?->message() ?? "Request failed with code: {$resCode}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resCode));

            throw new GatewayException($message, $this->getName(), $resCode);
        }

        $token = (string) ($data['Token'] ?? '');

        return RedirectResponse::redirect(
            self::GATEWAY_URL . '?Token=' . $token,
            $token,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $resCode = (int) ($callbackData['ResCode'] ?? -1);

        if ($resCode !== 0) {
            $error = SadadErrorCode::tryFrom($resCode);
            $message = $error?->message() ?? "Payment failed with code: {$resCode}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resCode));

            throw new VerificationException($message, $this->getName(), $resCode);
        }

        $token = (string) ($callbackData['Token'] ?? '');
        $orderId = (string) ($callbackData['OrderId'] ?? '');

        $signData = $this->sign($token);

        $data = $this->postJson(self::VERIFY_URL, [
            'Token' => $token,
            'SignData' => $signData,
        ]);

        $resultCode = (int) ($data['ResCode'] ?? -1);

        if ($resultCode !== 0) {
            $error = SadadErrorCode::tryFrom($resultCode);
            $message = $error?->message() ?? "Verification failed with code: {$resultCode}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resultCode));

            throw new VerificationException($message, $this->getName(), $resultCode);
        }

        $transaction = new Transaction(
            id: new TransactionId($orderId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($data['Amount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $token,
            trackingCode: (string) ($data['SystemTraceNo'] ?? ''),
            cardNumber: isset($data['CustomerCardNumber']) ? (string) $data['CustomerCardNumber'] : null,
            extra: [
                'RetrivalRefNo' => $data['RetrivalRefNo'] ?? '',
                'SystemTraceNo' => $data['SystemTraceNo'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    /**
     * Create a DES/3DES signature for Sadad authentication.
     */
    private function sign(string $data): string
    {
        $key = \base64_decode($this->config->terminalKey);
        $iv = "\0\0\0\0\0\0\0\0";

        $encrypted = \openssl_encrypt(
            $data,
            'DES-EDE3-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Failed to create Sadad signature.');
        }

        return \base64_encode($encrypted);
    }
}
