<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Vandar;

final class VandarConfig
{
    public function __construct(
        public string $apiKey,
    ) {
    }
}
