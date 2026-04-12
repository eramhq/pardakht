# NextPay

> NextPay payment gateway. REST-based.

## Configuration

```php
use Eram\Pardakht\Gateway\NextPay\NextPayConfig;

$config = new NextPayConfig(
    apiKey: 'your-api-key',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `apiKey` | `string` | Yes | Your NextPay API key |

## Purchase

```php
$gateway = $pardakht->create('nextpay', $config);

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
