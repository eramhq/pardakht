# Events

Pardakht dispatches lifecycle events at key points during the payment flow. Events are optional — if no `EventDispatcher` is provided, events are silently skipped.

## Setup

Implement the `EventDispatcher` interface and pass it to `Pardakht`:

```php
use Eram\Pardakht\Http\EventDispatcher;

class MyEventDispatcher implements EventDispatcher
{
    public function dispatch(object $event): object
    {
        // Handle or forward the event
        return $event;
    }
}

$pardakht = new Pardakht(eventDispatcher: new MyEventDispatcher());
```

## Event Types

### PurchaseInitiated

Dispatched when `purchase()` is called, before the gateway request is sent.

```php
use Eram\Pardakht\Event\PurchaseInitiated;

// Properties:
$event->gatewayName; // string — e.g., "zarinpal"
$event->request;     // PurchaseRequest
```

### CallbackReceived

Dispatched when `verify()` is called, before verification begins.

```php
use Eram\Pardakht\Event\CallbackReceived;

// Properties:
$event->gatewayName;  // string
$event->callbackData; // array<string, mixed>
```

### PaymentVerified

Dispatched after successful payment verification.

```php
use Eram\Pardakht\Event\PaymentVerified;

// Properties:
$event->gatewayName; // string
$event->transaction; // TransactionInterface
```

### PaymentSettled

Dispatched after successful settlement (Mellat, Parsian).

```php
use Eram\Pardakht\Event\PaymentSettled;

// Properties:
$event->gatewayName; // string
$event->transaction; // TransactionInterface
```

### PaymentFailed

Dispatched when a gateway operation fails.

```php
use Eram\Pardakht\Event\PaymentFailed;

// Properties:
$event->gatewayName; // string
$event->reason;      // string — human-readable error message
$event->errorCode;   // int|string — gateway-specific error code (default: 0)
```

## Event Flow

```
purchase() ──→ PurchaseInitiated ──→ [gateway request] ──→ redirect
                                                             │
callback URL ←─────────────────────────────────────────────────┘
     │
verify()  ──→ CallbackReceived ──→ [verification request]
     │                                    │
     │                              ┌─────┴─────┐
     │                          success       failure
     │                              │             │
     │                     PaymentVerified   PaymentFailed
     │
settle() ──→ [settlement request]
                    │
              ┌─────┴─────┐
          success       failure
              │             │
       PaymentSettled  PaymentFailed
```

## Use Cases

- **Logging** — Record every payment attempt and outcome
- **Notifications** — Send SMS/email on `PaymentVerified`
- **Analytics** — Track conversion rates from `PurchaseInitiated` to `PaymentVerified`
- **Alerting** — Monitor `PaymentFailed` for operational issues
- **Audit trail** — Store all events for compliance
