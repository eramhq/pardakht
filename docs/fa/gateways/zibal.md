<div dir="rtl">

# زیبال

> درگاه پرداخت ایرانی. مبتنی بر REST.

## پیکربندی

```php
use Eram\Pardakht\Gateway\Zibal\ZibalConfig;

$config = new ZibalConfig(
    merchant: 'کد-مرچنت-شما',
);
```

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `merchant` | `string` | بله | کد مرچنت زیبال |

## خرید

```php
$gateway = $pardakht->create('zibal', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'پرداخت سفارش',
    mobile: '09123456789',
));

header('Location: ' . $response->getUrl());
```

## تایید

```php
$transaction = $gateway->verify();
```

## تسویه

نیاز نیست. پرداخت‌ها به‌طور خودکار تسویه می‌شوند.

## نکات

- مبتنی بر REST
- مبلغ به ریال ارسال می‌شود

</div>
