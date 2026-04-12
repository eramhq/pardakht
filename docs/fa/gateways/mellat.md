<div dir="rtl">

# ملت (به‌پرداخت)

> درگاه پرداخت بانک ملت. مبتنی بر SOAP با مرحله تسویه اجباری.

## پیکربندی

```php
use Eram\Pardakht\Gateway\Mellat\MellatConfig;

$config = new MellatConfig(
    terminalId: 123456,
    username: 'نام-کاربری-شما',
    password: 'رمز-عبور-شما',
);
```

| پارامتر | نوع | الزامی | توضیحات |
|-----------|------|----------|-------------|
| `terminalId` | `int` | بله | شناسه ترمینال از به‌پرداخت |
| `username` | `string` | بله | نام کاربری وب‌سرویس |
| `password` | `string` | بله | رمز عبور وب‌سرویس |

## خرید

```php
$gateway = $pardakht->create('mellat', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
));

// فرم POST — باید از ارسال خودکار استفاده شود
echo $response->renderAutoSubmitForm();
```

## تایید

```php
$transaction = $gateway->verify();
```

## تسویه (الزامی)

ملت پس از تایید نیاز به تسویه دارد. اگر این مرحله را رد کنید، پرداخت پس از حدود ۱۵ تا ۳۰ دقیقه به‌طور خودکار برگشت می‌خورد.

```php
use Eram\Pardakht\Contracts\SupportsSettlement;

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## نکات

- مبتنی بر SOAP — نیاز به `ext-soap`
- از ریدایرکت فرم POST استفاده می‌کند (نه GET)
- تسویه اجباری است — رد کردن آن پرداخت را برگشت می‌زند
- مبلغ به ریال ارسال می‌شود

</div>
