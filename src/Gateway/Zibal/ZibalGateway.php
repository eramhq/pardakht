<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Zibal;

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
use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Transaction\Transaction;
use Eram\Pardakht\Transaction\TransactionId;
use Eram\Pardakht\Transaction\TransactionStatus;

/**
 * Zibal payment gateway (REST API).
 */
final class ZibalGateway extends AbstractGateway
{
    private const API_URL = 'https://gateway.zibal.ir/v1';
    private const GATEWAY_URL = 'https://gateway.zibal.ir/start/';

    public function __construct(
        private readonly ZibalConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'zibal';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $data = $this->postJson(self::API_URL . '/request', [
            'merchant' => $this->config->merchant,
            'amount' => $request->getAmount()->inRials(),
            'callbackUrl' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'orderId' => $request->getOrderId(),
            'mobile' => $request->getMobile(),
        ]);

        $result = (int) ($data['result'] ?? -1);

        if ($result !== 100) {
            $message = (string) ($data['message'] ?? "Request failed with code: {$result}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $result));

            throw new GatewayException($message, $this->getName(), $result);
        }

        $trackId = (string) ($data['trackId'] ?? '');

        return RedirectResponse::redirect(
            self::GATEWAY_URL . $trackId,
            $trackId,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $success = (int) ($callbackData['success'] ?? 0);
        $trackId = (string) ($callbackData['trackId'] ?? '');
        $orderId = (string) ($callbackData['orderId'] ?? '');
        $status = (int) ($callbackData['status'] ?? -1);

        if ($success !== 1) {
            $message = "Payment failed with status: {$status}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $status));

            throw new VerificationException($message, $this->getName(), $status);
        }

        $data = $this->postJson(self::API_URL . '/verify', [
            'merchant' => $this->config->merchant,
            'trackId' => $trackId,
        ]);

        $result = (int) ($data['result'] ?? -1);

        if ($result !== 100) {
            $message = (string) ($data['message'] ?? "Verification failed with code: {$result}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $result));

            throw new VerificationException($message, $this->getName(), $result);
        }

        $cardNumber = (string) ($data['cardNumber'] ?? '');
        $refNumber = (string) ($data['refNumber'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($trackId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($data['amount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $trackId,
            trackingCode: $refNumber,
            cardNumber: $this->nullIfEmpty($cardNumber),
            extra: [
                'orderId' => $orderId,
                'paidAt' => $data['paidAt'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
