<div dir="rtl">

# پی‌آی‌آر (Pay.ir)

> درگاه پرداخت Pay.ir. مبتنی بر REST.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\PayIr\PayIrConfig;

$config = new PayIrConfig(
    apiKey: 'کلید-API-شما',
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `apiKey` | `string` | بله | کلید API پی‌آی‌آر |

## خرید

<div dir="ltr">

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
- مبلغ به ریال ارسال می‌شود

</div>
