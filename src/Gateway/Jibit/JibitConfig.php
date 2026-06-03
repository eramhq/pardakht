<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Jibit;

final class JibitConfig
{
    public function __construct(
        public string $apiKey,
        public string $secretKey,
    ) {}
}
