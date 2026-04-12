<div dir="rtl">

# زرین‌پال

> محبوب‌ترین درگاه پرداخت آنلاین ایران. مبتنی بر REST با پشتیبانی سندباکس.

## پیکربندی

<div dir="ltr">

```php
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;

$config = new ZarinpalConfig(
    merchantId: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    sandbox: false, // برای تست true کنید
);
```

</div>

| پارامتر | نوع | الزامی | پیش‌فرض | توضیحات |
|-----------|------|----------|---------|-------------|
| `merchantId` | `string` | بله | — | شناسه مرچنت زرین‌پال |
| `sandbox` | `bool` | خیر | `false` | استفاده از محیط سندباکس |

## خرید

<div dir="ltr">

```php
$gateway = $pardakht->create('zarinpal', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'اشتراک ویژه',
    mobile: '09123456789',
    email: 'user@example.com',
));

// ریدایرکت GET
header('Location: ' . $response->getUrl());
```

</div>

## تایید

<div dir="ltr">

```php
$transaction = $gateway->verify();

$transaction->getReferenceId();  // Authority
$transaction->getTrackingCode(); // RefID
$transaction->getCardNumber();   // شماره کارت ماسک‌شده
```

</div>

## سندباکس

`sandbox: true` را تنظیم کنید تا از محیط تست زرین‌پال استفاده کنید. پول واقعی کسر نمی‌شود.

<div dir="ltr">

```php
$config = new ZarinpalConfig(
    merchantId: 'test',
    sandbox: true,
);
```

</div>

## تسویه

نیاز نیست. پرداخت‌ها به‌طور خودکار تسویه می‌شوند.

## نکات

- مبلغ به ریال ارسال می‌شود (از `Amount` به‌طور خودکار تبدیل می‌شود)
- موبایل و ایمیل اختیاری هستند اما تجربه کاربری صفحه پرداخت را بهبود می‌دهند
- فیلد `description` در صفحه پرداخت به کاربر نمایش داده می‌شود

</div>
