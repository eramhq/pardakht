<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Sizpay;

final class SizpayConfig
{
    public function __construct(
        public string $merchantId,
        public string $terminalId,
        public string $username,
        public string $password,
        public string $signKey,
    ) {
    }
}
