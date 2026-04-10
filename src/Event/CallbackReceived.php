<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Event;

final class CallbackReceived
{
    /**
     * @param array<string, mixed> $callbackData
     */
    public function __construct(
        public string $gatewayName,
        public array $callbackData,
    ) {
    }
}
