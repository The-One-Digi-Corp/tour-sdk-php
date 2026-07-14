# tour-sdk-php

PHP client for the Travelo Partner API. Sibling of the TypeScript `tour-sdk`;
both target the same contract served by `travelo-api`.

## Install

Consumers sitting next to this repo (e.g. `be-partner`) resolve it from disk;
everyone else resolves it from git. Declare both: `path` wins when the sibling
directory exists, and on CI/servers where it does not, that repository yields
nothing and Composer falls through to `vcs`.

```json
{
    "repositories": [
        { "type": "path", "url": "../tour-sdk-php", "options": { "symlink": true } },
        { "type": "vcs",  "url": "git@github.com:The-One-Digi-Corp/tour-sdk-php.git" }
    ],
    "require": { "theonedigi/tour-sdk-php": "^0.1" }
}
```

Two things that will waste an afternoon otherwise:

- **`path` must come first.** Repositories are canonical by priority order: if the
  `vcs` repo is listed first and offers only `dev-*` branches, it *blocks* the
  lower-priority `path` repo instead of deferring to it, and the install fails with
  "does not match your constraint". Note `composer config repositories.x …`
  **prepends**, so adding the two in reading order gets you the broken order.
- **`vcs` only works once a matching tag exists.** A constraint like `^0.1` cannot be
  satisfied by branches. Tag `v0.1.0` on `main` before any consumer relies on the
  `vcs` path; until then only machines with the sibling directory can install.

`composer.json` carries an explicit `version` because the `path` repository cannot
infer one unless HEAD sits exactly on a tag. Bump it together with each tag.

## Usage

```php
use TheOneDigi\TourSdk\PartnerClient;

$client = PartnerClient::fromConfig([
    'base_url'  => 'http://localhost:8000', // origin only; a trailing /api is stripped
    'client_id' => env('TRAVELO_PARTNER_CLIENT_ID'),
    'secret'    => env('TRAVELO_PARTNER_SECRET'),
    'currency'  => 'USD',
]);

$quote = $client->bookings()->quote([
    'tour_code'      => 'T-1',
    'departure_date' => '2026-08-01',
    'adult_quantity' => 2,
]);

// Persist $idempotencyKey BEFORE this call — see below.
$booking = $client->bookings()->create($payload, $idempotencyKey);
$client->bookings()->confirm($booking['order_code']);
```

Catalog reads have no dedicated resource; call them generically:

```php
$tours = $client->get('api/partner/tours', ['take' => 10]);
```

## Things that will bite you

- **Two-step booking.** `create()` only reserves the slot (`PENDING_PAYMENT`) and
  starts a hold TTL (30 min by default, `PARTNER_HOLD_TTL_MINUTES` on the server).
  Collect payment on your side, then `confirm()`. Unconfirmed holds are expired
  server-side and their seats released.
- **Charge the amount from `create()`, not from `quote()`.** Prices can move
  between the two calls. `quote()` is a fail-fast pre-check.
- **`TransportException` means UNKNOWN, not failed.** The write may have landed.
  Retry with the same idempotency key — never generate a new one on retry.
- **Clock skew.** travelo-api rejects signatures more than 300s off. Keep NTP on.
- **Scopes.** Reads need `tour:read`, writes need `tour:book`. A credential with
  no scopes set is unrestricted; one with a non-empty list is enforced.

## Webhooks

`travelo-api` posts booking lifecycle events (`booking.created`, `booking.confirmed`,
`booking.expired`, `booking.cancelled`, `booking.completed`, `booking.refunded`) to
the partner's configured `webhook_url`, signed with **the same secret** as the API
credential.

```php
use TheOneDigi\TourSdk\WebhookVerifier;

$verifier = new WebhookVerifier($secret);

// Always the RAW body — re-encoding a decoded array breaks verification.
if (! $verifier->isValid($rawBody, $request->header('X-Partner-Signature'), $request->header('X-Partner-Timestamp'))) {
    abort(401);
}

$hash = WebhookVerifier::eventHash(json_decode($rawBody, true));
```

The payload carries **no unique event id**, so de-duplicate on `eventHash()`.
Deliveries time out after 10s server-side: record the event, return 200, process
asynchronously.

## Tests

```bash
composer install
vendor/bin/phpunit
```

`tests/fixtures/partner-hmac-fixtures.json` is copied from
`tour-sdk/fixtures/hmac/`. It pins signature conformance with the TypeScript SDK —
if `PartnerAuthService` in travelo-api ever changes the signing scheme, re-copy the
regenerated fixture and both clients must still agree.
