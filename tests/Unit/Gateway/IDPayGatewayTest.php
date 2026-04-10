<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Tests\Unit\Gateway;

use EramDev\Pardakht\Exception\GatewayException;
use EramDev\Pardakht\Exception\VerificationException;
use EramDev\Pardakht\Gateway\IDPay\IDPayConfig;
use EramDev\Pardakht\Gateway\IDPay\IDPayGateway;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Money\Amount;
use EramDev\Pardakht\Transaction\TransactionStatus;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

final class IDPayGatewayTest extends TestCase
{
    private HttpFactory $httpFactory;

    protected function setUp(): void
    {
        $this->httpFactory = new HttpFactory();
    }

    #[Test]
    public function gateway_name(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $this->assertSame('idpay', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_redirect(): void
    {
        $responseBody = \json_encode([
            'id' => 'abc123',
            'link' => 'https://idpay.ir/p/ws/abc123',
        ]);

        $httpClient = $this->createMockHttpClient(201, $responseBody);

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-1',
        );

        $response = $gateway->purchase($request);

        $this->assertSame('https://idpay.ir/p/ws/abc123', $response->getUrl());
        $this->assertSame('abc123', $response->getReferenceId());
    }

    #[Test]
    public function purchase_throws_on_error(): void
    {
        $responseBody = \json_encode([
            'error_code' => 32,
            'error_message' => 'Invalid API key',
        ]);

        $httpClient = $this->createMockHttpClient(403, $responseBody);

        $gateway = new IDPayGateway(
            new IDPayConfig('invalid-key'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-1',
        );

        $this->expectException(GatewayException::class);

        $gateway->purchase($request);
    }

    #[Test]
    public function verify_successful(): void
    {
        $responseBody = \json_encode([
            'status' => 100,
            'track_id' => 12345,
            'amount' => 500000,
            'payment' => [
                'card_no' => '610433******8718',
                'hashed_card_no' => 'hash123',
            ],
        ]);

        $httpClient = $this->createMockHttpClient(200, $responseBody);

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $transaction = $gateway->verify([
            'status' => 10,
            'id' => 'abc123',
            'order_id' => 'ORDER-1',
        ]);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame('12345', $transaction->getTrackingCode());
        $this->assertSame('610433******8718', $transaction->getCardNumber());
    }

    #[Test]
    public function verify_throws_on_failed_status(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $this->expectException(VerificationException::class);

        $gateway->verify([
            'status' => 7,
            'id' => 'abc123',
            'order_id' => 'ORDER-1',
        ]);
    }

    private function createMockHttpClient(int $statusCode, string $body): ClientInterface
    {
        $response = $this->httpFactory->createResponse($statusCode)
            ->withBody($this->httpFactory->createStream($body))
            ->withHeader('Content-Type', 'application/json');

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return $client;
    }
}
