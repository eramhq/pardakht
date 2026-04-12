<?php

declare(strict_types=1);

namespace Eram\Pardakht\Tests\Unit\Money;

use Eram\Pardakht\Exception\InvalidAmountException;
use Eram\Pardakht\Money\Amount;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    #[Test]
    public function creates_from_rials(): void
    {
        $amount = Amount::fromRials(500_000);

        $this->assertSame(500_000, $amount->inRials());
        $this->assertSame(50_000, $amount->inToman());
    }

    #[Test]
    public function creates_from_toman(): void
    {
        $amount = Amount::fromToman(50_000);

        $this->assertSame(500_000, $amount->inRials());
        $this->assertSame(50_000, $amount->inToman());
    }

    #[Test]
    public function toman_and_rials_are_interchangeable(): void
    {
        $fromRials = Amount::fromRials(500_000);
        $fromToman = Amount::fromToman(50_000);

        $this->assertTrue($fromRials->equals($fromToman));
    }

    #[Test]
    public function throws_on_negative_amount(): void
    {
        $this->expectException(InvalidAmountException::class);

        Amount::fromRials(-100);
    }

    #[Test]
    public function zero_amount(): void
    {
        $amount = Amount::fromRials(0);

        $this->assertTrue($amount->isZero());
        $this->assertSame(0, $amount->inRials());
        $this->assertSame(0, $amount->inToman());
    }

    #[Test]
    public function adds_amounts(): void
    {
        $a = Amount::fromToman(10_000);
        $b = Amount::fromToman(20_000);

        $sum = $a->add($b);

        $this->assertSame(30_000, $sum->inToman());
    }

    #[Test]
    public function subtracts_amounts(): void
    {
        $a = Amount::fromToman(30_000);
        $b = Amount::fromToman(10_000);

        $diff = $a->subtract($b);

        $this->assertSame(20_000, $diff->inToman());
    }

    #[Test]
    public function subtract_below_zero_throws(): void
    {
        $this->expectException(InvalidAmountException::class);

        $a = Amount::fromToman(10_000);
        $b = Amount::fromToman(20_000);

        $a->subtract($b);
    }

    #[Test]
    public function comparison_methods(): void
    {
        $small = Amount::fromToman(10_000);
        $large = Amount::fromToman(20_000);

        $this->assertTrue($large->greaterThan($small));
        $this->assertFalse($small->greaterThan($large));

        $this->assertTrue($small->lessThan($large));
        $this->assertFalse($large->lessThan($small));
    }

    #[Test]
    public function equality(): void
    {
        $a = Amount::fromRials(100);
        $b = Amount::fromRials(100);
        $c = Amount::fromRials(200);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    #[Test]
    public function immutability(): void
    {
        $original = Amount::fromToman(10_000);
        $added = $original->add(Amount::fromToman(5_000));

        $this->assertSame(10_000, $original->inToman());
        $this->assertSame(15_000, $added->inToman());
    }

    #[Test]
    public function to_string_returns_rials(): void
    {
        $amount = Amount::fromToman(50_000);

        $this->assertSame('500000', (string) $amount);
    }
}
