<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Event;

final class PaymentFailed
{
    public function __construct(
        public string $gatewayName,
        public string $reason,
        public int|string $errorCode = 0,
    ) {
    }
}
