<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Tests\Unit\Banking;

use EramDev\Pardakht\Banking\Sheba;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShebaTest extends TestCase
{
    #[Test]
    public function validates_correct_sheba(): void
    {
        // Valid IR IBAN: IR062960000000100324200001
        $sheba = new Sheba('IR062960000000100324200001');

        $this->assertSame('IR062960000000100324200001', $sheba->value());
    }

    #[Test]
    public function adds_ir_prefix_if_missing(): void
    {
        $sheba = new Sheba('062960000000100324200001');

        $this->assertSame('IR062960000000100324200001', $sheba->value());
    }

    #[Test]
    public function strips_spaces_and_dashes(): void
    {
        $sheba = new Sheba('IR06 2960 0000 0010 0324 2000 01');

        $this->assertSame('IR062960000000100324200001', $sheba->value());
    }

    #[Test]
    public function rejects_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Sheba('IR12345');
    }

    #[Test]
    public function rejects_invalid_checksum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('checksum');

        // Deliberately wrong check digits
        new Sheba('IR992960000000100324200001');
    }

    #[Test]
    public function digits_without_prefix(): void
    {
        $sheba = new Sheba('IR062960000000100324200001');

        $this->assertSame('062960000000100324200001', $sheba->digits());
    }

    #[Test]
    public function formatted_output(): void
    {
        $sheba = new Sheba('IR062960000000100324200001');
        $formatted = $sheba->formatted();

        $this->assertSame('IR06 2960 0000 0010 0324 2000 01', $formatted);
    }

    #[Test]
    public function static_validation(): void
    {
        $this->assertTrue(Sheba::isValid('IR062960000000100324200001'));
        $this->assertFalse(Sheba::isValid('IR12345'));
        $this->assertFalse(Sheba::isValid('IR992960000000100324200001'));
    }

    #[Test]
    public function equality(): void
    {
        $a = new Sheba('IR062960000000100324200001');
        $b = new Sheba('062960000000100324200001');

        $this->assertTrue($a->equals($b));
    }
}
