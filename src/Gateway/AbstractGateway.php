<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway;

use EramDev\Pardakht\Contracts\GatewayInterface;
use EramDev\Pardakht\Contracts\TransactionInterface;
use EramDev\Pardakht\Exception\ConnectionException;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Http\RedirectResponse;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base class for REST-based payment gateways.
 */
abstract class AbstractGateway implements GatewayInterface
{
    use GatewayHelperTrait;

    protected ClientInterface $httpClient;
    protected RequestFactoryInterface $requestFactory;
    protected StreamFactoryInterface $streamFactory;
    protected LoggerInterface $logger;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
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
     * Auto-detect callback data from the current request.
     *
     * @param array<string, mixed>|null $callbackData
     * @return array<string, mixed>
     */
    protected function resolveCallbackData(?array $callbackData): array
    {
        if ($callbackData !== null) {
            return $callbackData;
        }

        // Auto-detect from superglobals
        if (!empty($_POST)) {
            return $_POST;
        }

        return $_GET;
    }

    /**
     * Send a JSON POST request to the gateway API.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function postJson(string $url, array $data, array $headers = []): ResponseInterface
    {
        $body = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $this->logger->debug('Pardakht: sending request', [
            'gateway' => $this->getName(),
            'url' => $url,
        ]);

        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException(
                \sprintf('HTTP request to %s failed: %s', $url, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * Decode a JSON response body.
     *
     * @return array<string, mixed>
     */
    protected function decodeResponse(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $data */
        $data = \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
