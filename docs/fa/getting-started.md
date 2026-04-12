<div dir="rtl">

# شروع سریع

## پیش‌نیازها

- PHP نسخه ۸.۱ یا بالاتر
- اکستنشن‌ها: `ext-curl`، `ext-json`، `ext-openssl`، `ext-soap`

## نصب

<div dir="ltr">

```bash
composer require eram/pardakht
```

</div>

پرداخت **هیچ وابستگی Composer** ندارد. فقط به اکستنشن‌های PHP نیاز دارد که در اکثر نصب‌های PHP موجود هستند.

## مثال سریع

یک جریان پرداخت کامل با زرین‌پال:

### ۱. ساخت درگاه

<div dir="ltr">

```php
use Eram\Pardakht\Pardakht;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;

$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig(
    merchantId: 'کد-مرچنت-شما',
));
```

</div>

### ۲. شروع پرداخت

<div dir="ltr">

```php
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Http\PurchaseRequest;

$request = new PurchaseRequest(
    amount: Amount::fromToman(50_000),       // ۵۰٬۰۰۰ تومان
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'اشتراک ویژه',
);

$response = $gateway->purchase($request);
```

</div>

### ۳. انتقال کاربر به درگاه

<div dir="ltr">

```php
// برای درگاه‌های REST (زرین‌پال، آی‌دی‌پی و ...) — ریدایرکت ساده
header('Location: ' . $response->getUrl());
exit;

// برای درگاه‌های SOAP (ملت، سامان، پارسیان) — فرم خودکار
echo $response->renderAutoSubmitForm();
```

</div>

### ۴. مدیریت کالبک

<div dir="ltr">

```php
// در هندلر URL بازگشت:
$transaction = $gateway->verify();

echo $transaction->getReferenceId();    // شناسه مرجع درگاه
echo $transaction->getTrackingCode();   // کد رهگیری برای کاربر
echo $transaction->getCardNumber();     // شماره کارت پرداخت‌کننده
echo $transaction->getAmount()->inToman(); // 50000
```

</div>

### ۵. تسویه (در صورت نیاز)

برخی درگاه‌های بانکی (ملت، پارسیان) نیاز به مرحله تسویه جداگانه بعد از تایید دارند. اگر این مرحله انجام نشود، پرداخت بعد از ۱۵ تا ۳۰ دقیقه به صورت خودکار برگشت می‌خورد.

<div dir="ltr">

```php
use Eram\Pardakht\Contracts\SupportsSettlement;

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

</div>

## درگاه‌های پشتیبانی شده

| Alias | درگاه | پروتکل | نیاز به تسویه |
|-------|-------|---------|---------------|
| `mellat` | ملت (به‌پرداخت) | SOAP | بله |
| `saman` | سامان (سپ) | SOAP | خیر |
| `parsian` | پارسیان (پک) | SOAP | بله |
| `sadad` | سداد (بانک ملی) | REST | خیر |
| `pasargad` | پاسارگاد | REST | خیر |
| `zarinpal` | زرین‌پال | REST | خیر |
| `idpay` | آی‌دی‌پی | REST | خیر |
| `zibal` | زیبال | REST | خیر |
| `payir` | پی‌آی‌آر | REST | خیر |
| `nextpay` | نکست‌پی | REST | خیر |
| `vandar` | وندار | REST | خیر |
| `sizpay` | سیزپی | REST | خیر |

## قدم‌های بعدی

- [مفاهیم اصلی](concepts.md) — درک طراحی کتابخانه
- [کتاب آشپزی](cookbook.md) — دستورالعمل‌های عملی
- [راهنمای درگاه‌ها](README.md#راهنمای-درگاهها) — مستندات هر درگاه

</div>
