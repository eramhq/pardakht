<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Sadad;

final class SadadConfig
{
    public function __construct(
        public string $merchantId,
        public string $terminalId,
        public string $terminalKey,
    ) {}
}
