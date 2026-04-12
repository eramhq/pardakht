# Zibal

> Iranian payment gateway. REST-based.

## Configuration

```php
use Eram\Pardakht\Gateway\Zibal\ZibalConfig;

$config = new ZibalConfig(
    merchant: 'your-merchant-code',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `merchant` | `string` | Yes | Your Zibal merchant code |

## Purchase

```php
$gateway = $pardakht->create('zibal', $config);

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
