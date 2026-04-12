<?php

declare(strict_types=1);

namespace Eram\Pardakht\Http;

/**
 * Minimal logger contract used internally by Pardakht.
 *
 * Only the `debug` level is used — gateways log the URL and gateway name
 * when sending requests so you can trace HTTP and SOAP calls in development.
 */
interface Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;
}
