<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalGateway;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ZarinpalGatewayTest extends TestCase
{
    #[Test]
    public function purchase_returns_redirect_response(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'data' => [
                'code' => 100,
                'authority' => 'A00000000000000000000000000217885159',
            ],
        ]));

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test-merchant-id', sandbox: true),
            $httpClient,
        );

        $response = $gateway->purchase(new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: '12345',
            description: 'Test payment',
        ));

        $this->assertStringContainsString('A00000000000000000000000000217885159', $response->getUrl());
        $this->assertSame('A00000000000000000000000000217885159', $response->getReferenceId());
        $this->assertSame('GET', $response->getMethod());
    }

    #[Test]
    public function purchase_throws_on_error(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'data' => [],
            'errors' => [
                'code' => -1,
                'message' => 'Invalid merchant ID',
            ],
        ]));

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('invalid-merchant'),
            $httpClient,
        );

        $this->expectException(GatewayException::class);

        $gateway->purchase(new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: '12345',
        ));
    }

    #[Test]
    public function verify_returns_transaction(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'data' => [
                'code' => 100,
                'ref_id' => 123456789,
                'card_pan' => '610433******8718',
                'amount' => 500000,
                'fee_type' => 'Merchant',
                'fee' => 0,
            ],
        ]));

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test-merchant-id'),
            $httpClient,
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
        $httpClient = $this->createMock(HttpClient::class);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test-merchant-id'),
            $httpClient,
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
        $httpClient = $this->createMock(HttpClient::class);

        $gateway = new ZarinpalGateway(
            new ZarinpalConfig('test'),
            $httpClient,
        );

        $this->assertSame('zarinpal', $gateway->getName());
    }

    private function createMockHttpClient(int $statusCode, string $body): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')
            ->willReturn(new HttpResponse($statusCode, $body));

        return $client;
    }
}
