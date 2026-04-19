<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\IDPay\IDPayConfig;
use Eram\Pardakht\Gateway\IDPay\IDPayGateway;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IDPayGatewayTest extends TestCase
{
    #[Test]
    public function gateway_name(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
        );

        $this->assertSame('idpay', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_redirect(): void
    {
        $httpClient = $this->createMockHttpClient(201, json_encode([
            'id' => 'abc123',
            'link' => 'https://idpay.ir/p/ws/abc123',
        ]));

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
        );

        $response = $gateway->purchase(new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-1',
        ));

        $this->assertSame('https://idpay.ir/p/ws/abc123', $response->getUrl());
        $this->assertSame('abc123', $response->getReferenceId());
    }

    #[Test]
    public function purchase_throws_on_error(): void
    {
        $httpClient = $this->createMockHttpClient(403, json_encode([
            'error_code' => 32,
            'error_message' => 'Invalid API key',
        ]));

        $gateway = new IDPayGateway(
            new IDPayConfig('invalid-key'),
            $httpClient,
        );

        $this->expectException(GatewayException::class);

        $gateway->purchase(new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-1',
        ));
    }

    #[Test]
    public function verify_successful(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'status' => 100,
            'track_id' => 12345,
            'amount' => 500000,
            'payment' => [
                'card_no' => '610433******8718',
                'hashed_card_no' => 'hash123',
            ],
        ]));

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
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
        $httpClient = $this->createMock(HttpClient::class);

        $gateway = new IDPayGateway(
            new IDPayConfig('test-api-key'),
            $httpClient,
        );

        $this->expectException(VerificationException::class);

        $gateway->verify([
            'status' => 7,
            'id' => 'abc123',
            'order_id' => 'ORDER-1',
        ]);
    }

    private function createMockHttpClient(int $statusCode, string $body): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')
            ->willReturn(new HttpResponse($statusCode, $body));

        return $client;
    }
}
