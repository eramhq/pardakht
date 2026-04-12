# Mellat (Behpardakht)

> Bank Mellat's payment gateway. SOAP-based with mandatory settlement step.

## Configuration

```php
use Eram\Pardakht\Gateway\Mellat\MellatConfig;

$config = new MellatConfig(
    terminalId: 123456,
    username: 'your-username',
    password: 'your-password',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `terminalId` | `int` | Yes | Terminal ID from Behpardakht |
| `username` | `string` | Yes | Web service username |
| `password` | `string` | Yes | Web service password |

## Purchase

```php
$gateway = $pardakht->create('mellat', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
));

// POST form — must use auto-submit
echo $response->renderAutoSubmitForm();
```

## Verify

```php
$transaction = $gateway->verify();
```

## Settlement (Required)

Mellat requires settlement after verification. If you skip this step, the payment will be automatically reversed after approximately 15-30 minutes.

```php
use Eram\Pardakht\Contracts\SupportsSettlement;

if ($gateway instanceof SupportsSettlement) {
    $transaction = $gateway->settle($transaction);
}
```

## Notes

- SOAP-based — requires `ext-soap`
- Uses POST form redirect (not GET)
- Settlement is mandatory — skipping it reverses the payment
- Amount is sent in Rials
