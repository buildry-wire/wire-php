# buildry-wire/wire-php

Official PHP SDK for the [Wire](https://wire.mn) payment API. Dependency-free
(uses only `ext-curl` and `ext-json`); requires PHP 8.1+.

Full documentation: [docs.wire.mn](https://docs.wire.mn).

## Install

```bash
composer require buildry-wire/wire-php
```

## Quickstart

```php
use BuildryWire\Wire\Wire;

$wire = new Wire('your-api-key');

// Create a payment intent. Amounts are in minor units (MNT integer).
// In test mode use the "sandbox" operator id; in live mode use the
// operator ids enabled on your account.
$pi = $wire->paymentIntents->create([
    'amount' => 50000,
    'currency' => 'MNT',
    'allowed_operators' => ['sandbox'],
]);

echo $pi->id, ' ', $pi->status, PHP_EOL;

// Confirm it.
$pi = $wire->paymentIntents->confirm($pi->id, [
    'return_url' => 'https://example.com/return',
]);
```

## Auto-pagination

List methods return a lazy iterator that follows `has_more` automatically:

```php
foreach ($wire->charges->list(limit: 50) as $charge) {
    echo $charge->id, PHP_EOL;
}
```

## Webhook verification

Verify the **raw** request body before parsing it. Verification fails closed.

```php
use BuildryWire\Wire\Webhooks;
use BuildryWire\Wire\SignatureVerificationError;

$rawBody = file_get_contents('php://input');
$header = $_SERVER['HTTP_WIREPAYMENT_SIGNATURE'] ?? '';

try {
    $event = (new Webhooks())->verify($rawBody, $header, $endpointSecret);
    // handle $event->type
} catch (SignatureVerificationError $e) {
    http_response_code(400);
    exit;
}
```

## Errors

```php
use BuildryWire\Wire\WireError;
use BuildryWire\Wire\WireConnectionError;

try {
    $wire->paymentIntents->create(['amount' => -1]);
} catch (WireConnectionError $e) {
    // network failure or timeout (retries already exhausted)
} catch (WireError $e) {
    echo $e->code, ' ', $e->requestId, ' ', $e->statusCode, PHP_EOL;
}
```

## Configuration

```php
$wire = new Wire('your-api-key', [
    'baseURL'    => 'https://api.wire.mn', // default
    'timeout'    => 30.0,                  // seconds
    'maxRetries' => 2,                     // retried on 429/5xx/network
    'backoffMs'  => 500,                   // base backoff
]);
```

Requests are authenticated with a bearer token, every `POST` carries an
`Idempotency-Key` (generated automatically if you do not supply one and reused
across retries), and `429`/`5xx`/network errors are retried with exponential
backoff and jitter, honoring `Retry-After`.

## License

MIT
