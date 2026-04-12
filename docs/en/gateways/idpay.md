# IDPay

> Popular Iranian payment gateway. REST-based with sandbox support.

## Configuration

```php
use Eram\Pardakht\Gateway\IDPay\IDPayConfig;

$config = new IDPayConfig(
    apiKey: 'your-api-key',
    sandbox: false, // Set true for testing
);
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `apiKey` | `string` | Yes | — | Your IDPay API key |
| `sandbox` | `bool` | No | `false` | Use sandbox environment |

## Purchase

```php
$gateway = $pardakht->create('idpay', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'Payment for order',
    mobile: '09123456789',
    email: 'user@example.com',
));

header('Location: ' . $response->getUrl());
```

## Verify

```php
$transaction = $gateway->verify();
```

## Sandbox

```php
$config = new IDPayConfig(
    apiKey: 'test',
    sandbox: true,
);
```

## Settlement

Not required. Payments are settled automatically.

## Notes

- REST-based
- Amount is sent in Rials
- Supports sandbox mode for testing
