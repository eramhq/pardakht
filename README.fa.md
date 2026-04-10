<div dir="rtl">

# پرداخت

کتابخانه PHP برای اتصال به درگاه‌های پرداخت ایرانی. ساده، تایپ‌سیف و مستقل از فریمورک.

[**English Documentation**](README.md)

## نصب

```bash
composer require eram/pardakht
```

## شروع سریع

```php
use EramDev\Pardakht\Pardakht;
use EramDev\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Money\Amount;

// ساخت درگاه (Guzzle به صورت خودکار شناسایی می‌شود)
$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('کد-مرچنت-شما'));

// ایجاد تراکنش
$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://yoursite.com/callback',
    orderId: 'ORDER-123',
    description: 'پرداخت تستی',
));

// انتقال کاربر به درگاه
header('Location: ' . $response->getUrl());

// تایید پرداخت (در صفحه بازگشت — به صورت خودکار GET/POST تشخیص داده می‌شود)
$transaction = $gateway->verify();

echo $transaction->getTrackingCode();  // کد رهگیری
echo $transaction->getAmount()->inToman();  // مبلغ به تومان
```

## درگاه‌های بانکی (ملت، پارسیان)

درگاه‌های بانکی نیاز به مرحله تسویه (settle) دارند:

```php
use EramDev\Pardakht\Contracts\SupportsSettlement;
use EramDev\Pardakht\Gateway\Mellat\MellatConfig;

$gateway = $pardakht->create('mellat', new MellatConfig(
    terminalId: 12345, username: 'user', password: 'pass',
));

$transaction = $gateway->verify();

// تسویه الزامی است!
if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## درگاه‌های پشتیبانی شده

| درگاه | نوع | تسویه |
|-------|-----|-------|
| ملت (به‌پرداخت) | SOAP | بله |
| سامان (سپ) | SOAP | خیر |
| پارسیان (پک) | SOAP | بله |
| سداد (بانک ملی) | REST | خیر |
| پاسارگاد | REST | خیر |
| زرین‌پال | REST | خیر |
| آی‌دی‌پی | REST | خیر |
| زیبال | REST | خیر |
| پی‌آی‌آر | REST | خیر |
| نکست‌پی | REST | خیر |
| وندار | REST | خیر |
| سیزپی | REST | خیر |

## مبلغ (ریال/تومان)

شیء `Amount` از اشتباه ریال و تومان جلوگیری می‌کند:

```php
$amount = Amount::fromToman(50_000);
$amount->inRials();  // ۵۰۰۰۰۰
$amount->inToman();  // ۵۰۰۰۰
```

## ابزارهای بانکی

```php
use EramDev\Pardakht\Banking\CardNumber;
use EramDev\Pardakht\Banking\Sheba;

// اعتبارسنجی شماره کارت
CardNumber::isValid('6037990000000006'); // true

// اعتبارسنجی شبا
Sheba::isValid('IR062960000000100324200001'); // true

// تشخیص بانک
$card = new CardNumber('6037990000000006');
$card->bankName(); // بانک ملی ایران
```

## مجوز

MIT - ساخته شده توسط [ارم.دو](https://eram.dev)

</div>
