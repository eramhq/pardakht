# Pay.ir

> Pay.ir payment gateway. REST-based.

## Configuration

```php
use Eram\Pardakht\Gateway\PayIr\PayIrConfig;

$config = new PayIrConfig(
    apiKey: 'your-api-key',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `apiKey` | `string` | Yes | Your Pay.ir API key |

## Purchase

```php
$gateway = $pardakht->create('payir', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'Order payment',
    mobile: '09123456789',
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
