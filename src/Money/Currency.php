<?php

declare(strict_types=1);

namespace Eram\Pardakht\Money;

enum Currency: string
{
    /**
     * Iranian Rial — the official currency unit used by bank APIs.
     */
    case IRR = 'IRR';

    /**
     * Iranian Toman — the common display unit (1 Toman = 10 Rials).
     */
    case IRT = 'IRT';

    public function label(): string
    {
        return match ($this) {
            self::IRR => 'ریال',
            self::IRT => 'تومان',
        };
    }
}
