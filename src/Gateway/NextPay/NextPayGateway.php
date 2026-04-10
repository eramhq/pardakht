<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\NextPay;

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
 * NextPay payment gateway (REST API).
 */
final class NextPayGateway extends AbstractGateway
{
    private const API_URL = 'https://nextpay.org/nx/gateway';
    private const GATEWAY_URL = 'https://nextpay.org/nx/gateway/payment/';

    public function __construct(
        private readonly NextPayConfig $config,
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
        return 'nextpay';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $response = $this->postJson(self::API_URL . '/token', [
            'api_key' => $this->config->apiKey,
            'amount' => $request->getAmount()->inRials(),
            'callback_uri' => $request->getCallbackUrl(),
            'order_id' => $request->getOrderId(),
            'customer_phone' => $request->getMobile(),
        ]);

        $data = $this->decodeResponse($response);
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

        $response = $this->postJson(self::API_URL . '/verify', [
            'api_key' => $this->config->apiKey,
            'trans_id' => $transId,
            'amount' => $amount,
        ]);

        $data = $this->decodeResponse($response);
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
            cardNumber: $cardHolder !== '' ? $cardHolder : null,
            extra: [
                'order_id' => $orderId,
                'Shaparak_Ref_Id' => $shaparakRefId,
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
