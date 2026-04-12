<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway;

use Eram\Pardakht\Contracts\GatewayInterface;
use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Exception\ConnectionException;
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\Logger;
use Eram\Pardakht\Http\NullLogger;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\RedirectResponse;

/**
 * Base class for REST-based payment gateways.
 */
abstract class AbstractGateway implements GatewayInterface
{
    use GatewayHelperTrait;

    protected HttpClient $httpClient;
    protected Logger $logger;

    public function __construct(
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function getName(): string;

    abstract public function purchase(PurchaseRequest $request): RedirectResponse;

    /**
     * Verify a payment. If no callback data is provided, auto-detects from $_POST or $_GET.
     *
     * @param array<string, mixed>|null $callbackData
     */
    abstract public function verify(?array $callbackData = null): TransactionInterface;

    /**
     * POST JSON to the gateway API and return the decoded response body.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException On transport, encode, or decode failure.
     */
    protected function postJson(string $url, array $data, array $headers = []): array
    {
        try {
            $jsonBody = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new ConnectionException("Failed to encode request body: {$e->getMessage()}", 0, $e);
        }

        $this->logger->debug('Pardakht: sending request', [
            'gateway' => $this->getName(),
            'url' => $url,
        ]);

        $response = $this->httpClient->postJson($url, $jsonBody, $headers);

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = \json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConnectionException(
                \sprintf('Failed to decode response from %s: %s', $url, $e->getMessage()),
                0,
                $e,
            );
        }

        return $decoded;
    }
}
