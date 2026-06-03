<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Paystar;

final class PaystarConfig
{
    public function __construct(
        public string $gatewayId,
        public string $signKey,
    ) {}
}
