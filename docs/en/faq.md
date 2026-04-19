# FAQ

## Why so few dependencies?

Payment libraries are critical infrastructure. Every dependency is a supply-chain risk and a version-conflict surface. Pardakht pulls in only [`eram/abzar`](https://github.com/eramhq/abzar-php) — our own Persian/Iranian utility library, MIT-licensed and itself free of third-party runtime deps — and otherwise relies on PHP extensions that ship with standard installations: `ext-curl`, `ext-json`, `ext-openssl`, `ext-soap`. No Guzzle, no Symfony components, no framework coupling.

If you prefer Guzzle or another HTTP client, implement the `HttpClient` interface and inject it — see the [Cookbook](cookbook.md#custom-http-client-guzzle-example).

## Why store amounts in Rials internally?

Iranian payment APIs are split: some expect Rials, others expect Tomans. The 10x conversion is the single most common source of payment bugs in Iranian e-commerce. By storing everything in Rials (the smallest unit) internally, each gateway converts to the unit its API expects automatically. You work in whichever unit you prefer:

```php
Amount::fromToman(50_000)->inRials();  // 500,000
Amount::fromRials(500_000)->inToman(); // 50,000
```

## What is the difference between SOAP and REST gateways?

Traditional Iranian bank gateways (Mellat, Saman, Parsian) use SOAP web services. Modern payment gateways (Zarinpal, IDPay, Zibal) use REST APIs. From your code's perspective, both implement `GatewayInterface` identically. The only visible difference is the redirect — SOAP gateways require a POST form, while REST gateways use a simple URL redirect.

## What is settlement and why do some gateways need it?

Mellat and Parsian use a three-phase protocol: **purchase → verify → settle**. After verification, the payment is in a "pending settlement" state. If you don't call `settle()` within the gateway's timeout (typically 15-30 minutes), the payment is automatically reversed and the money returns to the buyer.

This exists because the bank separates "confirming the payment happened" (verify) from "confirming the merchant wants the money" (settle). Use `instanceof SupportsSettlement` to handle this generically.

## How do I test without a real gateway?

You have several options:

1. **Sandbox mode** — Zarinpal and IDPay support sandbox environments:
   ```php
   new ZarinpalConfig(merchantId: 'test', sandbox: true);
   new IDPayConfig(apiKey: 'test', sandbox: true);
   ```

2. **Mock the HttpClient** — Implement `HttpClient` to return fixed responses in your test suite.

3. **Mock the gateway** — Since gateways implement `GatewayInterface`, you can mock the entire gateway in your application tests.

## Why is there no Laravel/Symfony integration package?

Pardakht is designed to work with any PHP application. Framework integration is typically just a service provider that reads config and registers the `Pardakht` instance in the container — roughly 20 lines of code. We believe this is simple enough that a dedicated package would add more maintenance burden than value.

Example for Laravel:

```php
// AppServiceProvider
$this->app->singleton(Pardakht::class, fn () => new Pardakht(
    logger: new LaravelLogger(),
));
```

## Can I use multiple gateways simultaneously?

Yes. A single `Pardakht` instance can create any number of gateway instances:

```php
$pardakht = new Pardakht();
$zarinpal = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-1'));
$mellat = $pardakht->create('mellat', new MellatConfig(123, 'user', 'pass'));
```

They share the same HTTP client and logger but are otherwise independent.

## How do I handle the callback from different gateways?

Each gateway sends different parameters in the callback. The `verify()` method abstracts this — it auto-detects `$_POST` or `$_GET` data and extracts what it needs. You can also pass callback data explicitly:

```php
// Auto-detect (reads $_POST or $_GET)
$transaction = $gateway->verify();

// Explicit data (useful in frameworks)
$transaction = $gateway->verify($request->all());
```

## What happens if verification fails?

A `VerificationException` is thrown, which extends `GatewayException`. It carries the gateway name and error code:

```php
try {
    $transaction = $gateway->verify();
} catch (VerificationException $e) {
    $e->getGatewayName(); // "zarinpal"
    $e->getErrorCode();   // -51
    $e->getMessage();     // Human-readable error message
}
```

## Which PHP versions are supported?

PHP 8.1 and later. The library uses enums, readonly properties, named arguments, and other PHP 8.1+ features.
