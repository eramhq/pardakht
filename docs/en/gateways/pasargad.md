# Pasargad

> Bank Pasargad's payment gateway. REST-based with RSA signing.

## Configuration

```php
use Eram\Pardakht\Gateway\Pasargad\PasargadConfig;

$config = new PasargadConfig(
    merchantCode: 'your-merchant-code',
    terminalCode: 'your-terminal-code',
    privateKey: file_get_contents('/path/to/private-key.pem'),
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `merchantCode` | `string` | Yes | Merchant code from Pasargad |
| `terminalCode` | `string` | Yes | Terminal code |
| `privateKey` | `string` | Yes | RSA private key (PEM format) |

## Purchase

```php
$gateway = $pardakht->create('pasargad', $config);

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
- Requires RSA private key for request signing (`ext-openssl`)
- Amount is sent in Rials
