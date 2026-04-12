<div dir="rtl">

# HTTP و لاگ‌گیری

پرداخت با کلاینت‌های HTTP و SOAP داخلی ارائه می‌شود، اما هر لایه انتقال قابل تعویض است.

## اینترفیس HttpClient

```php
namespace Eram\Pardakht\Http;

interface HttpClient
{
    public function postJson(string $url, string $body, array $headers = []): HttpResponse;
}
```

قرارداد تک‌متدی، ساخت آداپتور برای هر کتابخانه HTTP را بسیار آسان می‌کند.

### پیش‌فرض: CurlHttpClient

کلاینت پیش‌فرض مستقیماً از `ext-curl` استفاده می‌کند. نیازی به پیکربندی نیست:

```php
$pardakht = new Pardakht(); // به‌طور پیش‌فرض از CurlHttpClient استفاده می‌کند
```

### مثال آداپتور سفارشی

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
    public array $headers; // کلیدها با حروف کوچک

    public function header(string $name): ?string;
    public function isSuccessful(): bool; // وضعیت 2xx
}
```

## اینترفیس Logger

```php
namespace Eram\Pardakht\Http;

interface Logger
{
    public function debug(string $message, array $context = []): void;
}
```

فقط سطح `debug` استفاده می‌شود. درگاه‌ها هنگام ارسال درخواست، URL و نام درگاه را لاگ می‌کنند که برای ردیابی فراخوانی‌های HTTP و SOAP در محیط توسعه مفید است.

### پیش‌فرض: NullLogger

لاگر پیش‌فرض تمام پیام‌ها را دور می‌ریزد:

```php
$pardakht = new Pardakht(); // به‌طور پیش‌فرض از NullLogger استفاده می‌کند
```

### مثال آداپتور PSR-3

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

درگاه‌های مبتنی بر SOAP (ملت، سامان، پارسیان) به نمونه `\SoapClient` نیاز دارند. `SoapClientFactory` داخلی آبجکت‌های استاندارد `\SoapClient` می‌سازد. می‌توانید آن را جایگزین کنید تا تنظیمات SOAP را شخصی‌سازی کنید یا یک ماک تزریق کنید:

```php
use Eram\Pardakht\Http\SoapClientFactory;

$pardakht = new Pardakht(soapFactory: new SoapClientFactory());
```

## تزریق وابستگی‌ها

```php
$pardakht = new Pardakht(
    httpClient: new GuzzleHttpClient(),
    logger: new PsrLoggerAdapter($psrLogger),
    eventDispatcher: new MyEventDispatcher(),
    soapFactory: new SoapClientFactory(),
);
```

تمام پارامترها اختیاری هستند و به هر ترکیبی قابل استفاده‌اند.

</div>
