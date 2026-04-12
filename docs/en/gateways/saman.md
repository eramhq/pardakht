# Saman (Sep)

> Saman Electronic Payment gateway. SOAP-based, no settlement required.

## Configuration

```php
use Eram\Pardakht\Gateway\Saman\SamanConfig;

$config = new SamanConfig(
    merchantId: 'your-merchant-id',
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `merchantId` | `string` | Yes | Merchant ID from Saman bank |

## Purchase

```php
$gateway = $pardakht->create('saman', $config);

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

## Settlement

Not required. Payments are settled automatically.

## Notes

- SOAP-based — requires `ext-soap`
- Uses POST form redirect (not GET)
- Amount is sent in Rials
