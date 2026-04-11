<div dir="rtl">

# پرداخت

کتابخانه PHP برای اتصال به درگاه‌های پرداخت ایرانی. ساده، تایپ‌سیف و مستقل از فریمورک.

[**English Documentation**](README.md)

## نصب

```bash
composer require eram/pardakht
```

## نحوه استفاده

روند کار همیشه یکسان است: **ساخت درگاه → شروع پرداخت → انتقال کاربر → تایید**. درگاه‌های بانکی (ملت، پارسیان) یک مرحله **تسویه** اضافی بعد از تایید دارند.

```php
use Eram\Pardakht\Pardakht;
use Eram\Pardakht\Contracts\SupportsSettlement;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Money\Amount;

$pardakht = new Pardakht();

// ۱. ساخت درگاه — برای هر درگاه پشتیبانی‌شده، فقط alias و config را عوض کنید
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('کد-مرچنت-شما'));

// ۲. شروع تراکنش
$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://yoursite.com/callback',
    orderId: 'ORDER-123',
    description: 'پرداخت تستی',
    mobile: '09121234567',
    email: 'user@example.com',
));

// ۳. انتقال کاربر به درگاه
header('Location: ' . $response->getUrl());
// برخی درگاه‌ها (ملت، سامان، سیزپی) نیاز به فرم POST دارند:
// echo $response->renderAutoSubmitForm();

// ۴. در صفحه بازگشت — verify() به صورت خودکار $_GET و $_POST را تشخیص می‌دهد
$transaction = $gateway->verify();

// ۵. درگاه‌های بانکی (ملت، پارسیان) نیاز به تسویه دارند، در غیر این صورت پول برگشت می‌خورد
if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}

// دسترسی به نتیجه تراکنش
$transaction->getId()->value();        // 'ORDER-123'
$transaction->getStatus();             // TransactionStatus::Settled
$transaction->getReferenceId();        // شناسه مرجع درگاه
$transaction->getTrackingCode();       // کد رهگیری برای نمایش به کاربر
$transaction->getCardNumber();         // '610433******0003'
$transaction->getAmount()->inToman();  // 50000
```

همین. همه درگاه‌ها از همین پنج مرحله پیروی می‌کنند — فقط کلاس config تغییر می‌کند.

## درگاه‌های پشتیبانی شده

| Alias | درگاه | Config | نیاز به تسویه |
|-------|------|--------|--------------|
| `mellat` | ملت (به‌پرداخت) | `MellatConfig` | بله |
| `saman` | سامان (سپ) | `SamanConfig` | خیر |
| `parsian` | پارسیان (پک) | `ParsianConfig` | بله |
| `sadad` | سداد (بانک ملی) | `SadadConfig` | خیر |
| `pasargad` | پاسارگاد | `PasargadConfig` | خیر |
| `zarinpal` | زرین‌پال | `ZarinpalConfig` | خیر |
| `idpay` | آی‌دی‌پی | `IDPayConfig` | خیر |
| `zibal` | زیبال | `ZibalConfig` | خیر |
| `payir` | پی‌آی‌آر | `PayIrConfig` | خیر |
| `nextpay` | نکست‌پی | `NextPayConfig` | خیر |
| `vandar` | وندار | `VandarConfig` | خیر |
| `sizpay` | سیزپی | `SizpayConfig` | خیر |

## مبلغ (ریال/تومان)

شیء `Amount` از رایج‌ترین باگ پرداخت ایرانی جلوگیری می‌کند: قاطی کردن ریال و تومان.

```php
use Eram\Pardakht\Money\Amount;

$amount = Amount::fromToman(50_000);
$amount->inRials();   // 500000
$amount->inToman();   // 50000

$sum = $amount->add(Amount::fromToman(10_000));
$sum->inToman();      // 60000

$amount->greaterThan(Amount::fromToman(20_000));  // true
$amount->equals(Amount::fromRials(500_000));      // true
```

## ابزارهای بانکی

```php
use Eram\Pardakht\Banking\CardNumber;
use Eram\Pardakht\Banking\Sheba;

// شماره کارت — اعتبارسنجی Luhn و تشخیص بانک
$card = new CardNumber('6037-9900-0000-0006');
$card->number();     // '6037990000000006'
$card->masked();     // '603799******0006'
$card->formatted();  // '6037-9900-0000-0006'
$card->bankName();   // 'بانک ملی ایران'
CardNumber::isValid('6037990000000006'); // true

// شبا (IBAN ایرانی) — اعتبارسنجی چک‌سام و تشخیص بانک
$sheba = new Sheba('IR062960000000100324200001');
$sheba->value();     // 'IR062960000000100324200001'
$sheba->formatted(); // 'IR06 2960 0000 0010 0324 2000 01'
$sheba->bankName();  // تشخیص خودکار از کد شبا
Sheba::isValid('IR062960000000100324200001'); // true
```

## رویدادها

با ارسال هر PSR-14 event dispatcher، رویدادهای چرخه حیات پرداخت را دریافت کنید: `PurchaseInitiated`, `CallbackReceived`, `PaymentVerified`, `PaymentSettled`, `PaymentFailed`.

```php
$pardakht = new Pardakht(eventDispatcher: $yourDispatcher);
```

## مجوز

MIT - ساخته شده توسط [ارم.دو](https://eram.dev)

</div>
