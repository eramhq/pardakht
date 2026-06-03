<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Paystar;

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
 * Paystar payment gateway (core.paystar.ir REST API, v1).
 *
 * Auth: Bearer {gatewayId}. Each call is signed with HMAC-SHA512 over the
 * "#"-joined fields using the merchant's sign key:
 *   create: amount#order_id#callback
 *   verify: amount#ref_num#card_number
 *
 * Verify against the Paystar sandbox before production — amounts are in rials.
 */
final class PaystarGateway extends AbstractGateway
{
    private const API_URL = 'https://core.paystar.ir/api/pardakht';
    private const PAYMENT_URL = 'https://core.paystar.ir/api/pardakht/payment';

    public function __construct(
        private readonly PaystarConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'paystar';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $amount = $request->getAmount()->inRials();
        $orderId = $request->getOrderId();
        $callback = $request->getCallbackUrl();

        $data = $this->postJson(self::API_URL . '/create', [
            'amount' => $amount,
            'order_id' => $orderId,
            'callback' => $callback,
            'sign' => $this->sign("{$amount}#{$orderId}#{$callback}"),
            'mobile' => $request->getMobile(),
            'description' => $request->getDescription(),
        ], $this->authHeaders());

        $status = (int) ($data['status'] ?? -1);
        if ($status !== 1) {
            $message = (string) ($data['message'] ?? "Request failed with status: {$status}");
            $this->dispatch(new PaymentFailed($this->getName(), $message, $status));
            throw new GatewayException($message, $this->getName(), $status);
        }

        $token = (string) ($data['data']['token'] ?? '');
        $refNum = (string) ($data['data']['ref_num'] ?? '');

        return RedirectResponse::post(self::PAYMENT_URL, $refNum, ['token' => $token]);
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $status = (int) ($callbackData['status'] ?? -1);
        $refNum = (string) ($callbackData['ref_num'] ?? '');
        $orderId = (string) ($callbackData['order_id'] ?? '');
        $cardNumber = (string) ($callbackData['card_number'] ?? '');
        $trackingCode = (string) ($callbackData['tracking_code'] ?? '');
        $amount = (int) ($callbackData['amount'] ?? 0);

        if ($status !== 1) {
            $message = "Payment failed with status: {$status}";
            $this->dispatch(new PaymentFailed($this->getName(), $message, $status));
            throw new VerificationException($message, $this->getName(), $status);
        }

        $data = $this->postJson(self::API_URL . '/verify', [
            'amount' => $amount,
            'ref_num' => $refNum,
            'sign' => $this->sign("{$amount}#{$refNum}#{$cardNumber}"),
        ], $this->authHeaders());

        $verifyStatus = (int) ($data['status'] ?? -1);
        if ($verifyStatus !== 1) {
            $message = (string) ($data['message'] ?? "Verification failed with status: {$verifyStatus}");
            $this->dispatch(new PaymentFailed($this->getName(), $message, $verifyStatus));
            throw new VerificationException($message, $this->getName(), $verifyStatus);
        }

        $transaction = new Transaction(
            id: new TransactionId($refNum),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($amount),
            status: TransactionStatus::Verified,
            referenceId: $refNum,
            trackingCode: $trackingCode,
            cardNumber: $this->nullIfEmpty($cardNumber),
            extra: ['orderId' => $orderId],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha512', $payload, $this->config->signKey);
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->gatewayId,
            'Content-Type' => 'application/json',
        ];
    }
}
