# Cookbook

Practical recipes for common payment scenarios.

## Basic Payment with Error Handling

```php
use Eram\Pardakht\Pardakht;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Money\Amount;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\ConnectionException;

$pardakht = new Pardakht();
$gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));

try {
    $response = $gateway->purchase(new PurchaseRequest(
        amount: Amount::fromToman(25_000),
        callbackUrl: 'https://example.com/payment/callback',
        orderId: 'INV-2024-001',
        description: 'Order #001',
        mobile: '09123456789',
    ));

    // Save $response->getReferenceId() to your database
    header('Location: ' . $response->getUrl());
    exit;
} catch (ConnectionException $e) {
    // Network error — retry or show maintenance page
    log_error('Gateway unreachable: ' . $e->getMessage());
} catch (GatewayException $e) {
    // Gateway rejected the request
    log_error(sprintf(
        'Gateway %s error [%s]: %s',
        $e->getGatewayName(),
        $e->getErrorCode(),
        $e->getMessage(),
    ));
}
```

## Verification Callback Handler

```php
use Eram\Pardakht\Contracts\SupportsSettlement;
use Eram\Pardakht\Exception\VerificationException;

// Re-create the same gateway instance
$gateway = $pardakht->create('mellat', new MellatConfig(
    terminalId: 123456,
    username: 'user',
    password: 'pass',
));

try {
    $transaction = $gateway->verify(); // Auto-detects $_POST/$_GET

    // Settlement step (Mellat, Parsian only)
    if ($gateway instanceof SupportsSettlement) {
        $transaction = $gateway->settle($transaction);
    }

    // Payment successful — update your order
    update_order($transaction->getReferenceId(), [
        'status' => 'paid',
        'tracking_code' => $transaction->getTrackingCode(),
        'card_number' => $transaction->getCardNumber(),
        'amount_rials' => $transaction->getAmount()->inRials(),
    ]);
} catch (VerificationException $e) {
    // Payment failed or was cancelled
    mark_order_failed($e->getGatewayName(), $e->getErrorCode());
}
```

## Switching Gateways via Config

```php
// Store gateway config in your application config
$gatewayConfigs = [
    'zarinpal' => new ZarinpalConfig(merchantId: 'xxx'),
    'idpay' => new IDPayConfig(apiKey: 'yyy'),
    'mellat' => new MellatConfig(terminalId: 123, username: 'u', password: 'p'),
];

// Switch gateway with a single config change
$activeGateway = 'zarinpal'; // Change this to switch
$gateway = $pardakht->create($activeGateway, $gatewayConfigs[$activeGateway]);
```

## Handling Both Redirect Types

```php
$response = $gateway->purchase($request);

if ($response->isPost()) {
    // SOAP gateways (Mellat, Saman, Parsian) need an auto-submit form
    echo $response->renderAutoSubmitForm('Redirecting to bank...');
} else {
    // REST gateways — simple redirect
    header('Location: ' . $response->getUrl());
    exit;
}
```

## Custom HTTP Client (Guzzle Example)

```php
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\HttpResponse;
use Eram\Pardakht\Exception\ConnectionException;
use GuzzleHttp\Client;

class GuzzleHttpClient implements HttpClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
    }

    public function postJson(string $url, string $body, array $headers = []): HttpResponse
    {
        try {
            $response = $this->client->post($url, [
                'body' => $body,
                'headers' => array_merge(
                    ['Content-Type' => 'application/json'],
                    $headers,
                ),
            ]);

            return new HttpResponse(
                statusCode: $response->getStatusCode(),
                body: (string) $response->getBody(),
                headers: array_change_key_case(
                    array_map(fn ($v) => $v[0] ?? '', $response->getHeaders()),
                ),
            );
        } catch (\Throwable $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }
}

$pardakht = new Pardakht(httpClient: new GuzzleHttpClient());
```

## Logging All Requests

```php
use Eram\Pardakht\Http\Logger;

class FileLogger implements Logger
{
    public function debug(string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE),
        );
        file_put_contents('/var/log/pardakht.log', $line, FILE_APPEND);
    }
}

$pardakht = new Pardakht(logger: new FileLogger());
```

## Event Listeners

```php
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Event\PurchaseInitiated;
use Eram\Pardakht\Event\PaymentVerified;
use Eram\Pardakht\Event\PaymentFailed;

class PaymentEventDispatcher implements EventDispatcher
{
    public function dispatch(object $event): object
    {
        match (true) {
            $event instanceof PurchaseInitiated => $this->onPurchase($event),
            $event instanceof PaymentVerified => $this->onVerified($event),
            $event instanceof PaymentFailed => $this->onFailed($event),
            default => null,
        };

        return $event;
    }

    private function onPurchase(PurchaseInitiated $event): void
    {
        // Track purchase attempts
    }

    private function onVerified(PaymentVerified $event): void
    {
        // Send confirmation SMS
    }

    private function onFailed(PaymentFailed $event): void
    {
        // Alert operations team
    }
}

$pardakht = new Pardakht(eventDispatcher: new PaymentEventDispatcher());
```

## Validating Card and Sheba Numbers

```php
use Eram\Pardakht\Banking\CardNumber;
use Eram\Pardakht\Banking\Sheba;

// Quick validation
$valid = CardNumber::isValid('6037991234567890'); // true or false
$valid = Sheba::isValid('IR062960000000100324200001');

// Full value object with bank detection
$card = new CardNumber('6037-9912-3456-7890');
echo $card->bankName();    // "ملی"
echo $card->masked();      // "603799******7890"
echo $card->formatted();   // "6037-9912-3456-7890"

$sheba = new Sheba('IR062960000000100324200001');
echo $sheba->bankName();   // "ملت"
echo $sheba->formatted();  // "IR06 2960 0000 0010 0324 2000 01"
```
