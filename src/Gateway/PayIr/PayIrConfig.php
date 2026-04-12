<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\PayIr;

final class PayIrConfig
{
    public function __construct(
        public string $apiKey,
    ) {}
}
