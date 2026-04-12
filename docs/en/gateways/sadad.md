# Sadad (Bank Melli)

> Bank Melli Iran's payment gateway. REST-based.

## Configuration

```php
use Eram\Pardakht\Gateway\Sadad\SadadConfig;

$config = new SadadConfig(
    merchantId: 'your-merchant-id',
    terminalId: 'your-terminal-id',
    terminalKey: 'your-terminal-key',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `merchantId` | `string` | Yes | Merchant ID |
| `terminalId` | `string` | Yes | Terminal ID |
| `terminalKey` | `string` | Yes | Terminal encryption key |

## Purchase

```php
$gateway = $pardakht->create('sadad', $config);

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
- Uses `ext-openssl` for signing requests
- Amount is sent in Rials
