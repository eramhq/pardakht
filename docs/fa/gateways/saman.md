<div dir="rtl">

# سامان (سپ)

> درگاه پرداخت الکترونیک سامان. مبتنی بر SOAP، بدون نیاز به تسویه.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\Saman\SamanConfig;

$config = new SamanConfig(
    merchantId: 'شناسه-مرچنت-شما',
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `merchantId` | `string` | بله | شناسه مرچنت از بانک سامان |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('saman', $config);

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

## تسویه

نیاز نیست. پرداخت‌ها به‌طور خودکار تسویه می‌شوند.

## نکات

- مبتنی بر SOAP — نیاز به `ext-soap`
- از ریدایرکت فرم POST استفاده می‌کند (نه GET)
- مبلغ به ریال ارسال می‌شود

</div>
