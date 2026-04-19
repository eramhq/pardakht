<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\IDPay;

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
 * IDPay payment gateway (REST API).
 */
final class IDPayGateway extends AbstractGateway
{
    private const API_URL = 'https://api.idpay.ir/v1.1/payment';

    public function __construct(
        private readonly IDPayConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
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

        $data = $this->postJson(self::API_URL, [
            'order_id' => $request->getOrderId(),
            'amount' => $request->getAmount()->inRials(),
            'callback' => $request->getCallbackUrl(),
            'desc' => $request->getDescription(),
            'phone' => $request->getMobile(),
            'mail' => $request->getEmail(),
        ], $headers);


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

        $data = $this->postJson(self::API_URL . '/verify', [
            'id' => $id,
            'order_id' => $orderId,
        ], $headers);

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
            cardNumber: $this->nullIfEmpty($cardNo),
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
