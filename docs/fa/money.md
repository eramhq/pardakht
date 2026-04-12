<div dir="rtl">

# پول و ارز

## مشکل ریال/تومان

ایران دو واحد پولی در استفاده روزمره دارد: **ریال** (IRR، رسمی) و **تومان** (IRT، عامیانه، برابر ۱۰ ریال). APIهای درگاه‌های پرداخت ناهماهنگ هستند — برخی ریال و برخی تومان می‌خواهند. یک تبدیل اشتباه یعنی کاربران شما ۱۰ برابر بیشتر یا ۱۰ برابر کمتر پرداخت می‌کنند.

پرداخت این دسته از باگ‌ها را کاملاً با آبجکت مقداری `Amount` حذف می‌کند.

## Amount

`Amount` یک آبجکت مقداری تغییرناپذیر (immutable) است که مقادیر پولی را داخلی به ریال ذخیره می‌کند. هر درگاه می‌داند API‌اش چه واحدی می‌خواهد و به‌طور خودکار تبدیل می‌کند.

### ساخت Amount

<div dir="ltr">

```php
use Eram\Pardakht\Money\Amount;

$a = Amount::fromToman(50_000);   // 50,000 تومان = 500,000 ریال
$b = Amount::fromRials(500_000);  // 500,000 ریال = 50,000 تومان

$a->equals($b); // true — مقدار یکسان، ساخت متفاوت
```

</div>

### خواندن مقادیر

<div dir="ltr">

```php
$amount = Amount::fromToman(25_000);

$amount->inRials(); // 250,000
$amount->inToman(); // 25,000
```

</div>

### عملیات ریاضی

تمام عملیات ریاضی نمونه‌های جدید `Amount` برمی‌گردانند (تغییرناپذیری):

<div dir="ltr">

```php
$a = Amount::fromToman(30_000);
$b = Amount::fromToman(20_000);

$a->add($b)->inToman();      // 50,000
$a->subtract($b)->inToman(); // 10,000
```

</div>

کم کردن مقدار بزرگ‌تر از مقدار کوچک‌تر باعث پرتاب `InvalidAmountException` می‌شود (مبالغ نمی‌توانند منفی باشند).

### مقایسه‌ها

<div dir="ltr">

```php
$a = Amount::fromToman(30_000);
$b = Amount::fromToman(20_000);

$a->greaterThan($b); // true
$a->lessThan($b);    // false
$a->equals($b);      // false
$a->isZero();        // false
```

</div>

### نمایش رشته‌ای

`Amount` متد `__toString()` را پیاده‌سازی می‌کند که مقدار ریالی را به صورت رشته برمی‌گرداند:

<div dir="ltr">

```php
(string) Amount::fromToman(50_000); // "500000"
```

</div>

## Currency Enum

اینام `Currency` دو واحد پولی را نشان می‌دهد:

<div dir="ltr">

```php
use Eram\Pardakht\Money\Currency;

Currency::IRR; // ریال
Currency::IRT; // تومان

Currency::IRR->label(); // "ریال"
Currency::IRT->label(); // "تومان"
```

</div>

## نحوه استفاده درگاه‌ها از Amount

نیازی به تبدیل دستی نیست. هر درگاه واحد مورد نیازش را می‌خواند:

<div dir="ltr">

```php
// شما می‌نویسید:
$request = new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    // ...
);

// زرین‌پال داخلی $amount->inRials() را فراخوانی می‌کند  → 500,000 ارسال می‌کند
// آیدی‌پی داخلی $amount->inRials() را فراخوانی می‌کند   → 500,000 ارسال می‌کند
// همه درگاه‌ها تبدیل را خودشان انجام می‌دهند
```

</div>

</div>
