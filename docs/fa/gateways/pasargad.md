<div dir="rtl">

# پاسارگاد

> درگاه پرداخت بانک پاسارگاد. مبتنی بر REST با امضای RSA.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\Pasargad\PasargadConfig;

$config = new PasargadConfig(
    merchantCode: 'کد-مرچنت-شما',
    terminalCode: 'کد-ترمینال-شما',
    privateKey: file_get_contents('/path/to/private-key.pem'),
);
```

</div>

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `merchantCode` | `string` | بله | کد مرچنت از پاسارگاد |
| `terminalCode` | `string` | بله | کد ترمینال |
| `privateKey` | `string` | بله | کلید خصوصی RSA (فرمت PEM) |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('pasargad', $config);

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
- نیاز به کلید خصوصی RSA برای امضای درخواست‌ها (`ext-openssl`)
- مبلغ به ریال ارسال می‌شود

</div>
