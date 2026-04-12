<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Vandar;

final class VandarConfig
{
    public function __construct(
        public string $apiKey,
    ) {}
}
