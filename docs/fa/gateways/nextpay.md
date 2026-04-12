<div dir="rtl">

# نکست‌پی

> درگاه پرداخت NextPay. مبتنی بر REST.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\NextPay\NextPayConfig;

$config = new NextPayConfig(
    apiKey: 'کلید-API-شما',
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `apiKey` | `string` | بله | کلید API نکست‌پی |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('nextpay', $config);

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
- مبلغ به ریال ارسال می‌شود

</div>
