<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\PayIr;

final class PayIrConfig
{
    public function __construct(
        public string $apiKey,
    ) {
    }
}
