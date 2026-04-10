<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Saman;

final class SamanConfig
{
    public function __construct(
        public string $merchantId,
    ) {
    }
}
