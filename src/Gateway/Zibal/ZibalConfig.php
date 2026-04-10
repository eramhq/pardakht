<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Zibal;

final class ZibalConfig
{
    public function __construct(
        public string $merchant,
    ) {
    }
}
