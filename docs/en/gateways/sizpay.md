# Sizpay

> Sizpay payment gateway. REST-based.

## Configuration

```php
use Eram\Pardakht\Gateway\Sizpay\SizpayConfig;

$config = new SizpayConfig(
    merchantId: 'your-merchant-id',
    terminalId: 'your-terminal-id',
    username: 'your-username',
    password: 'your-password',
    signKey: 'your-sign-key',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `merchantId` | `string` | Yes | Merchant ID |
| `terminalId` | `string` | Yes | Terminal ID |
| `username` | `string` | Yes | Web service username |
| `password` | `string` | Yes | Web service password |
| `signKey` | `string` | Yes | Request signing key |

## Purchase

```php
$gateway = $pardakht->create('sizpay', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
));

header('Location: ' . $response->getUrl());
```

## Verify

```php
$transaction = $gateway->verify();
```

## Settlement

Not required. Payments are settled automatically.

## Notes

- REST-based
- Requires 5 configuration parameters
- Amount is sent in Rials
