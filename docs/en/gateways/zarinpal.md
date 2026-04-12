# Zarinpal

> The most popular Iranian online payment gateway. REST-based with sandbox support.

## Configuration

```php
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;

$config = new ZarinpalConfig(
    merchantId: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    sandbox: false, // Set true for testing
);
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `merchantId` | `string` | Yes | — | Your Zarinpal merchant ID |
| `sandbox` | `bool` | No | `false` | Use sandbox environment |

## Purchase

```php
$gateway = $pardakht->create('zarinpal', $config);

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/callback',
    orderId: 'ORDER-123',
    description: 'Premium plan',
    mobile: '09123456789',
    email: 'user@example.com',
));

// GET redirect
header('Location: ' . $response->getUrl());
```

## Verify

```php
$transaction = $gateway->verify();

$transaction->getReferenceId();  // Authority
$transaction->getTrackingCode(); // RefID
$transaction->getCardNumber();   // Masked card number
```

## Sandbox

Set `sandbox: true` to use Zarinpal's test environment. No real money is charged.

```php
$config = new ZarinpalConfig(
    merchantId: 'test',
    sandbox: true,
);
```

## Settlement

Not required. Payments are settled automatically.

## Notes

- Amount is sent in Rials (converted automatically from `Amount`)
- Mobile and email are optional but improve UX on the payment page
- The `description` field is shown to the user on the payment page
