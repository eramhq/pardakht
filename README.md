# Pardakht

A simple, type-safe PHP library for Iranian payment gateways.

[![Tests](https://github.com/eramhq/pardakht/actions/workflows/tests.yml/badge.svg)](https://github.com/eramhq/pardakht/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)

**Documentation:** [English](docs/en/README.md) | [فارسی](docs/fa/README.md)

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

## Learn More

Full documentation with API reference, cookbook, gateway guides, and more:

- [English Documentation](docs/en/README.md)
- [مستندات فارسی](docs/fa/README.md)

## License

MIT - Built by [Eram](https://github.com/eramhq) — open-source tools for the Persian ecosystem ([daynum](https://github.com/eramhq/daynum), [pardakht](https://github.com/eramhq/pardakht), [persian-kit](https://github.com/eramhq/persian-kit))
