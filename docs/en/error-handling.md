# Error Handling

## Exception Hierarchy

```
RuntimeException
└── PardakhtException (abstract)
    ├── InvalidAmountException
    ├── ConnectionException
    └── GatewayException
        └── VerificationException
```

All Pardakht exceptions extend `PardakhtException`, so you can catch everything with a single catch block if needed.

## Exception Types

### PardakhtException

Abstract base class for all library exceptions. Extends `\RuntimeException`.

```php
use Eram\Pardakht\Exception\PardakhtException;

try {
    $gateway->purchase($request);
} catch (PardakhtException $e) {
    // Catches any Pardakht error
}
```

### InvalidAmountException

Thrown when an invalid monetary amount is provided (e.g., negative amounts).

```php
use Eram\Pardakht\Exception\InvalidAmountException;

Amount::fromRials(-100); // throws InvalidAmountException
```

### ConnectionException

Thrown on transport-level failures: DNS resolution, TCP timeouts, TLS errors, etc. This means the gateway was unreachable — the payment was never attempted.

```php
use Eram\Pardakht\Exception\ConnectionException;

try {
    $gateway->purchase($request);
} catch (ConnectionException $e) {
    // Safe to retry — the gateway never received the request
}
```

### GatewayException

Thrown when the gateway responds with an error. Carries the gateway name and gateway-specific error code.

```php
use Eram\Pardakht\Exception\GatewayException;

try {
    $gateway->purchase($request);
} catch (GatewayException $e) {
    $e->getGatewayName(); // "zarinpal"
    $e->getErrorCode();   // -11 (gateway-specific code)
    $e->getMessage();     // Human-readable error
}
```

### VerificationException

Thrown when payment verification fails. Extends `GatewayException`, so it carries the same `getGatewayName()` and `getErrorCode()` methods.

Common causes:
- User cancelled the payment
- Payment amount mismatch
- Duplicate verification attempt
- Gateway timeout

```php
use Eram\Pardakht\Exception\VerificationException;

try {
    $transaction = $gateway->verify();
} catch (VerificationException $e) {
    // Payment did not complete successfully
}
```

## Recommended Catch Order

Catch exceptions from most specific to least specific:

```php
try {
    $response = $gateway->purchase($request);
} catch (VerificationException $e) {
    // Verification-specific handling
} catch (GatewayException $e) {
    // Other gateway errors
} catch (ConnectionException $e) {
    // Network errors — safe to retry
} catch (InvalidAmountException $e) {
    // Bad amount — fix your code
} catch (PardakhtException $e) {
    // Catch-all for any Pardakht error
}
```

## Gateway Error Codes

Each gateway has its own error code enum with human-readable messages. These are used internally to populate `GatewayException`, but you can also reference them directly:

| Gateway | Error Code Class |
|---------|-----------------|
| Zarinpal | `ZarinpalErrorCode` |
| Mellat | `MellatErrorCode` |
| Saman | `SamanErrorCode` |
| Parsian | `ParsianErrorCode` |
| Sadad | `SadadErrorCode` |

Each error code enum provides a `message(): string` method that returns a human-readable error description.
