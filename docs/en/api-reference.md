# API Reference

Complete reference for all public classes, interfaces, and methods.

## Pardakht (Entry Point)

```php
namespace Eram\Pardakht;

final class Pardakht
{
    public function __construct(
        ?HttpClient $httpClient = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
        ?SoapClientFactory $soapFactory = null,
    );

    public function create(string $gateway, object $config): GatewayInterface;

    /** @return list<string> */
    public static function available(): array;
}
```

## Contracts

### GatewayInterface

```php
namespace Eram\Pardakht\Contracts;

interface GatewayInterface
{
    public function getName(): string;
    public function purchase(PurchaseRequest $request): RedirectResponse;

    /** @param array<string, mixed>|null $callbackData */
    public function verify(?array $callbackData = null): TransactionInterface;
}
```

### TransactionInterface

```php
namespace Eram\Pardakht\Contracts;

interface TransactionInterface
{
    public function getId(): TransactionId;
    public function getGatewayName(): string;
    public function getAmount(): Amount;
    public function getStatus(): TransactionStatus;
    public function getReferenceId(): string;
    public function getTrackingCode(): ?string;
    public function getCardNumber(): ?string;

    /** @return array<string, mixed> */
    public function getExtra(): array;

    public function withStatus(TransactionStatus $status): static;
}
```

### SupportsSettlement

```php
namespace Eram\Pardakht\Contracts;

interface SupportsSettlement
{
    public function settle(TransactionInterface $transaction): TransactionInterface;
}
```

Implemented by: Mellat, Parsian.

### SupportsRefund

```php
namespace Eram\Pardakht\Contracts;

interface SupportsRefund
{
    public function refund(TransactionInterface $transaction, ?Amount $amount = null): TransactionInterface;
}
```

## Money

### Amount

```php
namespace Eram\Pardakht\Money;

final class Amount
{
    public static function fromRials(int $rials): self;
    public static function fromToman(int $toman): self;

    public function inRials(): int;
    public function inToman(): int;

    public function add(self $other): self;
    public function subtract(self $other): self;

    public function equals(self $other): bool;
    public function greaterThan(self $other): bool;
    public function lessThan(self $other): bool;
    public function isZero(): bool;

    public function __toString(): string; // Returns Rial value
}
```

### Currency

```php
namespace Eram\Pardakht\Money;

enum Currency: string
{
    case IRR = 'IRR'; // Rial
    case IRT = 'IRT'; // Toman

    public function label(): string;
}
```

## Transaction

### Transaction

```php
namespace Eram\Pardakht\Transaction;

final class Transaction implements TransactionInterface
{
    public function __construct(
        TransactionId $id,
        string $gatewayName,
        Amount $amount,
        TransactionStatus $status,
        string $referenceId,
        ?string $trackingCode = null,
        ?string $cardNumber = null,
        array $extra = [],
    );

    public function withStatus(TransactionStatus $status): static;
    public function withTrackingCode(string $trackingCode): self;

    // ... all TransactionInterface methods
}
```

### TransactionId

```php
namespace Eram\Pardakht\Transaction;

final class TransactionId
{
    public function __construct(string $value);

    public function value(): string;
    public function equals(self $other): bool;
    public function __toString(): string;
}
```

### TransactionStatus

```php
namespace Eram\Pardakht\Transaction;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Verified = 'verified';
    case Settled = 'settled';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(string $locale = 'fa'): string;
}
```

Labels by locale:

| Case | `'fa'` | `'en'` |
|------|--------|--------|
| Pending | در انتظار | Pending |
| Paid | پرداخت شده | Paid |
| Verified | تایید شده | Verified |
| Settled | تسویه شده | Settled |
| Failed | ناموفق | Failed |
| Refunded | بازگشت داده شده | Refunded |

## HTTP

### PurchaseRequest

```php
namespace Eram\Pardakht\Http;

final class PurchaseRequest
{
    /** @param array<string, mixed> $extra */
    public function __construct(
        Amount $amount,
        string $callbackUrl,
        string $orderId,
        string $description = '',
        ?string $mobile = null,
        ?string $email = null,
        array $extra = [],
    );

    public function getAmount(): Amount;
    public function getCallbackUrl(): string;
    public function getOrderId(): string;
    public function getDescription(): string;
    public function getMobile(): ?string;
    public function getEmail(): ?string;

    /** @return array<string, mixed> */
    public function getExtra(): array;
}
```

### RedirectResponse

```php
namespace Eram\Pardakht\Http;

final class RedirectResponse
{
    public static function redirect(string $url, string $referenceId): self;

    /** @param array<string, string> $formData */
    public static function post(string $url, string $referenceId, array $formData = []): self;

    public function getUrl(): string;
    public function getMethod(): string;
    public function getReferenceId(): string;

    /** @return array<string, string> */
    public function getFormData(): array;

    public function isPost(): bool;
    public function renderAutoSubmitForm(string $submitText = 'در حال انتقال به درگاه...'): string;
}
```

### HttpClient

```php
namespace Eram\Pardakht\Http;

interface HttpClient
{
    /** @param array<string, string> $headers */
    public function postJson(string $url, string $body, array $headers = []): HttpResponse;
}
```

### HttpResponse

```php
namespace Eram\Pardakht\Http;

final class HttpResponse
{
    public int $statusCode;
    public string $body;
    public array $headers;

    public function header(string $name): ?string;
    public function isSuccessful(): bool;
}
```

### Logger

```php
namespace Eram\Pardakht\Http;

interface Logger
{
    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void;
}
```

### EventDispatcher

```php
namespace Eram\Pardakht\Http;

interface EventDispatcher
{
    public function dispatch(object $event): object;
}
```

## Banking

### CardNumber

```php
namespace Eram\Pardakht\Banking;

final class CardNumber
{
    public function __construct(string $number);

    public function number(): string;
    public function masked(): string;
    public function formatted(): string;
    public function bankName(): ?string;
    public function equals(self $other): bool;
    public function __toString(): string;

    public static function isValid(string $number): bool;
}
```

### Sheba

```php
namespace Eram\Pardakht\Banking;

final class Sheba
{
    public function __construct(string $sheba);

    public function value(): string;
    public function digits(): string;
    public function bankName(): ?string;
    public function formatted(): string;
    public function equals(self $other): bool;
    public function __toString(): string;

    public static function isValid(string $sheba): bool;
}
```

### BankIdentifier

```php
namespace Eram\Pardakht\Banking;

final class BankIdentifier
{
    public static function fromCardNumber(string $number): ?string;
    public static function fromSheba(string $sheba): ?string;
}
```

## Events

All events are simple data classes with `public readonly` properties.

| Event | Properties |
|-------|-----------|
| `PurchaseInitiated` | `gatewayName: string`, `request: PurchaseRequest` |
| `CallbackReceived` | `gatewayName: string`, `callbackData: array` |
| `PaymentVerified` | `gatewayName: string`, `transaction: TransactionInterface` |
| `PaymentSettled` | `gatewayName: string`, `transaction: TransactionInterface` |
| `PaymentFailed` | `gatewayName: string`, `reason: string`, `errorCode: int\|string` |

## Exceptions

| Exception | Extends | Extra Methods |
|-----------|---------|--------------|
| `PardakhtException` | `RuntimeException` | — |
| `InvalidAmountException` | `PardakhtException` | — |
| `ConnectionException` | `PardakhtException` | — |
| `GatewayException` | `PardakhtException` | `getGatewayName(): string`, `getErrorCode(): int\|string` |
| `VerificationException` | `GatewayException` | *(inherited)* |
