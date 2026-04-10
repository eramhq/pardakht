<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Zibal;

use EramDev\Pardakht\Contracts\TransactionInterface;
use EramDev\Pardakht\Event\CallbackReceived;
use EramDev\Pardakht\Event\PaymentFailed;
use EramDev\Pardakht\Event\PaymentVerified;
use EramDev\Pardakht\Event\PurchaseInitiated;
use EramDev\Pardakht\Exception\GatewayException;
use EramDev\Pardakht\Exception\VerificationException;
use EramDev\Pardakht\Gateway\AbstractGateway;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Http\RedirectResponse;
use EramDev\Pardakht\Money\Amount;
use EramDev\Pardakht\Transaction\Transaction;
use EramDev\Pardakht\Transaction\TransactionId;
use EramDev\Pardakht\Transaction\TransactionStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Zibal payment gateway (REST API).
 */
final class ZibalGateway extends AbstractGateway
{
    private const API_URL = 'https://gateway.zibal.ir/v1';
    private const GATEWAY_URL = 'https://gateway.zibal.ir/start/';

    public function __construct(
        private readonly ZibalConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $requestFactory, $streamFactory, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'zibal';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $response = $this->postJson(self::API_URL . '/request', [
            'merchant' => $this->config->merchant,
            'amount' => $request->getAmount()->inRials(),
            'callbackUrl' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'orderId' => $request->getOrderId(),
            'mobile' => $request->getMobile(),
        ]);

        $data = $this->decodeResponse($response);
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

        $response = $this->postJson(self::API_URL . '/verify', [
            'merchant' => $this->config->merchant,
            'trackId' => $trackId,
        ]);

        $data = $this->decodeResponse($response);
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
            cardNumber: $cardNumber !== '' ? $cardNumber : null,
            extra: [
                'orderId' => $orderId,
                'paidAt' => $data['paidAt'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
