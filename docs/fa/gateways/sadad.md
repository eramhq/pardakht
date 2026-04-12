<div dir="rtl">

# سداد (بانک ملی)

> درگاه پرداخت بانک ملی ایران. مبتنی بر REST.

## پیکربندی

```php
use Eram\Pardakht\Gateway\Sadad\SadadConfig;

$config = new SadadConfig(
    merchantId: 'شناسه-مرچنت-شما',
    terminalId: 'شناسه-ترمینال-شما',
    terminalKey: 'کلید-ترمینال-شما',
);
```

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `merchantId` | `string` | بله | شناسه مرچنت |
| `terminalId` | `string` | بله | شناسه ترمینال |
| `terminalKey` | `string` | بله | کلید رمزنگاری ترمینال |

## خرید

```php
$gateway = $pardakht->create('sadad', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
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
- از `ext-openssl` برای امضای درخواست‌ها استفاده می‌کند
- مبلغ به ریال ارسال می‌شود

</div>
