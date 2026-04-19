<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Zibal\ZibalConfig;
use Eram\Pardakht\Gateway\Zibal\ZibalGateway;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ZibalGatewayTest extends TestCase
{
    #[Test]
    public function gateway_name(): void
    {
        $gateway = new ZibalGateway(
            new ZibalConfig('zibal'),
            $this->createMock(HttpClient::class),
        );

        $this->assertSame('zibal', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_redirect_with_track_id(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'result' => 100,
            'trackId' => 123456,
            'message' => 'success',
        ]));

        $gateway = new ZibalGateway(new ZibalConfig('zibal'), $httpClient);

        $response = $gateway->purchase(new PurchaseRequest(
            amount: Amount::fromToman(10_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-1',
            description: 'Test payment',
        ));

        $this->assertSame('https://gateway.zibal.ir/start/123456', $response->getUrl());
        $this->assertSame('123456', $response->getReferenceId());
        $this->assertSame('GET', $response->getMethod());
    }

    #[Test]
    public function purchase_throws_on_error_result(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'result' => 102,
            'message' => 'merchant not found',
        ]));

        $gateway = new ZibalGateway(new ZibalConfig('invalid'), $httpClient);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('merchant not found');

        $gateway->purchase(new PurchaseRequest(
            amount: Amount::fromToman(10_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-2',
        ));
    }

    #[Test]
    public function verify_returns_verified_transaction(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'result' => 100,
            'amount' => 100000,
            'cardNumber' => '603799******1234',
            'refNumber' => '987654321',
            'paidAt' => '2026-04-12T10:00:00',
            'message' => 'success',
        ]));

        $gateway = new ZibalGateway(new ZibalConfig('zibal'), $httpClient);

        $transaction = $gateway->verify([
            'success' => 1,
            'trackId' => '123456',
            'orderId' => 'ORDER-1',
            'status' => 1,
        ]);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame('zibal', $transaction->getGatewayName());
        $this->assertSame('123456', $transaction->getReferenceId());
        $this->assertSame('987654321', $transaction->getTrackingCode());
        $this->assertSame('603799******1234', $transaction->getCardNumber());
        $this->assertSame(100_000, $transaction->getAmount()->inRials());
        $this->assertSame(10_000, $transaction->getAmount()->inToman());
        $this->assertSame('ORDER-1', $transaction->getExtra()['orderId']);
    }

    #[Test]
    public function verify_throws_when_payment_not_successful(): void
    {
        $gateway = new ZibalGateway(
            new ZibalConfig('zibal'),
            $this->createMock(HttpClient::class),
        );

        $this->expectException(VerificationException::class);

        $gateway->verify([
            'success' => 0,
            'trackId' => '123456',
            'orderId' => 'ORDER-1',
            'status' => -1,
        ]);
    }

    #[Test]
    public function verify_throws_when_api_verification_fails(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'result' => 201,
            'message' => 'already verified',
        ]));

        $gateway = new ZibalGateway(new ZibalConfig('zibal'), $httpClient);

        $this->expectException(VerificationException::class);
        $this->expectExceptionMessage('already verified');

        $gateway->verify([
            'success' => 1,
            'trackId' => '123456',
            'orderId' => 'ORDER-1',
            'status' => 1,
        ]);
    }

    #[Test]
    public function verify_handles_null_card_number(): void
    {
        $httpClient = $this->createMockHttpClient(200, json_encode([
            'result' => 100,
            'amount' => 50000,
            'cardNumber' => '',
            'refNumber' => '111222333',
        ]));

        $gateway = new ZibalGateway(new ZibalConfig('zibal'), $httpClient);

        $transaction = $gateway->verify([
            'success' => 1,
            'trackId' => '789',
            'orderId' => 'ORDER-3',
            'status' => 1,
        ]);

        $this->assertNull($transaction->getCardNumber());
        $this->assertSame('111222333', $transaction->getTrackingCode());
    }

    private function createMockHttpClient(int $statusCode, string $body): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')
            ->willReturn(new HttpResponse($statusCode, $body));

        return $client;
    }
}
