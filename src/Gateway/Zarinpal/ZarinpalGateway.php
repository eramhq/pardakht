<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Zarinpal;

use Eram\Abzar\Money\Amount;
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
use Eram\Pardakht\Transaction\Transaction;
use Eram\Pardakht\Transaction\TransactionId;
use Eram\Pardakht\Transaction\TransactionStatus;

/**
 * Zarinpal payment gateway (REST API v4).
 *
 * Flow: payment/request → redirect → callback → payment/verify
 */
final class ZarinpalGateway extends AbstractGateway
{
    private const API_URL = 'https://api.zarinpal.com/pg/v4/payment';
    private const SANDBOX_API_URL = 'https://sandbox.zarinpal.com/pg/v4/payment';
    private const GATEWAY_URL = 'https://www.zarinpal.com/pg/StartPay/';
    private const SANDBOX_GATEWAY_URL = 'https://sandbox.zarinpal.com/pg/StartPay/';

    public function __construct(
        private readonly ZarinpalConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'zarinpal';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $apiUrl = $this->getApiUrl();

        $data = $this->postJson("{$apiUrl}/request.json", [
            'merchant_id' => $this->config->merchantId,
            'amount' => $request->getAmount()->inRials(),
            'callback_url' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'metadata' => array_filter([
                'mobile' => $request->getMobile(),
                'email' => $request->getEmail(),
                'order_id' => $request->getOrderId(),
            ]),
        ]);

        $dataSection = $data['data'] ?? [];
        $code = (int) ($dataSection['code'] ?? $data['errors']['code'] ?? -1);

        if ($code !== 100) {
            $error = ZarinpalErrorCode::tryFrom($code);
            $message = $error?->message() ?? ($data['errors']['message'] ?? "Request failed with code: {$code}");

            $this->dispatch(new PaymentFailed($this->getName(), (string) $message, $code));

            throw new GatewayException((string) $message, $this->getName(), $code);
        }

        $authority = (string) ($dataSection['authority'] ?? '');
        $gatewayUrl = $this->getGatewayUrl();

        return RedirectResponse::redirect(
            $gatewayUrl . $authority,
            $authority,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $authority = (string) ($callbackData['Authority'] ?? '');
        $status = (string) ($callbackData['Status'] ?? '');

        if ($status !== 'OK') {
            $this->dispatch(new PaymentFailed($this->getName(), 'Payment cancelled by user', -51));

            throw new VerificationException(
                'Payment cancelled by user',
                $this->getName(),
                ZarinpalErrorCode::UserCancelled->value,
            );
        }

        $apiUrl = $this->getApiUrl();

        $data = $this->postJson("{$apiUrl}/verify.json", [
            'merchant_id' => $this->config->merchantId,
            'authority' => $authority,
            'amount' => (int) ($callbackData['amount'] ?? 0),
        ]);

        $dataSection = $data['data'] ?? [];
        $code = (int) ($dataSection['code'] ?? $data['errors']['code'] ?? -1);

        if ($code !== 100 && $code !== 101) {
            $error = ZarinpalErrorCode::tryFrom($code);
            $message = $error?->message() ?? "Verification failed with code: {$code}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $code));

            throw new VerificationException($message, $this->getName(), $code);
        }

        $refId = (string) ($dataSection['ref_id'] ?? '');
        $cardPan = (string) ($dataSection['card_pan'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($authority),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($dataSection['amount'] ?? $callbackData['amount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $authority,
            trackingCode: $refId,
            cardNumber: $this->nullIfEmpty($cardPan),
            extra: [
                'fee_type' => $dataSection['fee_type'] ?? '',
                'fee' => $dataSection['fee'] ?? 0,
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    private function getApiUrl(): string
    {
        return $this->config->sandbox ? self::SANDBOX_API_URL : self::API_URL;
    }

    private function getGatewayUrl(): string
    {
        return $this->config->sandbox ? self::SANDBOX_GATEWAY_URL : self::GATEWAY_URL;
    }
}
