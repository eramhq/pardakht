<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Money;

use Eram\Pardakht\Money\Currency;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    #[Test]
    public function irr_values(): void
    {
        $currency = Currency::IRR;

        $this->assertSame('IRR', $currency->value);
        $this->assertSame('ریال', $currency->label());
    }

    #[Test]
    public function irt_values(): void
    {
        $currency = Currency::IRT;

        $this->assertSame('IRT', $currency->value);
        $this->assertSame('تومان', $currency->label());
    }

    #[Test]
    public function can_be_created_from_string(): void
    {
        $currency = Currency::from('IRR');

        $this->assertSame(Currency::IRR, $currency);
    }
}
