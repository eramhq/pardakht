<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Jibit\JibitConfig;
use Eram\Pardakht\Gateway\Jibit\JibitGateway;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JibitGatewayTest extends TestCase
{
    #[Test]
    public function gateway_name(): void
    {
        $gateway = new JibitGateway(
            new JibitConfig('api', 'secret'),
            $this->createMock(HttpClient::class),
        );

        $this->assertSame('jibit', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_redirect_to_psp_switching_url(): void
    {
        // 1st call = token handshake, 2nd = create purchase.
        $gateway = new JibitGateway(new JibitConfig('api', 'secret'), $this->mockClient(
            ['accessToken' => 'TOK'],
            ['pspSwitchingUrl' => 'https://gateway.jibit.ir/p/123', 'purchaseId' => 'PUR-1'],
        ));

        $response = $gateway->purchase($this->request());

        $this->assertSame('https://gateway.jibit.ir/p/123', $response->getUrl());
        $this->assertSame('PUR-1', $response->getReferenceId());
    }

    #[Test]
    public function purchase_throws_when_no_switching_url(): void
    {
        $gateway = new JibitGateway(new JibitConfig('api', 'secret'), $this->mockClient(
            ['accessToken' => 'TOK'],
            ['code' => 'forbidden', 'message' => 'merchant blocked'],
        ));

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('merchant blocked');

        $gateway->purchase($this->request());
    }

    #[Test]
    public function verify_returns_verified_transaction(): void
    {
        $gateway = new JibitGateway(new JibitConfig('api', 'secret'), $this->mockClient(
            ['accessToken' => 'TOK'],
            ['status' => 'SUCCESSFUL', 'amount' => 100_000, 'pspReferenceNumber' => 'PSP-9'],
        ));

        $transaction = $gateway->verify(['purchaseId' => 'PUR-1', 'status' => 'SUCCESSFUL']);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame('PUR-1', $transaction->getReferenceId());
        $this->assertSame('PSP-9', $transaction->getTrackingCode());
        $this->assertSame(100_000, $transaction->getAmount()->inRials());
    }

    #[Test]
    public function verify_throws_when_callback_failed(): void
    {
        $gateway = new JibitGateway(
            new JibitConfig('api', 'secret'),
            $this->createMock(HttpClient::class),
        );

        $this->expectException(VerificationException::class);

        $gateway->verify(['purchaseId' => 'PUR-1', 'status' => 'FAILED']);
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

    /** Returns a client whose consecutive postJson calls yield the given JSON bodies. */
    private function mockClient(array ...$bodies): HttpClient
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('postJson')->willReturnOnConsecutiveCalls(
            ...array_map(fn(array $b) => new HttpResponse(200, json_encode($b)), $bodies),
        );

        return $client;
    }
}
