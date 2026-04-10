<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\IDPay;

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
 * IDPay payment gateway (REST API).
 */
final class IDPayGateway extends AbstractGateway
{
    private const API_URL = 'https://api.idpay.ir/v1.1/payment';

    public function __construct(
        private readonly IDPayConfig $config,
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
        return 'idpay';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $headers = [
            'X-API-KEY' => $this->config->apiKey,
        ];

        if ($this->config->sandbox) {
            $headers['X-SANDBOX'] = '1';
        }

        $response = $this->postJson(self::API_URL, [
            'order_id' => $request->getOrderId(),
            'amount' => $request->getAmount()->inRials(),
            'callback' => $request->getCallbackUrl(),
            'desc' => $request->getDescription(),
            'phone' => $request->getMobile(),
            'mail' => $request->getEmail(),
        ], $headers);

        $data = $this->decodeResponse($response);

        if (isset($data['error_code'])) {
            $message = (string) ($data['error_message'] ?? 'Unknown error');

            $this->dispatch(new PaymentFailed($this->getName(), $message, (int) $data['error_code']));

            throw new GatewayException($message, $this->getName(), (int) $data['error_code']);
        }

        $id = (string) ($data['id'] ?? '');
        $link = (string) ($data['link'] ?? '');

        return RedirectResponse::redirect($link, $id);
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $status = (int) ($callbackData['status'] ?? 0);
        $id = (string) ($callbackData['id'] ?? '');
        $orderId = (string) ($callbackData['order_id'] ?? '');

        if ($status !== 10) {
            $error = IDPayErrorCode::tryFrom($status);
            $message = $error?->message() ?? "Payment failed with status: {$status}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $status));

            throw new VerificationException($message, $this->getName(), $status);
        }

        $headers = [
            'X-API-KEY' => $this->config->apiKey,
        ];

        if ($this->config->sandbox) {
            $headers['X-SANDBOX'] = '1';
        }

        $response = $this->postJson(self::API_URL . '/verify', [
            'id' => $id,
            'order_id' => $orderId,
        ], $headers);

        $data = $this->decodeResponse($response);
        $verifyStatus = (int) ($data['status'] ?? 0);

        if ($verifyStatus !== 100 && $verifyStatus !== 101) {
            $error = IDPayErrorCode::tryFrom($verifyStatus);
            $message = $error?->message() ?? "Verification failed with status: {$verifyStatus}";

            $this->dispatch(new PaymentFailed($this->getName(), $message, $verifyStatus));

            throw new VerificationException($message, $this->getName(), $verifyStatus);
        }

        $payment = $data['payment'] ?? [];
        $trackId = (string) ($data['track_id'] ?? '');
        $cardNo = (string) ($payment['card_no'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($id),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($data['amount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $id,
            trackingCode: $trackId,
            cardNumber: $cardNo !== '' ? $cardNo : null,
            extra: [
                'order_id' => $orderId,
                'track_id' => $trackId,
                'hashed_card_no' => $payment['hashed_card_no'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
