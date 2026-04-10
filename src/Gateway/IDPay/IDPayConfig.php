<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\IDPay;

final class IDPayConfig
{
    public function __construct(
        public string $apiKey,
        public bool $sandbox = false,
    ) {
    }
}
