<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Banking;

use Eram\Pardakht\Banking\CardNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CardNumberTest extends TestCase
{
    #[Test]
    public function validates_valid_card_number(): void
    {
        // This is a test card number that passes Luhn (Mellat BIN)
        $card = new CardNumber('6104330000000003');

        $this->assertSame('6104330000000003', $card->number());
    }

    #[Test]
    public function static_validation(): void
    {
        $this->assertTrue(CardNumber::isValid('6104330000000003'));
        $this->assertFalse(CardNumber::isValid('1234567890123456'));
    }

    #[Test]
    public function rejects_invalid_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('16 digits');

        new CardNumber('12345');
    }

    #[Test]
    public function rejects_invalid_luhn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Luhn');

        new CardNumber('1234567890123456');
    }

    #[Test]
    public function strips_non_digit_characters(): void
    {
        $card = new CardNumber('6104-3300-0000-0003');

        $this->assertSame('6104330000000003', $card->number());
    }

    #[Test]
    public function masked_format(): void
    {
        $card = new CardNumber('6104330000000003');

        $this->assertSame('610433******0003', $card->masked());
    }

    #[Test]
    public function formatted_output(): void
    {
        $card = new CardNumber('6104330000000003');

        $this->assertSame('6104-3300-0000-0003', $card->formatted());
    }

    #[Test]
    public function detects_bank_name(): void
    {
        $card = new CardNumber('6037990000000006');

        // 603799 = Bank Melli
        $bankName = $card->bankName();

        $this->assertNotNull($bankName);
        $this->assertStringContainsString('ملی', $bankName);
    }

    #[Test]
    public function equality(): void
    {
        $a = new CardNumber('6104330000000003');
        $b = new CardNumber('6104-3300-0000-0003');

        $this->assertTrue($a->equals($b));
    }
}
