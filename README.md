# Pardakht

A simple, type-safe PHP library for Iranian payment gateways.

[![Tests](https://github.com/eramhq/pardakht/actions/workflows/tests.yml/badge.svg)](https://github.com/eramhq/pardakht/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)

[**مستندات فارسی**](README.fa.md)

## Install

```bash
composer require eram/pardakht
```

## Usage

The flow is always the same: **create → purchase → redirect → verify**. Some bank gateways need an extra **settle** step after verify.

```php
use Eram\Pardakht\Pardakht;
use Eram\Pardakht\Contracts\SupportsSettlement;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Money\Amount;

$pardakht = new Pardakht();

// 1. Create a gateway — swap the alias and config for any supported gateway
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('your-merchant-id'));

// 2. Start a purchase
$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://yoursite.com/callback',
    orderId: 'ORDER-123',
    description: 'Test payment',
    mobile: '09121234567',
    email: 'user@example.com',
));

// 3. Send the user to the gateway
header('Location: ' . $response->getUrl());
// Some gateways (Mellat, Saman, Sizpay) need a POST form instead:
// echo $response->renderAutoSubmitForm();

// 4. On the callback page — verify() auto-detects $_GET / $_POST
$transaction = $gateway->verify();

// 5. Bank gateways (Mellat, Parsian) require settlement or the money is reversed
if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}

// Read the result
$transaction->getId()->value();        // 'ORDER-123'
$transaction->getStatus();             // TransactionStatus::Settled
$transaction->getReferenceId();        // gateway reference
$transaction->getTrackingCode();       // user-visible tracking code
$transaction->getCardNumber();         // '610433******0003'
$transaction->getAmount()->inToman();  // 50000
```

That's the whole API. Every gateway follows the same five steps; only the config class changes.

## Supported Gateways

| Alias | Gateway | Config | Needs Settlement |
|-------|---------|--------|------------------|
| `mellat` | Mellat (Behpardakht) | `MellatConfig` | Yes |
| `saman` | Saman (Sep) | `SamanConfig` | No |
| `parsian` | Parsian (Pec) | `ParsianConfig` | Yes |
| `sadad` | Sadad (Bank Melli) | `SadadConfig` | No |
| `pasargad` | Pasargad | `PasargadConfig` | No |
| `zarinpal` | Zarinpal | `ZarinpalConfig` | No |
| `idpay` | IDPay | `IDPayConfig` | No |
| `zibal` | Zibal | `ZibalConfig` | No |
| `payir` | Pay.ir | `PayIrConfig` | No |
| `nextpay` | NextPay | `NextPayConfig` | No |
| `vandar` | Vandar | `VandarConfig` | No |
| `sizpay` | Sizpay | `SizpayConfig` | No |

## Amount (Rial/Toman Safe)

The `Amount` value object eliminates the most common Iranian payment bug: mixing Rial and Toman.

```php
use Eram\Pardakht\Money\Amount;

$amount = Amount::fromToman(50_000);
$amount->inRials();   // 500000
$amount->inToman();   // 50000

$sum = $amount->add(Amount::fromToman(10_000));
$sum->inToman();      // 60000

$amount->greaterThan(Amount::fromToman(20_000));  // true
$amount->equals(Amount::fromRials(500_000));      // true
```

## Banking Utilities

```php
use Eram\Pardakht\Banking\CardNumber;
use Eram\Pardakht\Banking\Sheba;

// Card number — Luhn validation + bank detection
$card = new CardNumber('6037-9900-0000-0006');
$card->number();     // '6037990000000006'
$card->masked();     // '603799******0006'
$card->formatted();  // '6037-9900-0000-0006'
$card->bankName();   // 'بانک ملی ایران'
CardNumber::isValid('6037990000000006'); // true

// Sheba (Iranian IBAN) — checksum validation + bank detection
$sheba = new Sheba('IR062960000000100324200001');
$sheba->value();     // 'IR062960000000100324200001'
$sheba->formatted(); // 'IR06 2960 0000 0010 0324 2000 01'
$sheba->bankName();  // detected from Sheba code
Sheba::isValid('IR062960000000100324200001'); // true
```

## Custom HTTP Client

Pardakht ships with a built-in `CurlHttpClient` and has **zero packagist dependencies**. If you need a custom HTTP client, implement the single-method interface:

```php
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;

class MyHttpClient implements HttpClient
{
    public function postJson(string $url, string $body, array $headers = []): HttpResponse
    {
        // your implementation
        return new HttpResponse(200, $responseBody);
    }
}

$pardakht = new Pardakht(httpClient: new MyHttpClient());
```

## Events

Implement `Eram\Pardakht\Http\EventDispatcher` to receive lifecycle events: `PurchaseInitiated`, `CallbackReceived`, `PaymentVerified`, `PaymentSettled`, `PaymentFailed`.

```php
$pardakht = new Pardakht(eventDispatcher: $yourDispatcher);
```

## License

MIT - Built by [Eram.dev](https://eram.dev)
