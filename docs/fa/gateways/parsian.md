<div dir="rtl">

# پارسیان (پک)

> درگاه تجارت الکترونیک پارسیان. مبتنی بر SOAP با مرحله تسویه اجباری.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\Parsian\ParsianConfig;

$config = new ParsianConfig(
    pin: 'کد-پین-شما',
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `pin` | `string` | بله | کد پین از بانک پارسیان |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('parsian', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
));

// ریدایرکت فرم POST
echo $response->renderAutoSubmitForm();
```

</div>

## تایید

<div dir="ltr">

```php
$transaction = $gateway->verify();
```

</div>

## تسویه (الزامی)

پارسیان پس از تایید نیاز به تسویه دارد. پرداخت‌های تسویه‌نشده به‌طور خودکار برگشت می‌خورند.

<div dir="ltr">

```php
use Eram\Pardakht\Contracts\SupportsSettlement;

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

</div>

## نکات

- مبتنی بر SOAP — نیاز به `ext-soap`
- از ریدایرکت فرم POST استفاده می‌کند (نه GET)
- تسویه اجباری است
- مبلغ به ریال ارسال می‌شود

</div>
