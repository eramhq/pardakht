<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Zarinpal;

final class ZarinpalConfig
{
    public function __construct(
        public string $merchantId,
        public bool $sandbox = false,
    ) {
    }
}
