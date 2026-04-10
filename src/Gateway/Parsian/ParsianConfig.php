<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Parsian;

final class ParsianConfig
{
    public function __construct(
        public string $pin,
    ) {
    }
}
