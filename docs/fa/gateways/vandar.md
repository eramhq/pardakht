<div dir="rtl">

# وندار

> درگاه پرداخت Vandar. مبتنی بر REST.

## پیکربندی

```php
use Eram\Pardakht\Gateway\Vandar\VandarConfig;

$config = new VandarConfig(
    apiKey: 'کلید-API-شما',
);
```

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `apiKey` | `string` | بله | کلید API وندار |

## خرید

```php
$gateway = $pardakht->create('vandar', $config);

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
- مبلغ به ریال ارسال می‌شود

</div>
