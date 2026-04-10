<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\NextPay;

final class NextPayConfig
{
    public function __construct(
        public string $apiKey,
    ) {
    }
}
