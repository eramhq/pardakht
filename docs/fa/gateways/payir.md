<div dir="rtl">

# پی‌آی‌آر (Pay.ir)

> درگاه پرداخت Pay.ir. مبتنی بر REST.

## پیکربندی

```php
use Eram\Pardakht\Gateway\PayIr\PayIrConfig;

$config = new PayIrConfig(
    apiKey: 'کلید-API-شما',
);
```

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `apiKey` | `string` | بله | کلید API پی‌آی‌آر |

## خرید

```php
$gateway = $pardakht->create('payir', $config);

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
