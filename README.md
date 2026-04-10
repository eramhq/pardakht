# Pardakht

A simple, type-safe PHP library for Iranian payment gateways.

[![Tests](https://github.com/eramhq/pardakht/actions/workflows/tests.yml/badge.svg)](https://github.com/eramhq/pardakht/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)

[**مستندات فارسی**](README.fa.md)

## Install

```bash
composer require eramhq/pardakht guzzlehttp/guzzle guzzlehttp/psr7
```

## Usage

```php
use EramDev\Pardakht\Pardakht;
use EramDev\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Money\Amount;

// Create gateway (Guzzle auto-discovered)
$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('your-merchant-id'));

// Purchase
$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://yoursite.com/callback',
    orderId: 'ORDER-123',
    description: 'Test payment',
));

// Redirect user
header('Location: ' . $response->getUrl());

// Verify (on callback page — auto-detects GET/POST)
$transaction = $gateway->verify();

echo $transaction->getTrackingCode();
echo $transaction->getAmount()->inToman();
```

### Bank Gateways (Mellat, Parsian)

Bank gateways require an extra settlement step:

```php
use EramDev\Pardakht\Contracts\SupportsSettlement;
use EramDev\Pardakht\Gateway\Mellat\MellatConfig;

$gateway = $pardakht->create('mellat', new MellatConfig(
    terminalId: 12345, username: 'user', password: 'pass',
));

$transaction = $gateway->verify();

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## Supported Gateways

| Gateway | Type | Settlement |
|---------|------|------------|
| Mellat (Behpardakht) | SOAP | Yes |
| Saman (Sep) | SOAP | No |
| Parsian (Pec) | SOAP | Yes |
| Sadad (Bank Melli) | REST | No |
| Pasargad | REST | No |
| Zarinpal | REST | No |
| IDPay | REST | No |
| Zibal | REST | No |
| Pay.ir | REST | No |
| NextPay | REST | No |
| Vandar | REST | No |
| Sizpay | REST | No |

## Amount (Rial/Toman Safe)

```php
$amount = Amount::fromToman(50_000);
$amount->inRials();  // 500000
$amount->inToman();  // 50000
```

## Banking Utilities

```php
use EramDev\Pardakht\Banking\CardNumber;
use EramDev\Pardakht\Banking\Sheba;

CardNumber::isValid('6037990000000006'); // true
Sheba::isValid('IR062960000000100324200001'); // true
```

## License

MIT - Built by [Eram.dev](https://eram.dev)
