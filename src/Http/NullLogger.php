<?php

declare(strict_types=1);

namespace Eram\Pardakht\Http;

final class NullLogger implements Logger
{
    public function debug(string $message, array $context = []): void
    {
    }
}
