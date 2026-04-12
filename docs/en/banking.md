# Banking Utilities

Pardakht includes value objects for validating and working with Iranian bank card numbers and Sheba (IBAN) numbers.

## CardNumber

A value object for 16-digit Iranian bank card numbers with Luhn validation and automatic bank detection.

### Construction and Validation

```php
use Eram\Pardakht\Banking\CardNumber;

// Construct with validation (throws on invalid)
$card = new CardNumber('6037991234567890');
$card = new CardNumber('6037-9912-3456-7890'); // Dashes are stripped

// Static validation (no exception)
CardNumber::isValid('6037991234567890'); // true
CardNumber::isValid('1234567890123456'); // false (Luhn check fails)
```

Construction throws `\InvalidArgumentException` if:
- The number is not exactly 16 digits
- The Luhn checksum fails

### Formatting

```php
$card = new CardNumber('6037991234567890');

$card->number();    // "6037991234567890"
$card->formatted(); // "6037-9912-3456-7890"
$card->masked();    // "603799******7890"
```

### Bank Detection

```php
$card = new CardNumber('6037991234567890');
$card->bankName(); // "ملی" (or null if BIN is unknown)
```

Bank detection uses the first 6 digits (BIN) to identify the issuing bank.

### Comparison

```php
$a = new CardNumber('6037991234567890');
$b = new CardNumber('6037-9912-3456-7890');
$a->equals($b); // true
```

## Sheba (IBAN)

A value object for Iranian IBANs (International Bank Account Numbers) with ISO 13616 checksum validation and bank detection.

Iranian Sheba format: `IR` + 2 check digits + 22 digits = 26 characters total.

### Construction and Validation

```php
use Eram\Pardakht\Banking\Sheba;

// With or without IR prefix
$sheba = new Sheba('IR062960000000100324200001');
$sheba = new Sheba('062960000000100324200001'); // IR is auto-prepended

// Static validation
Sheba::isValid('IR062960000000100324200001'); // true
```

Construction throws `\InvalidArgumentException` if:
- The format is not IR + 24 digits
- The ISO 13616 mod-97 checksum fails

### Reading Values

```php
$sheba = new Sheba('IR062960000000100324200001');

$sheba->value();  // "IR062960000000100324200001"
$sheba->digits(); // "062960000000100324200001" (without IR prefix)
```

### Formatting

```php
$sheba->formatted(); // "IR06 2960 0000 0010 0324 2000 01"
```

### Bank Detection

```php
$sheba->bankName(); // "ملت" (or null if bank code is unknown)
```

Bank detection uses the bank code embedded in the Sheba (digits 5-7 after IR).

### Comparison

```php
$a = new Sheba('IR062960000000100324200001');
$b = new Sheba('062960000000100324200001');
$a->equals($b); // true
```

## BankIdentifier

The `BankIdentifier` class provides static methods for bank detection without constructing a value object:

```php
use Eram\Pardakht\Banking\BankIdentifier;

BankIdentifier::fromCardNumber('6037991234567890');     // "ملی"
BankIdentifier::fromSheba('IR062960000000100324200001'); // "ملت"
```

Both methods return `?string` — `null` if the bank code is not in the database.
