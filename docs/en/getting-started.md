# Getting Started

## Requirements

- PHP 8.1 or later
- Extensions: `ext-curl`, `ext-json`, `ext-openssl`, `ext-soap`

## Installation

```bash
composer require eram/pardakht
```

Pardakht has **zero** Composer dependencies. It only relies on PHP extensions that ship with most PHP installations.

## Quick Example

Here is a complete payment flow using Zarinpal:

### 1. Create a Gateway

```php
use Eram\Pardakht\Pardakht;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;

$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig(
    merchantId: 'your-merchant-id',
));
```

### 2. Initiate a Purchase

```php
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Http\PurchaseRequest;

$request = new PurchaseRequest(
    amount: Amount::fromToman(50_000),       // 50,000 Toman
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'Premium subscription',
);

$response = $gateway->purchase($request);
```

### 3. Redirect the User

```php
// For REST gateways (Zarinpal, IDPay, etc.) — simple redirect
header('Location: ' . $response->getUrl());
exit;

// For SOAP gateways (Mellat, Saman, Parsian) — auto-submit form
echo $response->renderAutoSubmitForm();
```

### 4. Handle the Callback

```php
// In your callback URL handler:
$transaction = $gateway->verify();

echo $transaction->getReferenceId();    // Gateway reference
echo $transaction->getTrackingCode();   // User-facing tracking code
echo $transaction->getCardNumber();     // Payer card (masked or full)
echo $transaction->getAmount()->inToman(); // 50000
```

### 5. Settlement (if required)

Some bank gateways (Mellat, Parsian) require a separate settlement step after verification. If you skip it, the payment is automatically reversed after 15-30 minutes.

```php
use Eram\Pardakht\Contracts\SupportsSettlement;

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## Available Gateways

| Alias | Gateway | Protocol | Settlement |
|-------|---------|----------|------------|
| `mellat` | Mellat (Behpardakht) | SOAP | Required |
| `saman` | Saman (Sep) | SOAP | No |
| `parsian` | Parsian (Pec) | SOAP | Required |
| `sadad` | Sadad (Bank Melli) | REST | No |
| `pasargad` | Pasargad | REST | No |
| `zarinpal` | Zarinpal | REST | No |
| `idpay` | IDPay | REST | No |
| `zibal` | Zibal | REST | No |
| `payir` | Pay.ir | REST | No |
| `nextpay` | NextPay | REST | No |
| `vandar` | Vandar | REST | No |
| `sizpay` | Sizpay | REST | No |

## Next Steps

- [Core Concepts](concepts.md) — Understand the design behind the library
- [Cookbook](cookbook.md) — Practical recipes for real-world scenarios
- [Gateway Catalog](README.md#gateway-catalog) — Detailed docs per gateway
