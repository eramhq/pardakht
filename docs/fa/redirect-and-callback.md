<div dir="rtl">

# ریدایرکت و کال‌بک

پس از فراخوانی موفق `purchase()`، باید کاربر را به صفحه پرداخت درگاه ریدایرکت کنید و کال‌بک بازگشت را مدیریت کنید.

## RedirectResponse

متد `purchase()` یک `RedirectResponse` با دو نوع ریدایرکت ممکن برمی‌گرداند:

### ریدایرکت GET (درگاه‌های REST)

درگاه‌های REST (زرین‌پال، آیدی‌پی، زیبال و غیره) یک URL برای ریدایرکت ساده HTTP ارائه می‌دهند:

<div dir="ltr">

```php
$response = $gateway->purchase($request);

header('Location: ' . $response->getUrl());
exit;
```

</div>

### فرم POST (درگاه‌های SOAP)

درگاه‌های بانکی SOAP (ملت، سامان، پارسیان) نیاز به ارسال فرم POST با فیلدهای مخفی به صفحه پرداخت بانک دارند:

<div dir="ltr">

```php
$response = $gateway->purchase($request);

echo $response->renderAutoSubmitForm();
```

</div>

HTML تولید شده شامل فرمی با فیلدهای مخفی و یک قطعه جاوااسکریپت است که آن را خودکار ارسال می‌کند. یک دکمه `<noscript>` برای کاربران بدون جاوااسکریپت وجود دارد.

### مدیریت هر دو نوع

<div dir="ltr">

```php
$response = $gateway->purchase($request);

if ($response->isPost()) {
    echo $response->renderAutoSubmitForm('در حال انتقال به بانک...');
} else {
    header('Location: ' . $response->getUrl());
    exit;
}
```

</div>

## API مربوط به RedirectResponse

<div dir="ltr">

```php
$response->getUrl();         // آدرس صفحه پرداخت درگاه
$response->getMethod();      // "GET" یا "POST"
$response->getReferenceId(); // شناسه مرجع درگاه (Authority، RefId و غیره)
$response->getFormData();    // فیلدهای POST (خالی برای GET)
$response->isPost();         // true برای درگاه‌های SOAP

$response->renderAutoSubmitForm(
    string $submitText = 'در حال انتقال به درگاه...'
): string;
```

</div>

### شناسه مرجع

`referenceId` بازگردانده شده توسط `purchase()` شناسه درگاه برای این تلاش پرداخت است. آن را در دیتابیس ذخیره کنید — برای تطبیق کال‌بک به آن نیاز خواهید داشت.

## مدیریت کال‌بک

پس از تکمیل (یا لغو) پرداخت توسط کاربر، درگاه او را به `callbackUrl` شما برمی‌گرداند.

### تشخیص خودکار

به‌طور پیش‌فرض، `verify()` داده‌های کال‌بک را از `$_POST` یا `$_GET` به‌طور خودکار می‌خواند:

<div dir="ltr">

```php
$transaction = $gateway->verify();
```

</div>

### داده صریح

در فریم‌ورک‌هایی که سوپرگلوبال‌ها مستقیماً استفاده نمی‌شوند، داده‌های کال‌بک را صریحاً ارسال کنید:

<div dir="ltr">

```php
// لاراول
$transaction = $gateway->verify($request->all());

// سیمفونی
$transaction = $gateway->verify($request->query->all());
```

</div>

### نتیجه تراکنش

پس از تایید موفق:

<div dir="ltr">

```php
$transaction->getId();           // آبجکت مقداری TransactionId
$transaction->getGatewayName();  // "zarinpal"
$transaction->getAmount();       // آبجکت مقداری Amount
$transaction->getStatus();       // TransactionStatus::Verified
$transaction->getReferenceId();  // شناسه مرجع درگاه
$transaction->getTrackingCode(); // کد پیگیری (یا null)
$transaction->getCardNumber();   // شماره کارت پرداخت‌کننده (یا null)
$transaction->getExtra();        // داده‌های اضافی مخصوص درگاه
```

</div>

## مثال جریان کامل

<div dir="ltr">

```php
// === صفحه خرید ===
$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/payment/callback',
    orderId: 'ORDER-123',
));

// ذخیره در دیتابیس
save_payment($response->getReferenceId(), 'ORDER-123', 'pending');

// ریدایرکت
header('Location: ' . $response->getUrl());
exit;

// === مدیریت کال‌بک ===
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));

try {
    $transaction = $gateway->verify();
    update_payment($transaction->getReferenceId(), 'verified');
    show_success_page($transaction->getTrackingCode());
} catch (VerificationException $e) {
    update_payment_failed($e->getErrorCode());
    show_failure_page();
}
```

</div>

</div>
