# HTTP & Logging

Pardakht ships with built-in HTTP and SOAP clients, but every transport layer is pluggable.

## HttpClient Interface

```php
namespace Eram\Pardakht\Http;

interface HttpClient
{
    public function postJson(string $url, string $body, array $headers = []): HttpResponse;
}
```

The single-method contract makes it trivial to implement adapters for any HTTP library.

### Built-in: CurlHttpClient

The default client uses `ext-curl` directly. No configuration needed:

```php
$pardakht = new Pardakht(); // Uses CurlHttpClient by default
```

### Custom Adapter Example

```php
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Exception\ConnectionException;

class SymfonyHttpClientAdapter implements HttpClient
{
    public function __construct(
        private \Symfony\Contracts\HttpClient\HttpClientInterface $client,
    ) {}

    public function postJson(string $url, string $body, array $headers = []): HttpResponse
    {
        try {
            $response = $this->client->request('POST', $url, [
                'body' => $body,
                'headers' => array_merge(
                    ['Content-Type' => 'application/json'],
                    $headers,
                ),
            ]);

            return new HttpResponse(
                statusCode: $response->getStatusCode(),
                body: $response->getContent(false),
                headers: array_change_key_case(
                    array_map(fn ($v) => $v[0] ?? '', $response->getHeaders(false)),
                ),
            );
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }
}
```

## HttpResponse

```php
final class HttpResponse
{
    public int $statusCode;
    public string $body;
    public array $headers; // Keys are lower-cased

    public function header(string $name): ?string;
    public function isSuccessful(): bool; // 2xx status
}
```

## Logger Interface

```php
namespace Eram\Pardakht\Http;

interface Logger
{
    public function debug(string $message, array $context = []): void;
}
```

Only the `debug` level is used. Gateways log the URL and gateway name when sending requests, which is useful for tracing HTTP and SOAP calls in development.

### Built-in: NullLogger

The default logger discards all messages:

```php
$pardakht = new Pardakht(); // Uses NullLogger by default
```

### PSR-3 Adapter Example

```php
use Eram\Pardakht\Http\Logger;
use Psr\Log\LoggerInterface;

class PsrLoggerAdapter implements Logger
{
    public function __construct(private LoggerInterface $logger) {}

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
```

## SoapClientFactory

SOAP-based gateways (Mellat, Saman, Parsian) need a `\SoapClient` instance. The built-in `SoapClientFactory` creates standard `\SoapClient` objects. You can replace it to customize SOAP options or inject a mock:

```php
use Eram\Pardakht\Http\SoapClientFactory;

$pardakht = new Pardakht(soapFactory: new SoapClientFactory());
```

## Injecting Dependencies

```php
$pardakht = new Pardakht(
    httpClient: new GuzzleHttpClient(),
    logger: new PsrLoggerAdapter($psrLogger),
    eventDispatcher: new MyEventDispatcher(),
    soapFactory: new SoapClientFactory(),
);
```

All parameters are optional and can be combined in any way.
