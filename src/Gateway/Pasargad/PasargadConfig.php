<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Pasargad;

final class PasargadConfig
{
    public function __construct(
        public string $merchantCode,
        public string $terminalCode,
        public string $privateKey,
    ) {
    }
}
