<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Vandar;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Event\CallbackReceived;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PurchaseInitiated;
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
 * Vandar payment gateway (REST API).
 */
final class VandarGateway extends AbstractGateway
{
    private const API_URL = 'https://ipg.vandar.io/api/v3';
    private const GATEWAY_URL = 'https://ipg.vandar.io/v3/';

    public function __construct(
        private readonly VandarConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'vandar';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $data = $this->postJson(self::API_URL . '/send', [
            'api_key' => $this->config->apiKey,
            'amount' => $request->getAmount()->inRials(),
            'callback_url' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'mobile_number' => $request->getMobile(),
            'factorNumber' => $request->getOrderId(),
        ]);

        $status = (int) ($data['status'] ?? 0);

        if ($status !== 1) {
            $this->failPurchase($this->flattenErrors($data['errors'] ?? [], 'Request failed'));
        }

        $token = (string) ($data['token'] ?? '');

        return RedirectResponse::redirect(
            self::GATEWAY_URL . $token,
            $token,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $token = (string) ($callbackData['token'] ?? '');
        $paymentStatus = (string) ($callbackData['payment_status'] ?? '');

        if ($paymentStatus !== 'OK') {
            $this->failVerification('Payment cancelled', -1);
        }

        $data = $this->postJson(self::API_URL . '/verify', [
            'api_key' => $this->config->apiKey,
            'token' => $token,
        ]);

        $status = (int) ($data['status'] ?? 0);

        if ($status !== 1) {
            $this->failVerification($this->flattenErrors($data['errors'] ?? [], 'Verification failed'));
        }

        $transId = (string) ($data['transId'] ?? '');
        $cardNumber = (string) ($data['cardNumber'] ?? '');
        $realAmount = (int) ($data['realAmount'] ?? $data['amount'] ?? 0);
        $factorNumber = (string) ($data['factorNumber'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($transId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($realAmount),
            status: TransactionStatus::Verified,
            referenceId: $token,
            trackingCode: $transId,
            cardNumber: $this->nullIfEmpty($cardNumber),
            extra: [
                'factorNumber' => $factorNumber,
                'cid' => $data['cid'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    /**
     * @param mixed $errors
     */
    private function flattenErrors(mixed $errors, string $fallback): string
    {
        if (!\is_array($errors) || $errors === []) {
            return $fallback;
        }

        return implode(', ', array_map(
            fn($e) => \is_array($e) ? implode(', ', $e) : (string) $e,
            $errors,
        ));
    }
}
