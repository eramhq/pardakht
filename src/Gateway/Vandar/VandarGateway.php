<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Vandar;

use EramDev\Pardakht\Contracts\TransactionInterface;
use EramDev\Pardakht\Event\CallbackReceived;
use EramDev\Pardakht\Event\PaymentVerified;
use EramDev\Pardakht\Event\PurchaseInitiated;
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
 * Vandar payment gateway (REST API).
 */
final class VandarGateway extends AbstractGateway
{
    private const API_URL = 'https://ipg.vandar.io/api/v3';
    private const GATEWAY_URL = 'https://ipg.vandar.io/v3/';

    public function __construct(
        private readonly VandarConfig $config,
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
        return 'vandar';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $response = $this->postJson(self::API_URL . '/send', [
            'api_key' => $this->config->apiKey,
            'amount' => $request->getAmount()->inRials(),
            'callback_url' => $request->getCallbackUrl(),
            'description' => $request->getDescription(),
            'mobile_number' => $request->getMobile(),
            'factorNumber' => $request->getOrderId(),
        ]);

        $data = $this->decodeResponse($response);
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
            $this->dispatch(new PaymentFailed($this->getName(), 'Payment cancelled'));

            throw new VerificationException('Payment cancelled', $this->getName(), -1);
        }

        $response = $this->postJson(self::API_URL . '/verify', [
            'api_key' => $this->config->apiKey,
            'token' => $token,
        ]);

        $data = $this->decodeResponse($response);
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
            cardNumber: $cardNumber !== '' ? $cardNumber : null,
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

        return \implode(', ', \array_map(
            fn ($e) => \is_array($e) ? \implode(', ', $e) : (string) $e,
            $errors,
        ));
    }
}
