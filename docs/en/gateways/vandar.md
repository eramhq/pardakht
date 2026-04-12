# Vandar

> Vandar payment gateway. REST-based.

## Configuration

```php
use Eram\Pardakht\Gateway\Vandar\VandarConfig;

$config = new VandarConfig(
    apiKey: 'your-api-key',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `apiKey` | `string` | Yes | Your Vandar API key |

## Purchase

```php
$gateway = $pardakht->create('vandar', $config);

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
- Amount is sent in Rials
