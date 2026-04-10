<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalGateway;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Transaction\TransactionStatus;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ZarinpalGatewayTest extends TestCase
{
    private HttpFactory $httpFactory;

    protected function setUp(): void
    {
        $this->httpFactory = new HttpFactory();
    }

    #[Test]
    public function purchase_returns_redirect_response(): void
    {
        $responseBody = \json_encode([
            'data' => [
                'code' => 100,
                'authority' => 'A00000000000000000000000000217885159',
            ],
        ]);

        $httpClient = $this->createMockHttpClient(200, $responseBody);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test-merchant-id', sandbox: true),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: '12345',
            description: 'Test payment',
        );

        $response = $gateway->purchase($request);

        $this->assertStringContainsString('A00000000000000000000000000217885159', $response->getUrl());
        $this->assertSame('A00000000000000000000000000217885159', $response->getReferenceId());
        $this->assertSame('GET', $response->getMethod());
    }

    #[Test]
    public function purchase_throws_on_error(): void
    {
        $responseBody = \json_encode([
            'data' => [],
            'errors' => [
                'code' => -1,
                'message' => 'Invalid merchant ID',
            ],
        ]);

        $httpClient = $this->createMockHttpClient(200, $responseBody);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('invalid-merchant'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: '12345',
        );

        $this->expectException(GatewayException::class);

        $gateway->purchase($request);
    }

    #[Test]
    public function verify_returns_transaction(): void
    {
        $responseBody = \json_encode([
            'data' => [
                'code' => 100,
                'ref_id' => 123456789,
                'card_pan' => '610433******8718',
                'amount' => 500000,
                'fee_type' => 'Merchant',
                'fee' => 0,
            ],
        ]);

        $httpClient = $this->createMockHttpClient(200, $responseBody);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test-merchant-id'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $transaction = $gateway->verify([
            'Authority' => 'A00000000000000000000000000217885159',
            'Status' => 'OK',
            'amount' => 500000,
        ]);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame(500_000, $transaction->getAmount()->inRials());
        $this->assertSame('123456789', $transaction->getTrackingCode());
        $this->assertSame('610433******8718', $transaction->getCardNumber());
        $this->assertSame('zarinpal', $transaction->getGatewayName());
    }

    #[Test]
    public function verify_throws_when_user_cancelled(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test-merchant-id'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $this->expectException(VerificationException::class);

        $gateway->verify([
            'Authority' => 'A00000000000000000000000000217885159',
            'Status' => 'NOK',
        ]);
    }

    #[Test]
    public function gateway_name(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test'),
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
        );

        $this->assertSame('zarinpal', $gateway->getName());
    }

    private function createMockHttpClient(int $statusCode, string $body): ClientInterface
    {
        $response = $this->httpFactory->createResponse($statusCode)
            ->withBody($this->httpFactory->createStream($body))
            ->withHeader('Content-Type', 'application/json');

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturn($response);

        return $client;
    }
}
