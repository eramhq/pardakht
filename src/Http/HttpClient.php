<?php

declare(strict_types=1);

namespace Eram\Pardakht\Http;

use Eram\Pardakht\Exception\ConnectionException;

/**
 * Minimal HTTP client contract for payment gateways.
 *
 * Implement this interface to plug in a custom HTTP client (e.g., adapters
 * wrapping Symfony HttpClient, Guzzle, or a mock for testing).
 */
interface HttpClient
{
    /**
     * Send a JSON POST request to the given URL.
     *
     * @param array<string, string> $headers
     * @throws ConnectionException On transport-level failures (DNS, timeout, TLS, etc.).
     */
    public function postJson(string $url, string $body, array $headers = []): HttpResponse;
}
