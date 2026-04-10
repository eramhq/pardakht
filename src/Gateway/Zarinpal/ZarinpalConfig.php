<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Zarinpal;

final class ZarinpalConfig
{
    public function __construct(
        public string $merchantId,
        public bool $sandbox = false,
    ) {
    }
}
