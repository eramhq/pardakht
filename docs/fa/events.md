<div dir="rtl">

# رویدادها

پرداخت در نقاط کلیدی جریان پرداخت رویدادهای چرخه حیات ارسال می‌کند. رویدادها اختیاری هستند — اگر `EventDispatcher` ارائه نشود، رویدادها بی‌صدا نادیده گرفته می‌شوند.

## راه‌اندازی

اینترفیس `EventDispatcher` را پیاده‌سازی کنید و به `Pardakht` ارسال کنید:

```php
use Eram\Pardakht\Http\EventDispatcher;

class MyEventDispatcher implements EventDispatcher
{
    public function dispatch(object $event): object
    {
        // رویداد را مدیریت یا ارسال کنید
        return $event;
    }
}

$pardakht = new Pardakht(eventDispatcher: new MyEventDispatcher());
```

## انواع رویداد

### PurchaseInitiated

هنگام فراخوانی `purchase()` و قبل از ارسال درخواست به درگاه ارسال می‌شود.

```php
use Eram\Pardakht\Event\PurchaseInitiated;

// خصوصیات:
$event->gatewayName; // string — مثلاً "zarinpal"
$event->request;     // PurchaseRequest
```

### CallbackReceived

هنگام فراخوانی `verify()` و قبل از شروع تایید ارسال می‌شود.

```php
use Eram\Pardakht\Event\CallbackReceived;

// خصوصیات:
$event->gatewayName;  // string
$event->callbackData; // array<string, mixed>
```

### PaymentVerified

پس از تایید موفق پرداخت ارسال می‌شود.

```php
use Eram\Pardakht\Event\PaymentVerified;

// خصوصیات:
$event->gatewayName; // string
$event->transaction; // TransactionInterface
```

### PaymentSettled

پس از تسویه موفق (ملت، پارسیان) ارسال می‌شود.

```php
use Eram\Pardakht\Event\PaymentSettled;

// خصوصیات:
$event->gatewayName; // string
$event->transaction; // TransactionInterface
```

### PaymentFailed

هنگام ناموفق بودن عملیات درگاه ارسال می‌شود.

```php
use Eram\Pardakht\Event\PaymentFailed;

// خصوصیات:
$event->gatewayName; // string
$event->reason;      // string — پیام خطای قابل خواندن
$event->errorCode;   // int|string — کد خطای مخصوص درگاه (پیش‌فرض: 0)
```

## جریان رویدادها

```
purchase() ──→ PurchaseInitiated ──→ [درخواست درگاه] ──→ ریدایرکت
                                                             │
آدرس کال‌بک ←─────────────────────────────────────────────────┘
     │
verify()  ──→ CallbackReceived ──→ [درخواست تایید]
     │                                    │
     │                              ┌─────┴─────┐
     │                           موفق        ناموفق
     │                              │             │
     │                     PaymentVerified   PaymentFailed
     │
settle() ──→ [درخواست تسویه]
                    │
              ┌─────┴─────┐
           موفق        ناموفق
              │             │
       PaymentSettled  PaymentFailed
```

## موارد استفاده

- **لاگ‌گیری** — ثبت هر تلاش پرداخت و نتیجه آن
- **اعلان‌ها** — ارسال پیامک/ایمیل هنگام `PaymentVerified`
- **آنالیتیکس** — پیگیری نرخ تبدیل از `PurchaseInitiated` تا `PaymentVerified`
- **هشدار** — نظارت بر `PaymentFailed` برای مشکلات عملیاتی
- **ردپای حسابرسی** — ذخیره تمام رویدادها برای انطباق

</div>
