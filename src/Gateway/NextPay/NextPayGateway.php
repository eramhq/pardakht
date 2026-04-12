<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\NextPay;

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
 * NextPay payment gateway (REST API).
 */
final class NextPayGateway extends AbstractGateway
{
    private const API_URL = 'https://nextpay.org/nx/gateway';
    private const GATEWAY_URL = 'https://nextpay.org/nx/gateway/payment/';

    public function __construct(
        private readonly NextPayConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'nextpay';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $data = $this->postJson(self::API_URL . '/token', [
            'api_key' => $this->config->apiKey,
            'amount' => $request->getAmount()->inRials(),
            'callback_uri' => $request->getCallbackUrl(),
            'order_id' => $request->getOrderId(),
            'customer_phone' => $request->getMobile(),
        ]);

        $code = (int) ($data['code'] ?? 0);

        if ($code !== -1) {
            $message = (string) ($data['message'] ?? "Request failed with code: {$code}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $code));

            throw new GatewayException($message, $this->getName(), $code);
        }

        $transId = (string) ($data['trans_id'] ?? '');

        return RedirectResponse::redirect(
            self::GATEWAY_URL . $transId,
            $transId,
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $transId = (string) ($callbackData['trans_id'] ?? '');
        $orderId = (string) ($callbackData['order_id'] ?? '');
        $amount = (int) ($callbackData['amount'] ?? 0);

        $data = $this->postJson(self::API_URL . '/verify', [
            'api_key' => $this->config->apiKey,
            'trans_id' => $transId,
            'amount' => $amount,
        ]);

        $code = (int) ($data['code'] ?? -1);

        if ($code !== 0) {
            $message = (string) ($data['message'] ?? "Verification failed with code: {$code}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $code));

            throw new VerificationException($message, $this->getName(), $code);
        }

        $cardHolder = (string) ($data['card_holder'] ?? '');
        $shaparakRefId = (string) ($data['Shaparak_Ref_Id'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($transId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($amount),
            status: TransactionStatus::Verified,
            referenceId: $transId,
            trackingCode: $shaparakRefId,
            cardNumber: $this->nullIfEmpty($cardHolder),
            extra: [
                'order_id' => $orderId,
                'Shaparak_Ref_Id' => $shaparakRefId,
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
