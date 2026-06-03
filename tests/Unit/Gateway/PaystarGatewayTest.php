<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Paystar\PaystarConfig;
use Eram\Pardakht\Gateway\Paystar\PaystarGateway;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaystarGatewayTest extends TestCase
{
    #[Test]
    public function gateway_name(): void
    {
        $gateway = new PaystarGateway(
            new PaystarConfig('gw-id', 'sign-key'),
            $this->createMock(HttpClient::class),
        );

        $this->assertSame('paystar', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_post_redirect_with_token(): void
    {
        $httpClient = $this->createMockHttpClient(json_encode([
            'status' => 1,
            'data' => ['token' => 'TKN-9', 'ref_num' => 'REF-1'],
        ]));

        $gateway = new PaystarGateway(new PaystarConfig('gw-id', 'sign-key'), $httpClient);

        $response = $gateway->purchase($this->request());

        $this->assertSame('https://core.paystar.ir/api/pardakht/payment', $response->getUrl());
        $this->assertSame('REF-1', $response->getReferenceId());
        $this->assertTrue($response->isPost());
        $this->assertSame('TKN-9', $response->getFormData()['token']);
    }

    #[Test]
    public function purchase_throws_on_error_status(): void
    {
        $httpClient = $this->createMockHttpClient(json_encode([
            'status' => -1,
            'message' => 'invalid sign',
        ]));

        $gateway = new PaystarGateway(new PaystarConfig('gw-id', 'sign-key'), $httpClient);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('invalid sign');

        $gateway->purchase($this->request());
    }

    #[Test]
    public function verify_returns_verified_transaction(): void
    {
        $httpClient = $this->createMockHttpClient(json_encode(['status' => 1]));

        $gateway = new PaystarGateway(new PaystarConfig('gw-id', 'sign-key'), $httpClient);

        $transaction = $gateway->verify([
            'status' => 1,
            'ref_num' => 'REF-1',
            'order_id' => 'ORDER-1',
            'amount' => 100_000,
            'card_number' => '603799******1234',
            'tracking_code' => '987654321',
        ]);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame('REF-1', $transaction->getReferenceId());
        $this->assertSame('987654321', $transaction->getTrackingCode());
        $this->assertSame(100_000, $transaction->getAmount()->inRials());
    }

    #[Test]
    public function verify_throws_when_callback_not_successful(): void
    {
        $gateway = new PaystarGateway(
            new PaystarConfig('gw-id', 'sign-key'),
            $this->createMock(HttpClient::class),
        );

        $this->expectException(VerificationException::class);

        $gateway->verify(['status' => -1, 'ref_num' => 'REF-1']);
    }

    private function request(): PurchaseRequest
    {
        return new PurchaseRequest(
            amount: Amount::fromToman(10_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-1',
            description: 'Test payment',
        );
    }

    private function createMockHttpClient(string $body): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')->willReturn(new HttpResponse(200, $body));

        return $client;
    }
}
