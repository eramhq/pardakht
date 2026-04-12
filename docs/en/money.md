# Money & Currency

## The Rial/Toman Problem

Iran has two units of currency in everyday use: the **Rial** (IRR, official) and the **Toman** (IRT, colloquial, equal to 10 Rials). Payment gateway APIs are inconsistent — some expect Rials, others expect Tomans. A mismatched conversion means your users pay 10x too much or 10x too little.

Pardakht eliminates this class of bug entirely with the `Amount` value object.

## Amount

`Amount` is an immutable value object that stores monetary values internally in Rials. Each gateway knows which unit its API expects and converts automatically.

### Creating Amounts

```php
use Eram\Pardakht\Money\Amount;

$a = Amount::fromToman(50_000);   // 50,000 Toman = 500,000 Rials
$b = Amount::fromRials(500_000);  // 500,000 Rials = 50,000 Toman

$a->equals($b); // true — same value, different construction
```

### Reading Values

```php
$amount = Amount::fromToman(25_000);

$amount->inRials(); // 250,000
$amount->inToman(); // 25,000
```

### Arithmetic

All arithmetic returns new `Amount` instances (immutability):

```php
$a = Amount::fromToman(30_000);
$b = Amount::fromToman(20_000);

$a->add($b)->inToman();      // 50,000
$a->subtract($b)->inToman(); // 10,000
```

Subtracting a larger amount from a smaller one throws `InvalidAmountException` (amounts cannot be negative).

### Comparisons

```php
$a = Amount::fromToman(30_000);
$b = Amount::fromToman(20_000);

$a->greaterThan($b); // true
$a->lessThan($b);    // false
$a->equals($b);      // false
$a->isZero();        // false
```

### String Representation

`Amount` implements `__toString()`, which returns the Rial value as a string:

```php
(string) Amount::fromToman(50_000); // "500000"
```

## Currency Enum

The `Currency` enum represents the two units:

```php
use Eram\Pardakht\Money\Currency;

Currency::IRR; // Rial
Currency::IRT; // Toman

Currency::IRR->label(); // "ریال"
Currency::IRT->label(); // "تومان"
```

## How Gateways Use Amount

You never convert manually. Each gateway reads the unit it needs:

```php
// You write:
$request = new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    // ...
);

// Zarinpal internally calls $amount->inRials()  → sends 500,000
// IDPay internally calls $amount->inRials()     → sends 500,000
// All gateways handle the conversion themselves
```
