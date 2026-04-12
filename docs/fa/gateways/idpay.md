<div dir="rtl">

# آیدی‌پی

> درگاه پرداخت محبوب ایرانی. مبتنی بر REST با پشتیبانی سندباکس.

## پیکربندی

```php
use Eram\Pardakht\Gateway\IDPay\IDPayConfig;

$config = new IDPayConfig(
    apiKey: 'کلید-API-شما',
    sandbox: false, // برای تست true کنید
);
```

| پارامتر | نوع | الزامی | پیش‌فرض | توضیحات |
|-----------|------|----------|---------|-------------|
| `apiKey` | `string` | بله | — | کلید API آیدی‌پی |
| `sandbox` | `bool` | خیر | `false` | استفاده از محیط سندباکس |

## خرید

```php
$gateway = $pardakht->create('idpay', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'پرداخت سفارش',
    mobile: '09123456789',
    email: 'user@example.com',
));

header('Location: ' . $response->getUrl());
```

## تایید

```php
$transaction = $gateway->verify();
```

## سندباکس

```php
$config = new IDPayConfig(
    apiKey: 'test',
    sandbox: true,
);
```

## تسویه

نیاز نیست. پرداخت‌ها به‌طور خودکار تسویه می‌شوند.

## نکات

- مبتنی بر REST
- مبلغ به ریال ارسال می‌شود
- از حالت سندباکس برای تست پشتیبانی می‌کند

</div>
