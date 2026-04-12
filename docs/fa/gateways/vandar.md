<div dir="rtl">

# وندار

> درگاه پرداخت Vandar. مبتنی بر REST.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\Vandar\VandarConfig;

$config = new VandarConfig(
    apiKey: 'کلید-API-شما',
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `apiKey` | `string` | بله | کلید API وندار |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('vandar', $config);

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
