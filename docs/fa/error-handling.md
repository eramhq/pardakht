<div dir="rtl">

# مدیریت خطا

## سلسله‌مراتب استثناها

<div dir="ltr">

```
RuntimeException
└── PardakhtException (abstract)
    ├── InvalidAmountException
    ├── ConnectionException
    └── GatewayException
        └── VerificationException
```

</div>

تمام استثناهای پرداخت از `PardakhtException` ارث‌بری دارند، بنابراین در صورت نیاز می‌توانید همه را با یک بلاک catch بگیرید.

## انواع استثنا

### PardakhtException

کلاس پایه انتزاعی برای تمام استثناهای کتابخانه. از `\RuntimeException` ارث‌بری دارد.

<div dir="ltr">

```php
use Eram\Pardakht\Exception\PardakhtException;

try {
    $gateway->purchase($request);
} catch (PardakhtException $e) {
    // هر خطای پرداخت را می‌گیرد
}
```

</div>

### InvalidAmountException

هنگام ارائه مبلغ نامعتبر (مثلاً مبالغ منفی) پرتاب می‌شود.

<div dir="ltr">

```php
use Eram\Pardakht\Exception\InvalidAmountException;

Amount::fromRials(-100); // InvalidAmountException پرتاب می‌کند
```

</div>

### ConnectionException

هنگام خطاهای سطح انتقال پرتاب می‌شود: DNS resolution، تایم‌اوت TCP، خطاهای TLS و غیره. این یعنی درگاه در دسترس نبود — پرداخت هرگز انجام نشد.

<div dir="ltr">

```php
use Eram\Pardakht\Exception\ConnectionException;

try {
    $gateway->purchase($request);
} catch (ConnectionException $e) {
    // امن برای تلاش مجدد — درگاه هرگز درخواست را دریافت نکرد
}
```

</div>

### GatewayException

هنگامی که درگاه با خطا پاسخ می‌دهد پرتاب می‌شود. نام درگاه و کد خطای مخصوص درگاه را حمل می‌کند.

<div dir="ltr">

```php
use Eram\Pardakht\Exception\GatewayException;

try {
    $gateway->purchase($request);
} catch (GatewayException $e) {
    $e->getGatewayName(); // "zarinpal"
    $e->getErrorCode();   // -11 (کد مخصوص درگاه)
    $e->getMessage();     // خطای قابل خواندن
}
```

</div>

### VerificationException

هنگام ناموفق بودن تایید پرداخت پرتاب می‌شود. از `GatewayException` ارث‌بری دارد، بنابراین همان متدهای `getGatewayName()` و `getErrorCode()` را دارد.

دلایل رایج:
- کاربر پرداخت را لغو کرده
- عدم تطابق مبلغ پرداخت
- تلاش تکراری برای تایید
- تایم‌اوت درگاه

<div dir="ltr">

```php
use Eram\Pardakht\Exception\VerificationException;

try {
    $transaction = $gateway->verify();
} catch (VerificationException $e) {
    // پرداخت با موفقیت انجام نشد
}
```

</div>

## ترتیب پیشنهادی catch

استثناها را از خاص‌ترین تا عمومی‌ترین بگیرید:

<div dir="ltr">

```php
try {
    $response = $gateway->purchase($request);
} catch (VerificationException $e) {
    // مدیریت مخصوص تایید
} catch (GatewayException $e) {
    // سایر خطاهای درگاه
} catch (ConnectionException $e) {
    // خطاهای شبکه — امن برای تلاش مجدد
} catch (InvalidAmountException $e) {
    // مبلغ نادرست — کد خود را اصلاح کنید
} catch (PardakhtException $e) {
    // گیرنده عمومی برای هر خطای پرداخت
}
```

</div>

## کدهای خطای درگاه

هر درگاه اینام کد خطای مخصوص خود با پیام‌های قابل خواندن دارد. این‌ها داخلی برای پر کردن `GatewayException` استفاده می‌شوند، اما می‌توانید مستقیماً هم به آن‌ها مراجعه کنید:

| درگاه | کلاس کد خطا |
|---------|-----------------|
| زرین‌پال | `ZarinpalErrorCode` |
| ملت | `MellatErrorCode` |
| سامان | `SamanErrorCode` |
| پارسیان | `ParsianErrorCode` |
| سداد | `SadadErrorCode` |

هر اینام کد خطا متد `message(): string` دارد که توضیح خطای قابل خواندن برمی‌گرداند.

</div>
