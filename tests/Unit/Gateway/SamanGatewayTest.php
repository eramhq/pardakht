<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Gateway;

use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Gateway\Saman\SamanConfig;
use Eram\Pardakht\Gateway\Saman\SamanGateway;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\SoapClientFactory;
use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Transaction\TransactionStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SamanGatewayTest extends TestCase
{
    #[Test]
    public function gateway_name(): void
    {
        $soapFactory = $this->createMock(SoapClientFactory::class);
        $gateway = new SamanGateway(new SamanConfig('test-merchant'), $soapFactory);

        $this->assertSame('saman', $gateway->getName());
    }

    #[Test]
    public function purchase_returns_post_form(): void
    {
        $soapFactory = $this->createMock(SoapClientFactory::class);
        $gateway = new SamanGateway(new SamanConfig('test-merchant'), $soapFactory);

        $request = new PurchaseRequest(
            amount: Amount::fromToman(50_000),
            callbackUrl: 'https://example.com/callback',
            orderId: 'ORDER-123',
        );

        $response = $gateway->purchase($request);

        $this->assertTrue($response->isPost());
        $this->assertSame('ORDER-123', $response->getReferenceId());
        $formData = $response->getFormData();
        $this->assertSame('test-merchant', $formData['MID']);
        $this->assertSame('500000', $formData['Amount']);
    }

    #[Test]
    public function verify_successful(): void
    {
        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient->method('__soapCall')
            ->with('verifyTransaction', $this->anything())
            ->willReturn(500000); // Returns verified amount

        $soapFactory = $this->createMock(SoapClientFactory::class);
        $soapFactory->method('create')->willReturn($soapClient);

        $gateway = new SamanGateway(new SamanConfig('test-merchant'), $soapFactory);

        $transaction = $gateway->verify([
            'State' => 'OK',
            'RefNum' => 'REF123',
            'ResNum' => 'ORDER-123',
            'TraceNo' => 'TRACE456',
            'SecurePan' => '610433******8718',
        ]);

        $this->assertSame(TransactionStatus::Verified, $transaction->getStatus());
        $this->assertSame(500_000, $transaction->getAmount()->inRials());
        $this->assertSame('REF123', $transaction->getReferenceId());
        $this->assertSame('TRACE456', $transaction->getTrackingCode());
    }

    #[Test]
    public function verify_throws_on_non_ok_state(): void
    {
        $soapFactory = $this->createMock(SoapClientFactory::class);
        $gateway = new SamanGateway(new SamanConfig('test-merchant'), $soapFactory);

        $this->expectException(VerificationException::class);

        $gateway->verify([
            'State' => 'Failed',
            'StateCode' => '-1',
        ]);
    }
}
