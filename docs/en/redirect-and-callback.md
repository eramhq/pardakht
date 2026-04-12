# Redirect & Callback

After a successful `purchase()` call, you need to redirect the user to the gateway's payment page and handle the callback when they return.

## RedirectResponse

The `purchase()` method returns a `RedirectResponse` with two possible redirect types:

### GET Redirect (REST Gateways)

REST gateways (Zarinpal, IDPay, Zibal, etc.) provide a URL for a simple HTTP redirect:

```php
$response = $gateway->purchase($request);

header('Location: ' . $response->getUrl());
exit;
```

### POST Form (SOAP Gateways)

SOAP bank gateways (Mellat, Saman, Parsian) require submitting a POST form with hidden fields to the bank's payment page:

```php
$response = $gateway->purchase($request);

echo $response->renderAutoSubmitForm();
```

The generated HTML includes a form with hidden fields and a JavaScript snippet that auto-submits it. A `<noscript>` fallback button is included for users without JavaScript.

### Handling Both Types

```php
$response = $gateway->purchase($request);

if ($response->isPost()) {
    echo $response->renderAutoSubmitForm('Redirecting to bank...');
} else {
    header('Location: ' . $response->getUrl());
    exit;
}
```

## RedirectResponse API

```php
$response->getUrl();         // Gateway payment page URL
$response->getMethod();      // "GET" or "POST"
$response->getReferenceId(); // Gateway reference (Authority, RefId, etc.)
$response->getFormData();    // POST fields (empty for GET)
$response->isPost();         // true for SOAP gateways

$response->renderAutoSubmitForm(
    string $submitText = 'Redirecting...'
): string;
```

### Reference ID

The `referenceId` returned by `purchase()` is the gateway's identifier for this payment attempt. Save it to your database — you'll need it to correlate the callback.

## Handling the Callback

After the user completes (or cancels) the payment, the gateway redirects them back to your `callbackUrl`.

### Auto-Detection

By default, `verify()` reads callback data from `$_POST` or `$_GET` automatically:

```php
$transaction = $gateway->verify();
```

### Explicit Data

In frameworks where superglobals aren't used directly, pass the callback data explicitly:

```php
// Laravel
$transaction = $gateway->verify($request->all());

// Symfony
$transaction = $gateway->verify($request->query->all());
```

### Transaction Result

After successful verification:

```php
$transaction->getId();           // TransactionId value object
$transaction->getGatewayName();  // "zarinpal"
$transaction->getAmount();       // Amount value object
$transaction->getStatus();       // TransactionStatus::Verified
$transaction->getReferenceId();  // Gateway reference
$transaction->getTrackingCode(); // User-facing tracking code (or null)
$transaction->getCardNumber();   // Payer's card number (or null)
$transaction->getExtra();        // Additional gateway-specific data
```

## Complete Flow Example

```php
// === Purchase page ===
$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));

$response = $gateway->purchase(new PurchaseRequest(
    amount: Amount::fromToman(50_000),
    callbackUrl: 'https://example.com/payment/callback',
    orderId: 'ORDER-123',
));

// Save to database
save_payment($response->getReferenceId(), 'ORDER-123', 'pending');

// Redirect
header('Location: ' . $response->getUrl());
exit;

// === Callback handler ===
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));

try {
    $transaction = $gateway->verify();
    update_payment($transaction->getReferenceId(), 'verified');
    show_success_page($transaction->getTrackingCode());
} catch (VerificationException $e) {
    update_payment_failed($e->getErrorCode());
    show_failure_page();
}
```
