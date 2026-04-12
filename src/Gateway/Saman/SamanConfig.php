<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Saman;

final class SamanConfig
{
    public function __construct(
        public string $merchantId,
    ) {}
}
