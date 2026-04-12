<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Mellat;

final class MellatConfig
{
    public function __construct(
        public int $terminalId,
        public string $username,
        public string $password,
    ) {}
}
