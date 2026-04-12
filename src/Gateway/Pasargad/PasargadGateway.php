<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Pasargad;

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
 * Pasargad Bank payment gateway.
 *
 * Uses RSA signing. Flow: GetToken → redirect → callback → VerifyPayment
 */
final class PasargadGateway extends AbstractGateway
{
    private const TOKEN_URL = 'https://pep.shaparak.ir/Api/v1/Payment/GetToken';
    private const VERIFY_URL = 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment';
    private const GATEWAY_URL = 'https://pep.shaparak.ir/payment.aspx';
    private const ACTION_PURCHASE = 1003;

    private \OpenSSLAsymmetricKey|null $parsedKey = null;

    public function __construct(
        private readonly PasargadConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'pasargad';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $timestamp = \date('Y/m/d H:i:s');
        $invoiceNumber = $request->getOrderId();
        $amount = $request->getAmount()->inRials();

        $signData = \sprintf(
            '#%s#%s#%s#%s#%s#%s#%s#',
            $this->config->merchantCode,
            $this->config->terminalCode,
            $invoiceNumber,
            \date('Y/m/d'),
            $amount,
            $request->getCallbackUrl(),
            self::ACTION_PURCHASE,
        );

        $sign = $this->rsaSign($signData);

        $data = $this->postJson(self::TOKEN_URL, [
            'MerchantCode' => $this->config->merchantCode,
            'TerminalCode' => $this->config->terminalCode,
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceDate' => \date('Y/m/d'),
            'Amount' => $amount,
            'RedirectAddress' => $request->getCallbackUrl(),
            'Timestamp' => $timestamp,
            'Action' => self::ACTION_PURCHASE,
            'Mobile' => $request->getMobile() ?? '',
            'Email' => $request->getEmail() ?? '',
            'Sign' => $sign,
        ]);

        $isSuccess = (bool) ($data['IsSuccess'] ?? false);

        if (!$isSuccess) {
            $message = (string) ($data['Message'] ?? 'Token request failed');

            $this->dispatch(new PaymentFailed($this->getName(), $message));

            throw new GatewayException($message, $this->getName());
        }

        $token = (string) ($data['Token'] ?? '');

        return RedirectResponse::redirect(
            self::GATEWAY_URL . '?n=' . $token,
            $token,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $invoiceNumber = (string) ($callbackData['iN'] ?? '');
        $invoiceDate = (string) ($callbackData['iD'] ?? '');
        $transactionReferenceId = (string) ($callbackData['tref'] ?? '');

        if ($transactionReferenceId === '') {
            $this->dispatch(new PaymentFailed($this->getName(), 'Payment cancelled by user'));

            throw new VerificationException('Payment cancelled by user', $this->getName(), -1);
        }

        $signData = \sprintf('#%s#%s#', $invoiceNumber, $invoiceDate);
        $sign = $this->rsaSign($signData);

        $data = $this->postJson(self::VERIFY_URL, [
            'MerchantCode' => $this->config->merchantCode,
            'TerminalCode' => $this->config->terminalCode,
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceDate' => $invoiceDate,
            'Amount' => (int) ($callbackData['Amount'] ?? 0),
            'Timestamp' => \date('Y/m/d H:i:s'),
            'Sign' => $sign,
        ]);

        $isSuccess = (bool) ($data['IsSuccess'] ?? false);

        if (!$isSuccess) {
            $message = (string) ($data['Message'] ?? 'Verification failed');

            $this->dispatch(new PaymentFailed($this->getName(), $message));

            throw new VerificationException($message, $this->getName());
        }

        $maskedCardNumber = (string) ($data['MaskedCardNumber'] ?? '');
        $shaparakRefNumber = (string) ($data['ShaparakRefNumber'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($invoiceNumber),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($data['Amount'] ?? $callbackData['Amount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $transactionReferenceId,
            trackingCode: $shaparakRefNumber,
            cardNumber: $this->nullIfEmpty($maskedCardNumber),
            extra: [
                'InvoiceDate' => $invoiceDate,
                'ShaparakRefNumber' => $shaparakRefNumber,
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    private function rsaSign(string $data): string
    {
        if ($this->parsedKey === null) {
            $key = \openssl_pkey_get_private($this->config->privateKey);

            if ($key === false) {
                throw new \RuntimeException('Invalid Pasargad RSA private key.');
            }

            $this->parsedKey = $key;
        }

        $signature = '';

        if (!\openssl_sign($data, $signature, $this->parsedKey, OPENSSL_ALGO_SHA1)) {
            throw new \RuntimeException('Failed to create RSA signature for Pasargad.');
        }

        return \base64_encode($signature);
    }
}
