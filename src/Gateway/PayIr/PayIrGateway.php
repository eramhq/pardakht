<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\PayIr;

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
 * Pay.ir payment gateway (REST API).
 */
final class PayIrGateway extends AbstractGateway
{
    private const API_URL = 'https://pay.ir/pg';
    private const GATEWAY_URL = 'https://pay.ir/pg/';

    public function __construct(
        private readonly PayIrConfig $config,
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
        return 'payir';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $response = $this->postJson(self::API_URL . '/send', [
            'api' => $this->config->apiKey,
            'amount' => $request->getAmount()->inRials(),
            'redirect' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'mobile' => $request->getMobile(),
            'factorNumber' => $request->getOrderId(),
        ]);

        $data = $this->decodeResponse($response);
        $status = (int) ($data['status'] ?? 0);

        if ($status !== 1) {
            $message = (string) ($data['errorMessage'] ?? 'Request failed');

            $this->dispatch(new PaymentFailed($this->getName(), $message, (int) ($data['errorCode'] ?? 0)));

            throw new GatewayException($message, $this->getName(), (int) ($data['errorCode'] ?? 0));
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

        $status = (int) ($callbackData['status'] ?? 0);
        $token = (string) ($callbackData['token'] ?? '');

        if ($status !== 1) {
            $this->dispatch(new PaymentFailed($this->getName(), 'Payment was not successful', $status));

            throw new VerificationException('Payment was not successful', $this->getName(), $status);
        }

        $response = $this->postJson(self::API_URL . '/verify', [
            'api' => $this->config->apiKey,
            'token' => $token,
        ]);

        $data = $this->decodeResponse($response);
        $verifyStatus = (int) ($data['status'] ?? 0);

        if ($verifyStatus !== 1) {
            $message = (string) ($data['errorMessage'] ?? 'Verification failed');

            $this->dispatch(new PaymentFailed($this->getName(), $message, $verifyStatus));

            throw new VerificationException($message, $this->getName(), $verifyStatus);
        }

        $transId = (string) ($data['transId'] ?? '');
        $cardNumber = (string) ($data['cardNumber'] ?? '');

        $transaction = new Transaction(
            id: new TransactionId($transId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials((int) ($data['amount'] ?? 0)),
            status: TransactionStatus::Verified,
            referenceId: $token,
            trackingCode: $transId,
            cardNumber: $cardNumber !== '' ? $cardNumber : null,
            extra: [
                'factorNumber' => $data['factorNumber'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
