<div dir="rtl">

# کتاب آشپزی

دستورالعمل‌های عملی برای سناریوهای رایج پرداخت.

## پرداخت ساده با مدیریت خطا

```php
use Eram\Pardakht\Pardakht;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\ConnectionException;

$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));

try {
    $response = $gateway->purchase(new PurchaseRequest(
        amount: Amount::fromToman(25_000),
        callbackUrl: 'https://example.com/payment/callback',
        orderId: 'INV-2024-001',
        description: 'سفارش شماره ۰۰۱',
        mobile: '09123456789',
    ));

    // ذخیره referenceId در دیتابیس
    header('Location: ' . $response->getUrl());
    exit;
} catch (ConnectionException $e) {
    // خطای شبکه — تلاش مجدد یا نمایش صفحه تعمیرات
    log_error('درگاه در دسترس نیست: ' . $e->getMessage());
} catch (GatewayException $e) {
    // درگاه درخواست را رد کرد
    log_error(sprintf(
        'خطای درگاه %s [%s]: %s',
        $e->getGatewayName(),
        $e->getErrorCode(),
        $e->getMessage(),
    ));
}
```

## هندلر کالبک تایید

```php
use Eram\Pardakht\Contracts\SupportsSettlement;
use Eram\Pardakht\Exception\VerificationException;

// ساخت مجدد همان نمونه درگاه
$gateway = $pardakht->create('mellat', new MellatConfig(
    terminalId: 123456,
    username: 'user',
    password: 'pass',
));

try {
    $transaction = $gateway->verify(); // تشخیص خودکار $_POST/$_GET

    // مرحله تسویه (فقط ملت و پارسیان)
    if ($gateway instanceof SupportsSettlement) {
        $transaction = $gateway->settle($transaction);
    }

    // پرداخت موفق — به‌روزرسانی سفارش
    update_order($transaction->getReferenceId(), [
        'status' => 'paid',
        'tracking_code' => $transaction->getTrackingCode(),
        'card_number' => $transaction->getCardNumber(),
        'amount_rials' => $transaction->getAmount()->inRials(),
    ]);
} catch (VerificationException $e) {
    // پرداخت ناموفق یا لغو شده
    mark_order_failed($e->getGatewayName(), $e->getErrorCode());
}
```

## تعویض درگاه از طریق تنظیمات

```php
// ذخیره تنظیمات درگاه در پیکربندی اپلیکیشن
$gatewayConfigs = [
    'zarinpal' => new ZarinpalConfig(merchantId: 'xxx'),
    'idpay' => new IDPayConfig(apiKey: 'yyy'),
    'mellat' => new MellatConfig(terminalId: 123, username: 'u', password: 'p'),
];

// تعویض درگاه با یک تغییر تنظیمات
$activeGateway = 'zarinpal'; // این را تغییر دهید
$gateway = $pardakht->create($activeGateway, $gatewayConfigs[$activeGateway]);
```

## مدیریت هر دو نوع ریدایرکت

```php
$response = $gateway->purchase($request);

if ($response->isPost()) {
    // درگاه‌های SOAP (ملت، سامان، پارسیان) نیاز به فرم خودکار دارند
    echo $response->renderAutoSubmitForm('در حال انتقال به بانک...');
} else {
    // درگاه‌های REST — ریدایرکت ساده
    header('Location: ' . $response->getUrl());
    exit;
}
```

## کلاینت HTTP سفارشی (مثال Guzzle)

```php
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Exception\ConnectionException;
use GuzzleHttp\Client;

class GuzzleHttpClient implements HttpClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
    }

    public function postJson(string $url, string $body, array $headers = []): HttpResponse
    {
        try {
            $response = $this->client->post($url, [
                'body' => $body,
                'headers' => array_merge(
                    ['Content-Type' => 'application/json'],
                    $headers,
                ),
            ]);

            return new HttpResponse(
                statusCode: $response->getStatusCode(),
                body: (string) $response->getBody(),
                headers: array_change_key_case(
                    array_map(fn ($v) => $v[0] ?? '', $response->getHeaders()),
                ),
            );
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }
}

$pardakht = new Pardakht(httpClient: new GuzzleHttpClient());
```

## لاگ کردن تمام درخواست‌ها

```php
use Eram\Pardakht\Http\Logger;

class FileLogger implements Logger
{
    public function debug(string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE),
        );
        file_put_contents('/var/log/pardakht.log', $line, FILE_APPEND);
    }
}

$pardakht = new Pardakht(logger: new FileLogger());
```

## شنونده‌های رویداد

```php
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Event\PurchaseInitiated;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PaymentFailed;

class PaymentEventDispatcher implements EventDispatcher
{
    public function dispatch(object $event): object
    {
        match (true) {
            $event instanceof PurchaseInitiated => $this->onPurchase($event),
            $event instanceof PaymentVerified => $this->onVerified($event),
            $event instanceof PaymentFailed => $this->onFailed($event),
            default => null,
        };

        return $event;
    }

    private function onPurchase(PurchaseInitiated $event): void
    {
        // ثبت تلاش‌های پرداخت
    }

    private function onVerified(PaymentVerified $event): void
    {
        // ارسال پیامک تایید
    }

    private function onFailed(PaymentFailed $event): void
    {
        // هشدار به تیم عملیات
    }
}

$pardakht = new Pardakht(eventDispatcher: new PaymentEventDispatcher());
```

## اعتبارسنجی شماره کارت و شبا

```php
use Eram\Pardakht\Banking\CardNumber;
use Eram\Pardakht\Banking\Sheba;

// اعتبارسنجی سریع
$valid = CardNumber::isValid('6037991234567890'); // true یا false
$valid = Sheba::isValid('IR062960000000100324200001');

// شیء کامل با تشخیص بانک
$card = new CardNumber('6037-9912-3456-7890');
echo $card->bankName();    // "ملی"
echo $card->masked();      // "603799******7890"
echo $card->formatted();   // "6037-9912-3456-7890"

$sheba = new Sheba('IR062960000000100324200001');
echo $sheba->bankName();   // "ملت"
echo $sheba->formatted();  // "IR06 2960 0000 0010 0324 2000 01"
```

</div>
