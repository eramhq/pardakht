<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Sizpay;

use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Event\CallbackReceived;
use Eram\Pardakht\Event\PaymentFailed;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PurchaseInitiated;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\AbstractGateway;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\RedirectResponse;
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Transaction\Transaction;
use Eram\Pardakht\Transaction\TransactionId;
use Eram\Pardakht\Transaction\TransactionStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Sizpay payment gateway (REST API).
 */
final class SizpayGateway extends AbstractGateway
{
    private const TOKEN_URL = 'https://rt.sizpay.ir/KimiaIPGRouteService.asmx/GetToken';
    private const VERIFY_URL = 'https://rt.sizpay.ir/KimiaIPGRouteService.asmx/KimiaConfirmPayment';
    private const GATEWAY_URL = 'https://rt.sizpay.ir/Route/Payment';

    public function __construct(
        private readonly SizpayConfig $config,
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
        return 'sizpay';
    }

    public function purchase(PurchaseRequest $request): RedirectResponse
    {
        $this->dispatch(new PurchaseInitiated($this->getName(), $request));

        $signData = \hash('sha256', \implode('', [
            $this->config->merchantId,
            $this->config->terminalId,
            $request->getAmount()->inRials(),
            $request->getOrderId(),
            $this->config->signKey,
        ]));

        $response = $this->postJson(self::TOKEN_URL, [
            'MerchantID' => $this->config->merchantId,
            'TerminalID' => $this->config->terminalId,
            'UserName' => $this->config->username,
            'Password' => $this->config->password,
            'Amount' => $request->getAmount()->inRials(),
            'OrderID' => (int) $request->getOrderId(),
            'ReturnURL' => $request->getCallbackUrl(),
            'InvoiceNo' => (int) $request->getOrderId(),
            'DocDate' => \date('Y/m/d'),
            'ExtraInf' => $request->getDescription(),
            'SignData' => $signData,
        ]);

        $data = $this->decodeResponse($response);
        $resCode = (int) ($data['ResCod'] ?? -1);

        if ($resCode !== 0) {
            $message = (string) ($data['Message'] ?? "Request failed with code: {$resCode}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resCode));

            throw new GatewayException($message, $this->getName(), $resCode);
        }

        $token = (string) ($data['Token'] ?? '');

        return RedirectResponse::post(
            self::GATEWAY_URL,
            $token,
            ['Token' => $token],
        );
    }

    public function verify(?array $callbackData = null): TransactionInterface
    {
        $callbackData = $this->resolveCallbackData($callbackData);
        $this->dispatch(new CallbackReceived($this->getName(), $callbackData));

        $resCode = (int) ($callbackData['ResCod'] ?? -1);
        $refNo = (string) ($callbackData['RefNo'] ?? '');
        $token = (string) ($callbackData['Token'] ?? '');
        $orderId = (string) ($callbackData['OrderId'] ?? '');
        $cardNo = (string) ($callbackData['CardNo'] ?? '');
        $amount = (int) ($callbackData['Amount'] ?? 0);

        if ($resCode !== 0) {
            $message = (string) ($callbackData['Message'] ?? "Payment failed with code: {$resCode}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $resCode));

            throw new VerificationException($message, $this->getName(), $resCode);
        }

        $signData = \hash('sha256', \implode('', [
            $this->config->merchantId,
            $this->config->terminalId,
            $amount,
            $orderId,
            $this->config->signKey,
        ]));

        $response = $this->postJson(self::VERIFY_URL, [
            'MerchantID' => $this->config->merchantId,
            'TerminalID' => $this->config->terminalId,
            'UserName' => $this->config->username,
            'Password' => $this->config->password,
            'Token' => $token,
            'SignData' => $signData,
        ]);

        $data = $this->decodeResponse($response);
        $verifyResCode = (int) ($data['ResCod'] ?? -1);

        if ($verifyResCode !== 0) {
            $message = (string) ($data['Message'] ?? "Verification failed with code: {$verifyResCode}");

            $this->dispatch(new PaymentFailed($this->getName(), $message, $verifyResCode));

            throw new VerificationException($message, $this->getName(), $verifyResCode);
        }

        $transaction = new Transaction(
            id: new TransactionId($orderId),
            gatewayName: $this->getName(),
            amount: Amount::fromRials($amount),
            status: TransactionStatus::Verified,
            referenceId: $token,
            trackingCode: $refNo,
            cardNumber: $cardNo !== '' ? $cardNo : null,
            extra: [
                'RefNo' => $refNo,
                'TraceNo' => $data['TraceNo'] ?? '',
            ],
        );

        $this->dispatch(new PaymentVerified($this->getName(), $transaction));

        return $transaction;
    }
}
