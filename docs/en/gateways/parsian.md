# Parsian (Pec)

> Parsian E-Commerce gateway. SOAP-based with mandatory settlement step.

## Configuration

```php
use Eram\Pardakht\Gateway\Parsian\ParsianConfig;

$config = new ParsianConfig(
    pin: 'your-pin-code',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `pin` | `string` | Yes | PIN code from Parsian bank |

## Purchase

```php
$gateway = $pardakht->create('parsian', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
));

// POST form redirect
echo $response->renderAutoSubmitForm();
```

## Verify

```php
$transaction = $gateway->verify();
```

## Settlement (Required)

Parsian requires settlement after verification. Unsettled payments are reversed automatically.

```php
use Eram\Pardakht\Contracts\SupportsSettlement;

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## Notes

- SOAP-based — requires `ext-soap`
- Uses POST form redirect (not GET)
- Settlement is mandatory
- Amount is sent in Rials
