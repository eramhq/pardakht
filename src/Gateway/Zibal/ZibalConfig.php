<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Zibal;

final class ZibalConfig
{
    public function __construct(
        public string $merchant,
    ) {}
}
