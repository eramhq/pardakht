<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\PayIr;

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
 * Pay.ir payment gateway (REST API).
 */
final class PayIrGateway extends AbstractGateway
{
    private const API_URL = 'https://pay.ir/pg';
    private const GATEWAY_URL = 'https://pay.ir/pg/';

    public function __construct(
        private readonly PayIrConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'payir';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $data = $this->postJson(self::API_URL . '/send', [
            'api' => $this->config->apiKey,
            'amount' => $request->getAmount()->inRials(),
            'redirect' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'mobile' => $request->getMobile(),
            'factorNumber' => $request->getOrderId(),
        ]);

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

        $data = $this->postJson(self::API_URL . '/verify', [
            'api' => $this->config->apiKey,
            'token' => $token,
        ]);

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
            cardNumber: $this->nullIfEmpty($cardNumber),
            extra: [
                'factorNumber' => $data['factorNumber'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
