<div dir="rtl">

# مفاهیم اصلی

## جریان پرداخت

هر پرداخت در پرداخت، صرف‌نظر از درگاه، از یک چرخه حیات یکسان پیروی می‌کند:

</div>

<div dir="ltr">

```
Create → Purchase → Redirect → Callback → Verify → [Settle]
```

</div>

<div dir="rtl">

۱. **ساخت** — ساخت نمونه درگاه با `Pardakht::create()`
۲. **خرید** — ارسال `PurchaseRequest` به درگاه؛ دریافت `RedirectResponse`
۳. **ریدایرکت** — انتقال کاربر به صفحه پرداخت درگاه (GET یا فرم POST)
۴. **کالبک** — درگاه کاربر را به `callbackUrl` شما برمی‌گرداند
۵. **تایید** — تایید پرداخت با درگاه؛ دریافت `Transaction`
۶. **تسویه** — (فقط ملت و پارسیان) نهایی‌سازی پرداخت قبل از برگشت خودکار

## انتزاع درگاه

تمام درگاه‌ها اینترفیس `GatewayInterface` را پیاده‌سازی می‌کنند که دقیقا دو متد دارد:

<div dir="ltr">

```php
interface GatewayInterface
{
    public function getName(): string;
    public function purchase(PurchaseRequest $request): RedirectResponse;
    public function verify(?array $callbackData = null): TransactionInterface;
}
```

</div>

یعنی با تغییر یک رشته می‌توانید درگاه را عوض کنید — بقیه کد بدون تغییر باقی می‌ماند.

## قابلیت‌های اختیاری

هر درگاه همه قابلیت‌ها را پشتیبانی نمی‌کند. قابلیت‌های اختیاری به صورت اینترفیس‌های جداگانه تعریف شده‌اند:

- **`SupportsSettlement`** — ملت و پارسیان نیاز به فراخوانی `settle()` بعد از `verify()` دارند. بدون تسویه، پرداخت در ۱۵ تا ۳۰ دقیقه خودکار برگشت می‌خورد.
- **`SupportsRefund`** — درگاه‌هایی که بازگشت وجه برنامه‌نویسی پشتیبانی می‌کنند.

از `instanceof` برای مدیریت استفاده کنید:

<div dir="ltr">

```php
if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

</div>

## مشکل ریال/تومان

سیستم‌های پرداخت ایرانی ریال و تومان را به صورت درهم استفاده می‌کنند. برخی API درگاه‌ها ریال می‌خواهند، برخی تومان. یک اشتباه ۱۰ برابری در هر جهت یعنی کاربر ۱۰ برابر بیشتر یا کمتر پرداخت می‌کند.

پرداخت این مشکل را با شیء `Amount` حل می‌کند:

<div dir="ltr">

```php
$amount = Amount::fromToman(50_000);  // شما با تومان فکر می‌کنید
$amount->inRials();                    // ۵۰۰٬۰۰۰ — درگاه ریال می‌گیرد
$amount->inToman();                    // ۵۰٬۰۰۰ — نمایش تومان
```

</div>

`Amount` همه چیز را به صورت داخلی به ریال ذخیره می‌کند. هر درگاه می‌داند API‌اش کدام واحد را می‌خواهد و تبدیل خودکار انجام می‌دهد. شما هرگز نیازی به ضرب یا تقسیم بر ۱۰ ندارید.

## تغییرناپذیری

تمام value objectها و DTOها در پرداخت تغییرناپذیر هستند:

- `Amount` — عملیات حسابی نمونه جدید برمی‌گرداند
- `Transaction` — متدهای `withStatus()` و `withTrackingCode()` نمونه جدید برمی‌گردانند
- `PurchaseRequest` — یک‌بار در زمان ساخت تنظیم می‌شود
- `RedirectResponse` — یک‌بار در زمان ساخت تنظیم می‌شود

## تزریق وابستگی

سازنده `Pardakht` چهار وابستگی اختیاری می‌پذیرد:

<div dir="ltr">

```php
$pardakht = new Pardakht(
    httpClient: $myHttpClient,          // حمل‌ونقل HTTP سفارشی
    logger: $myLogger,                  // لاگ اشکال‌زدایی
    eventDispatcher: $myDispatcher,     // رویدادهای چرخه حیات
    soapFactory: $mySoapFactory,        // ساخت کلاینت SOAP سفارشی
);
```

</div>

همه پارامترها اختیاری هستند. پیش‌فرض‌ها مستقیما از `ext-curl` و `ext-soap` استفاده می‌کنند — بدون Guzzle، بدون Symfony، بدون وابستگی به فریمورک.

## SOAP در مقابل REST

پرداخت هر دو نوع درگاه بانکی SOAP و درگاه پرداخت REST را پشتیبانی می‌کند. این تفاوت برای کد شما نامرئی است — هر دو `GatewayInterface` را پیاده‌سازی می‌کنند. تنها تفاوت قابل مشاهده در ریدایرکت است:

- **درگاه‌های REST** (زرین‌پال، آی‌دی‌پی، زیبال و ...) یک URL برای ریدایرکت GET ساده برمی‌گردانند.
- **درگاه‌های SOAP** (ملت، سامان، پارسیان) داده‌های فرم POST برمی‌گردانند. از `renderAutoSubmitForm()` برای تولید فرم HTML خودکار استفاده کنید.

<div dir="ltr">

```php
if ($response->isPost()) {
    echo $response->renderAutoSubmitForm();
} else {
    header('Location: ' . $response->getUrl());
}
```

</div>

</div>
