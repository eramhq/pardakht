<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Mellat\MellatConfig;
use Eram\Pardakht\Gateway\Mellat\MellatGateway;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\SoapClientFactory;
use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MellatGatewayTest extends TestCase
{
    private MellatConfig $config;

    protected function setUp(): void
    {
        $this->config = new MellatConfig(
            terminalId: 12345,
            username: 'test_user',
            password: 'test_pass',
        );
    }

    #[Test]
    public function gateway_name(): void
    {
        $soapFactory = $this->createMock(SoapClientFactory::class);
        $gateway = new MellatGateway($this->config, $soapFactory);

        $this->assertSame('mellat', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_post_redirect(): void
    {
        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient->method('__soapCall')
            ->with('bpPayRequest', $this->anything())
            ->willReturn((object) ['return' => '0,ABC123REF456']);

        $soapFactory = $this->createMock(SoapClientFactory::class);
        $soapFactory->method('create')->willReturn($soapClient);

        $gateway = new MellatGateway($this->config, $soapFactory);

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: '12345',
            description: 'Test payment',
        );

        $response = $gateway->purchase($request);

        $this->assertTrue($response->isPost());
        $this->assertSame('ABC123REF456', $response->getReferenceId());
        $this->assertArrayHasKey('RefId', $response->getFormData());
    }

    #[Test]
    public function purchase_throws_on_error_code(): void
    {
        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient->method('__soapCall')
            ->willReturn((object) ['return' => '21']);

        $soapFactory = $this->createMock(SoapClientFactory::class);
        $soapFactory->method('create')->willReturn($soapClient);

        $gateway = new MellatGateway($this->config, $soapFactory);

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: '12345',
        );

        $this->expectException(GatewayException::class);

        $gateway->purchase($request);
    }

    #[Test]
    public function verify_returns_verified_transaction(): void
    {
        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient->method('__soapCall')
            ->with('bpVerifyRequest', $this->anything())
            ->willReturn((object) ['return' => '0']);

        $soapFactory = $this->createMock(SoapClientFactory::class);
        $soapFactory->method('create')->willReturn($soapClient);

        $gateway = new MellatGateway($this->config, $soapFactory);

        $transaction = $gateway->verify([
            'ResCode' => '0',
            'RefId' => 'ABC123',
            'SaleReferenceId' => '999888',
            'SaleOrderId' => '12345',
            'CardHolderPan' => '610433******8718',
            'FinalAmount' => '500000',
        ]);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame('ABC123', $transaction->getReferenceId());
        $this->assertSame('999888', $transaction->getTrackingCode());
        $this->assertSame('610433******8718', $transaction->getCardNumber());
    }

    #[Test]
    public function verify_throws_on_non_zero_rescode(): void
    {
        $soapFactory = $this->createMock(SoapClientFactory::class);
        $gateway = new MellatGateway($this->config, $soapFactory);

        $this->expectException(VerificationException::class);

        $gateway->verify([
            'ResCode' => '43',
            'RefId' => 'ABC123',
        ]);
    }

    #[Test]
    public function settle_returns_settled_transaction(): void
    {
        // First mock verify
        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient->method('__soapCall')
            ->willReturn((object) ['return' => '0']);

        $soapFactory = $this->createMock(SoapClientFactory::class);
        $soapFactory->method('create')->willReturn($soapClient);

        $gateway = new MellatGateway($this->config, $soapFactory);

        $verified = $gateway->verify([
            'ResCode' => '0',
            'RefId' => 'ABC123',
            'SaleReferenceId' => '999888',
            'SaleOrderId' => '12345',
            'FinalAmount' => '500000',
        ]);

        $settled = $gateway->settle($verified);

        $this->assertSame(TransactionStatus::Settled, $settled->getStatus());
    }
}
