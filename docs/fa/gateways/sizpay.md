<div dir="rtl">

# سیزپی

> درگاه پرداخت Sizpay. مبتنی بر REST.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\Sizpay\SizpayConfig;

$config = new SizpayConfig(
    merchantId: 'شناسه-مرچنت-شما',
    terminalId: 'شناسه-ترمینال-شما',
    username: 'نام-کاربری-شما',
    password: 'رمز-عبور-شما',
    signKey: 'کلید-امضای-شما',
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `merchantId` | `string` | بله | شناسه مرچنت |
| `terminalId` | `string` | بله | شناسه ترمینال |
| `username` | `string` | بله | نام کاربری وب‌سرویس |
| `password` | `string` | بله | رمز عبور وب‌سرویس |
| `signKey` | `string` | بله | کلید امضای درخواست |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('sizpay', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
));

header('Location: ' . $response->getUrl());
```

</div>

## تایید

<div dir="ltr">

```php
$transaction = $gateway->verify();
```

</div>

## تسویه

نیاز نیست. پرداخت‌ها به‌طور خودکار تسویه می‌شوند.

## نکات

- مبتنی بر REST
- نیاز به ۵ پارامتر پیکربندی
- مبلغ به ریال ارسال می‌شود

</div>
