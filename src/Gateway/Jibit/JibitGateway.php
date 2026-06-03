<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Jibit;

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
 * Jibit IPG payment gateway (napi.jibit.ir/ipg/v1, token-based).
 *
 * Auth is a two-key handshake: POST /tokens with {apiKey, secretKey} returns a
 * bearer accessToken used for /purchases (create) and /purchases/{id}/verify.
 * Amounts are in rials. Verify against the Jibit sandbox before production.
 */
final class JibitGateway extends AbstractGateway
{
    private const API_URL = 'https://napi.jibit.ir/ipg/v1';

    public function __construct(
        private readonly JibitConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'jibit';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $data = $this->postJson(self::API_URL . '/purchases', [
            'amount' => $request->getAmount()->inRials(),
            'currency' => 'RIALS',
            'callbackUrl' => $request->getCallbackUrl(),
            'clientReferenceNumber' => $request->getOrderId(),
            'description' => $request->getDescription(),
            'userIdentifier' => $request->getMobile(),
        ], $this->authHeaders());

        $pspSwitchingUrl = (string) ($data['pspSwitchingUrl'] ?? '');
        $purchaseId = (string) ($data['purchaseId'] ?? '');

        if ($pspSwitchingUrl === '') {
            $message = (string) ($data['message'] ?? ($data['code'] ?? 'Purchase request failed'));
            $this->dispatch(new PaymentFailed($this->getName(), $message, 0));
            throw new GatewayException($message, $this->getName(), 0);
        }

        return RedirectResponse::redirect($pspSwitchingUrl, $purchaseId);
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $purchaseId = (string) ($callbackData['purchaseId'] ?? '');
        $status = (string) ($callbackData['status'] ?? '');

        if ($purchaseId === '' || ($status !== '' && strtoupper($status) !== 'SUCCESSFUL')) {
            $message = "Payment failed with status: {$status}";
            $this->dispatch(new PaymentFailed($this->getName(), $message, 0));
            throw new VerificationException($message, $this->getName(), 0);
        }

        $data = $this->postJson(self::API_URL . "/purchases/{$purchaseId}/verify", [
            'purchaseId' => $purchaseId,
        ], $this->authHeaders());

        if (strtoupper((string) ($data['status'] ?? '')) !== 'SUCCESSFUL') {
            $message = (string) ($data['message'] ?? ($data['code'] ?? 'Verification failed'));
            $this->dispatch(new PaymentFailed($this->getName(), $message, 0));
            throw new VerificationException($message, $this->getName(), 0);
        }

        $amount = (int) ($data['amount'] ?? $callbackData['amount'] ?? 0);
        $pspReference = (string) ($data['pspReferenceNumber'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($purchaseId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($amount),
            status: TransactionStatus::Verified,
            referenceId: $purchaseId,
            trackingCode: $pspReference,
            cardNumber: $this->nullIfEmpty((string) ($data['payerMaskedCardNumber'] ?? '')),
            extra: ['clientReferenceNumber' => $data['clientReferenceNumber'] ?? ''],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }

    /**
     * Two-key handshake → bearer access token.
     *
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        $token = $this->postJson(self::API_URL . '/tokens', [
            'apiKey' => $this->config->apiKey,
            'secretKey' => $this->config->secretKey,
        ]);

        $accessToken = (string) ($token['accessToken'] ?? '');
        if ($accessToken === '') {
            $message = (string) ($token['message'] ?? 'Failed to obtain Jibit access token');
            throw new GatewayException($message, $this->getName(), 0);
        }

        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
