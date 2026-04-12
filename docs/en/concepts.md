# Core Concepts

## Payment Flow

Every payment in Pardakht follows the same lifecycle, regardless of which gateway you use:

```
Create → Purchase → Redirect → Callback → Verify → [Settle]
```

1. **Create** — Instantiate a gateway via `Pardakht::create()`
2. **Purchase** — Send a `PurchaseRequest` to the gateway; receive a `RedirectResponse`
3. **Redirect** — Send the user to the gateway's payment page (GET redirect or POST form)
4. **Callback** — The gateway redirects the user back to your `callbackUrl`
5. **Verify** — Confirm the payment with the gateway; receive a `Transaction`
6. **Settle** — (Only Mellat, Parsian) Finalize the payment before it auto-reverses

## Gateway Abstraction

All gateways implement `GatewayInterface`, which exposes exactly two methods:

```php
interface GatewayInterface
{
    public function getName(): string;
    public function purchase(PurchaseRequest $request): RedirectResponse;
    public function verify(?array $callbackData = null): TransactionInterface;
}
```

This means you can swap gateways by changing a single string — the rest of your code stays identical.

## Optional Capabilities

Not every gateway supports every feature. Optional capabilities are expressed as separate interfaces:

- **`SupportsSettlement`** — Mellat and Parsian require a `settle()` call after `verify()`. If you skip settlement, the payment auto-reverses in 15-30 minutes.
- **`SupportsRefund`** — Gateways that allow programmatic refunds.

Use `instanceof` checks to handle these:

```php
if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## The Amount Problem

Iranian payment systems mix Rial and Toman interchangeably. Some gateway APIs expect Rials, others expect Tomans. A 10x error in either direction means your users pay 10x too much or too little.

Pardakht solves this with the `Amount` value object:

```php
$amount = Amount::fromToman(50_000);  // You think in Toman
$amount->inRials();                    // 500,000 — gateway gets Rials
$amount->inToman();                    // 50,000 — display gets Toman
```

`Amount` stores everything internally in Rials. Each gateway knows which unit its API expects and converts automatically. You never need to multiply or divide by 10 yourself.

## Immutability

All value objects and DTOs in Pardakht are immutable:

- `Amount` — arithmetic returns new instances
- `Transaction` — `withStatus()` and `withTrackingCode()` return new instances
- `PurchaseRequest` — set once at construction
- `RedirectResponse` — set once at construction

This prevents accidental mutation bugs where a shared reference changes state unexpectedly.

## Dependency Injection

The `Pardakht` constructor accepts four optional dependencies:

```php
$pardakht = new Pardakht(
    httpClient: $myHttpClient,          // Custom HTTP transport
    logger: $myLogger,                  // Debug logging
    eventDispatcher: $myDispatcher,     // Lifecycle events
    soapFactory: $mySoapFactory,        // Custom SOAP client creation
);
```

All parameters are optional. Defaults use `ext-curl` and `ext-soap` directly — no Guzzle, no Symfony HttpClient, no framework coupling.

## SOAP vs REST

Pardakht supports both SOAP-based bank gateways and REST-based payment gateways. The distinction is invisible to your code — both implement `GatewayInterface`. The only user-facing difference is the redirect:

- **REST gateways** (Zarinpal, IDPay, Zibal, etc.) return a URL for a simple GET redirect.
- **SOAP gateways** (Mellat, Saman, Parsian) return POST form data. Use `renderAutoSubmitForm()` to generate an auto-submitting HTML form.

```php
if ($response->isPost()) {
    echo $response->renderAutoSubmitForm();
} else {
    header('Location: ' . $response->getUrl());
}
```
