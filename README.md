# Travelo Tour SDK for PHP

PHP/Laravel SDK for the Travelo Partner API. It provides a signed HTTP client,
typed API entry points for tour catalog and partner booking flows, Laravel
service-provider wiring, optional catalog proxy routes, and webhook signature
verification.

This SDK is the PHP sibling of the TypeScript `tour-sdk`; both clients target the
same Partner API contract served by `travelo-api`.

## Status

Current package version: `0.1.0`

Runtime support:

- PHP `^8.1`
- Guzzle `^7.2`
- Laravel components `^10.0|^11.0|^12.0`

Development/test note: the currently installed PHPUnit dependency may require
PHP `>= 8.2`, depending on Composer resolution.

## What The SDK Does

- Signs every Partner API request with the Travelo HMAC scheme.
- Normalizes base URLs, including defensive stripping of a trailing `/api`.
- Adds SDK identity, request id, currency, locale, and integration headers.
- Unwraps Travelo's standard `{status, message, errors, data}` response envelope.
- Raises structured exceptions for API errors and transport failures.
- Provides tour catalog reads through `TourApi`.
- Provides partner booking actions through `BookingApi`.
- Provides typed SDK resources for common tour, booking, quote, calendar, and
  pagination responses.
- Generates OpenAPI-backed request and response DTOs under `src/Generated`.
- Supports idempotent booking creation via idempotency headers.
- Supports webhook verification for Travelo partner lifecycle events.
- Ships Laravel auto-discovery, config publishing, service-container bindings,
  facade support, webhook middleware, and opt-in read-only catalog proxy routes.
- Keeps contract coverage tests against the Partner API OpenAPI fixture.

## What The SDK Deliberately Does Not Do

- It does not manage partner-local payment tables.
- It does not expose or create `payment_histories`.
- It does not auto-register booking write routes in a Laravel consumer app.
- It does not call first-party checkout routes such as
  `/api/tours/bookings/checkout/{code}`.
- It does not decide whether money is settled. The consuming partner app owns
  payment collection and only calls `confirm()` after successful payment.

The rule of thumb is:

```text
travelo-api owns inventory and booking holds.
partner app owns customer payment and local payment history.
SDK only connects those two systems safely.
```

## Installation

There are two supported installation styles:

- Local path installation for monorepo development.
- Git/VCS installation for CI, staging, production, or external consumers.

### Local Path Installation

Use this when the consumer backend sits next to this SDK repo, for example:

```text
travelo/
  tour-sdk-php/
  be-travelo-partner/
```

In the consumer backend's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../tour-sdk-php",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "theonedigi/tour-sdk-php": "^0.1"
    }
}
```

Then run from the consumer backend:

```bash
composer update theonedigi/tour-sdk-php
```

Verify that Composer installed the SDK as a symlink:

```bash
readlink vendor/theonedigi/tour-sdk-php
```

Expected output in the monorepo layout above:

```text
../../../tour-sdk-php/
```

When developing locally:

- Changes to existing SDK PHP files are picked up immediately through the
  symlink.
- Run `composer dump-autoload` in the consumer backend after adding new SDK
  classes, namespaces, config files, or service-provider classes.
- Run `composer update theonedigi/tour-sdk-php` in the consumer backend after
  changing the SDK's `composer.json`, package version, dependencies, or Laravel
  auto-discovery metadata.
- The local SDK `composer.json` includes an explicit package version so the path
  repository can satisfy constraints such as `^0.1` even before a git tag exists.

### Git/VCS Installation

Use this when the SDK is consumed without a local sibling directory. The SDK must
be pushed to GitHub and tagged with a matching semver tag such as `v0.1.0`.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:The-One-Digi-Corp/tour-sdk-php.git"
        }
    ],
    "require": {
        "theonedigi/tour-sdk-php": "^0.1"
    }
}
```

Then run:

```bash
composer update theonedigi/tour-sdk-php
```

Notes:

- If a consumer declares both `path` and `vcs`, the `path` repository must be
  listed before `vcs`.
- A semver constraint such as `^0.1` needs a matching git tag when installing
  from `vcs`.
- `composer.json` includes an explicit package version because a path repository
  cannot infer one unless HEAD is exactly on a tag.

## Integration Checklist

Use this flow when adding the SDK to a new backend:

1. Install the package with Composer.
2. Configure `TRAVELO_API_URL`, `TRAVELO_PARTNER_CLIENT_ID`,
   `TRAVELO_PARTNER_SECRET`, default currency, timeout, and integration identity.
3. Inject `BookingApi` / `TourApi` from the Laravel container, or build
   `PartnerClient` manually in non-Laravel apps.
4. Use SDK request objects for outbound payloads. Prefer stable convenience
   wrappers in `src/Request`; use generated contract DTOs in
   `src/Generated/Request` when you need the exact OpenAPI surface.
5. Use SDK resource methods for inbound responses. These return generated
   contract resources from `src/Generated/Resource`; the classes in `src/Resource`
   are deprecated subclasses of them, kept so existing type hints resolve, plus
   the response envelopes the contract does not name. Persist `toArray()` as the
   raw audit snapshot when mirroring upstream bookings locally.
6. Generate and persist an idempotency key before calling `createResource()`.
   Retry unknown create outcomes with the same key.
7. Collect payment locally. After money is settled, call `confirm()` or
   `confirmResource()`. If local mirroring/payment fails, call `cancel()`.
8. Verify Travelo webhooks with `VerifyTraveloWebhook` or `WebhookVerifier`, and
   de-duplicate deliveries in the consumer app.
9. Add consumer-side tests that mock `BookingApi` / `TourApi` using SDK request
   and resource objects. This catches DTO drift at the integration boundary.

## Laravel Setup

The service provider is auto-discovered by Laravel.

Publish the config when you want an app-local copy:

```bash
php artisan vendor:publish --tag=travelo-config
```

Configure the consumer app:

```env
TRAVELO_API_URL=http://localhost:8000
TRAVELO_PARTNER_CLIENT_ID=partner_client_id
TRAVELO_PARTNER_SECRET=partner_secret
TRAVELO_DEFAULT_CURRENCY=USD
TRAVELO_API_TIMEOUT=10
TRAVELO_WEBHOOK_MAX_SKEW_SECONDS=300
TRAVELO_INTEGRATION_NAME=be-travelo-partner
TRAVELO_INTEGRATION_VERSION=dev
TRAVELO_HOLD_TTL_MINUTES=30
```

Environment variables:

| Variable | Required | Purpose |
| --- | --- | --- |
| `TRAVELO_API_URL` | Yes | Origin of `travelo-api`. A trailing `/api` is stripped. |
| `TRAVELO_PARTNER_CLIENT_ID` | Yes | Partner credential client id. |
| `TRAVELO_PARTNER_SECRET` | Yes | Shared secret for API request signing and webhook verification. |
| `TRAVELO_DEFAULT_CURRENCY` | No | Sent as `X-Currency` on SDK requests. Defaults to `USD`. |
| `TRAVELO_API_TIMEOUT` | No | HTTP timeout in seconds. Defaults to `10`. |
| `TRAVELO_WEBHOOK_MAX_SKEW_SECONDS` | No | Accepted webhook timestamp skew. Defaults to `300`. |
| `TRAVELO_INTEGRATION_NAME` | No | Sent as `X-Travelo-Integration-Name`. Defaults to `APP_NAME`. |
| `TRAVELO_INTEGRATION_VERSION` | No | Sent as `X-Travelo-Integration-Version`. Defaults to `dev`. |
| `TRAVELO_HOLD_TTL_MINUTES` | No | Consumer-side mirror of upstream booking hold TTL. Defaults to `30`. |

## Laravel Usage

Inject the API class you need:

```php
<?php

use TheOneDigi\TourSdk\Api\BookingApi;

final class PartnerCheckoutService
{
    public function __construct(
        private readonly BookingApi $bookings,
    ) {
    }

    public function quote(array $payload): array
    {
        return $this->bookings->quote($payload);
    }
}
```

Or use the facade:

```php
use TheOneDigi\TourSdk\Laravel\Facades\Travelo;

$tours = Travelo::tours()->list(['take' => 10]);
$booking = Travelo::bookings()->show('TB123ABC');
```

The package also binds these classes into the container:

- `TheOneDigi\TourSdk\PartnerClient`
- `TheOneDigi\TourSdk\Api\TourApi`
- `TheOneDigi\TourSdk\Api\BookingApi`
- `TheOneDigi\TourSdk\WebhookVerifier`

## Plain PHP Usage

```php
<?php

use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Request\BookingQuoteRequest;

$client = PartnerClient::fromConfig([
    'base_url' => 'http://localhost:8000',
    'client_id' => getenv('TRAVELO_PARTNER_CLIENT_ID'),
    'secret' => getenv('TRAVELO_PARTNER_SECRET'),
    'currency' => 'USD',
    'timeout' => 10,
    'integration_name' => 'custom-partner',
    'integration_version' => '1.0.0',
]);

$quote = $client->bookings()->quote(new BookingQuoteRequest(
    tourCode: 'IBTCARSGN3181',
    departureDate: '2026-08-01',
    adultQuantity: 2,
));
```

## Request Payloads

All request methods accept either the original array payload or an SDK request
object. Prefer request objects in third-party integrations because constructors
document required fields, give IDE autocomplete, serialize to the Partner API's
snake_case contract, and fail early for obvious invalid input.

```php
use TheOneDigi\TourSdk\Request\BookingApplicantRequest;
use TheOneDigi\TourSdk\Request\BookingCreateRequest;
use TheOneDigi\TourSdk\Request\BookingQuoteRequest;
use TheOneDigi\TourSdk\Request\TourCalendarDateRequest;

$quote = $client->bookings()->quoteResource(new BookingQuoteRequest(
    tourCode: 'IBTCARSGN3181',
    departureDate: '2026-08-01',
    adultQuantity: 2,
    childQuantity: 0,
    infantQuantity: 0,
    promotionCode: 'PROMO10',
));

$booking = $client->bookings()->createResource(
    new BookingCreateRequest(
        tourCode: 'IBTCARSGN3181',
        departureDate: '2026-08-01',
        name: 'Customer Name',
        phone: '0900000000',
        email: 'customer@example.com',
        applicants: [
            new BookingApplicantRequest(type: 1, fullName: 'Customer Name'),
        ],
        adultQuantity: 1,
        dialCode: '84',
        specialRequest: 'Vegetarian meal',
    ),
    $idempotencyKey,
);

$availability = $client->tours()->calendarByDateResource(
    'IBTCARSGN3181',
    new TourCalendarDateRequest(date: '2026-08-01', pax: 2),
);
```

Available request objects:

| Class | Use |
| --- | --- |
| `BookingQuoteRequest` | Price pre-check before holding inventory. |
| `BookingCreateRequest` | Create an upstream booking hold. |
| `BookingApplicantRequest` | Passenger/applicant item inside `BookingCreateRequest`. |
| `BookingUpdateApplicantRequest` | Update one applicant on an existing booking. |
| `BookingListRequest` | Filter/paginate partner booking history. |
| `CheckPromotionRequest` | Validate a promotion code. |
| `TourListRequest` | Search/filter catalog tours. |
| `TourTakeRequest` | Simple `take` query for featured/seasonal lists. |
| `SimilarToursRequest` | Query tours similar to a code or id. |
| `TourCalendarDateRequest` | Check availability for one departure date. |

Array payloads remain supported for backward compatibility and for newly added
Partner API fields before the SDK models them.

Generated request DTOs are available under
`TheOneDigi\TourSdk\Generated\Request`. They are generated from OpenAPI
operation ids and are useful when a consumer needs the full documented contract
instead of the smaller hand-written convenience wrappers:

```php
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutCreateBookingRequest;

$payload = new PartnerCheckoutCreateBookingRequest(
    tourCode: 'IBTCARSGN3181',
    departureDate: '2026-08-01',
    adultQuantity: 1,
    name: 'Customer Name',
    phone: '0900000000',
    email: 'customer@example.com',
    applicants: [
        ['type' => 1, 'full_name' => 'Customer Name'],
    ],
);

$booking = $client->bookings()->createResource($payload, $idempotencyKey);
```

## Editing Generated Resources

Run `composer generate:contract` to regenerate, `composer check:contract` to fail
on drift. Every file in `src/Generated/Resource` carries five marked regions:

```php
/* BEGIN MANUAL IMPORTS */   // preserved
/* END MANUAL IMPORTS */

    /* BEGIN AUTO FIELDS */      // rewritten from the contract every run
    /* END AUTO FIELDS */

    /* BEGIN MANUAL FIELDS */    // preserved
    /* END MANUAL FIELDS */

        /* BEGIN AUTO HYDRATION */   // rewritten every run
        /* END AUTO HYDRATION */

    /* BEGIN MANUAL HYDRATION */ // preserved — override hydrateManual() here
    /* END MANUAL HYDRATION */
```

Edit the MANUAL regions; a regeneration keeps them. Anything you write in an AUTO
region is lost on the next run.

Declaring a property in MANUAL FIELDS **replaces** the auto field of the same
name — the generator drops its own declaration, because PHP fatals on a
redeclared readonly property. That is how you correct the contract without
forking the generator.

Keep these regions small. The contract currently carries **98%** of the fields;
what is left in MANUAL is there for a stated reason, not by default:

| Resource | Manual field | Why |
| --- | --- | --- |
| `PartnerBookingResource` | `detail`, `applicants`, `refund` | Friendlier aliases over the contract's `tour_booking_*` keys, plus `payableAmount()`/`payableCurrency()`. Which amount to charge is not something a schema can say. `tour_booking_detail` is also still a bare `array` upstream — `array_merge()` in `PartnerBookingResource::toArray()` is opaque to Scramble. |
| `TourCalendarDetailResource` | `prices` | Upstream resolves them through `TourCalendarDetailPriceResource::collection(...)->resolve()`, which Scramble does not follow. |

When travelo-api starts describing one of these, delete it from MANUAL and let
AUTO take over — that is the direction of travel. `composer check:contract` tells
you the moment generated output stops matching what is committed.

### Why the contract goes wrong, and how it got fixed

Scramble infers from a resource's `toArray()`. It handles `$data['x'] = ...`
assignments fine; what defeats it is losing the *type* of what it is reading.
Everything the SDK used to hand-write traced back to one of these, and each was
fixed in travelo-api rather than papered over here:

| Symptom in the contract | Cause | Fix |
| --- | --- | --- |
| `id`/`status` typed `string` | Scramble cannot tell which model `$this->id` proxies to | `@mixin` on the resource |
| Whole schema is `array` with no properties | `parent::toArray()` | `@mixin` on the resource |
| Money typed `string` | value comes from `app(Service::class)->method()`, which Scramble will not resolve — a `@var` on the variable does not help either | cast at the point of use: `(float) $primary['total']` |
| A typed object collapses to `array` | `array_merge()` | unfixed; spreading instead emits an invalid schema (an unnamed `""` property), so `array_merge` stays and the SDK types the field by hand |

Before touching a resource in travelo-api, run its contract snapshot test — these
edits must change the documentation and nothing else:

    php vendor/bin/phpunit tests/Feature/PartnerContractSnapshotTest.php

## Tour Catalog API

All catalog methods are read-only and require the `tour:read` scope when scopes
are enabled on the credential.

```php
$client->tours()->list(['take' => 12, 'page' => 1]);
$client->tours()->references();
$client->tours()->seasonal(['take' => 6]);
$client->tours()->featured(['take' => 6]);
$client->tours()->similar(['tour_code' => 'IBTCARSGN3181']);
$client->tours()->show('IBTCARSGN3181');
$client->tours()->calendars('IBTCARSGN3181');
$client->tours()->calendarByDate('IBTCARSGN3181', ['date' => '2026-08-01', 'pax' => 2]);
$client->tours()->reviews(1);
$client->tours()->reviewImages(1);
```

## Booking API

Booking writes require the `tour:book` scope when scopes are enabled on the
credential.

```php
$client->bookings()->quote($payload);
$client->bookings()->create($payload, $idempotencyKey);
$client->bookings()->show('TB123ABC');
$client->bookings()->list(['status' => 1, 'page' => 1]);
$client->bookings()->checkPromotion(['code' => 'PROMO10']);
$client->bookings()->updateApplicant('TB123ABC', 7, ['full_name' => 'Nguyen Van A']);
$client->bookings()->confirm('TB123ABC');
$client->bookings()->cancel('TB123ABC');
```

Every array-returning booking method has a typed resource variant:

```php
$quote = $client->bookings()->quoteResource($payload);
$booking = $client->bookings()->createResource($payload, $idempotencyKey);
$booking = $client->bookings()->showResource('TB123ABC');
$bookings = $client->bookings()->listResources(['status' => 1]);
$booking = $client->bookings()->confirmResource('TB123ABC');
$booking = $client->bookings()->cancelResource('TB123ABC');
$booking = $client->bookings()->updateApplicantResource('TB123ABC', 7, [
    'full_name' => 'Nguyen Van A',
]);
```

Resource example:

```php
$booking = $client->bookings()->createResource($payload, $idempotencyKey);

$booking->orderCode;                 // "TB123ABC"
$booking->status;                    // 1
$booking->payableAmount();           // input_total
$booking->payableCurrency();         // input_currency
$booking->detail?->departureDate;
$booking->detail?->inputAdultPrice;
$booking->applicants[0]->fullName;

// Raw payload is still available for additive fields from travelo-api.
$booking->toArray();
$booking->get('new_future_field');
```

### Booking Flow

The Partner API uses a two-step booking model:

1. `quote()` calculates an authoritative pre-check price. It does not reserve
   seats.
2. `create()` reserves the seat upstream and returns a `PENDING_PAYMENT` booking.
3. The partner app mirrors the booking locally and collects payment through its
   own gateway.
4. After money is settled locally, the partner app calls `confirm()`.
5. If payment fails or local mirroring fails, the partner app calls `cancel()`.
6. If no confirmation arrives before the hold TTL, upstream expires the hold and
   releases the seats.

```php
use Illuminate\Support\Str;
use TheOneDigi\TourSdk\Exception\ApiException;
use TheOneDigi\TourSdk\Exception\TransportException;
use TheOneDigi\TourSdk\Request\BookingApplicantRequest;
use TheOneDigi\TourSdk\Request\BookingCreateRequest;

$payload = new BookingCreateRequest(
    tourCode: 'IBTCARSGN3181',
    departureDate: '2026-08-01',
    name: 'Customer Name',
    phone: '0900000000',
    email: 'customer@example.com',
    applicants: [
        new BookingApplicantRequest(type: 1, fullName: 'Customer Name'),
    ],
    adultQuantity: 1,
    childQuantity: 0,
    infantQuantity: 0,
    dialCode: '84',
);

$idempotencyKey = (string) Str::orderedUuid();

try {
    $booking = $client->bookings()->createResource($payload, $idempotencyKey);

    // Use these upstream order amount snapshots for your local invoice/payment.
    $orderCode = $booking->orderCode;
    $payableAmount = $booking->payableAmount();
    $payableCurrency = $booking->payableCurrency();

    // Your app creates its own local booking/payment rows here.
    // The SDK does not create payment_histories.

    // After your gateway confirms money is settled:
    $client->bookings()->confirm($orderCode);
} catch (ApiException $e) {
    if ($e->isConflict()) {
        // Sold out or invalid state.
    }

    throw $e;
} catch (TransportException $e) {
    // Unknown outcome. Retry create() with the SAME idempotency key.
    throw $e;
}
```

### Amounts And Payment Boundary

Use the amount snapshot returned by `create()` for local payment:

- `input_total`
- `input_sub_total`
- `input_discount`
- `input_cost`
- `input_currency`
- `input_currency_version`
- `input_currency_exchange_rate`
- `tour_booking_detail.input_*`

Do not use browser-submitted prices as the payable amount. Do not use `quote()`
as the final charge amount. Prices can change between quote and create.

`payment_histories` is not part of the SDK contract. If a partner app needs a
payment history table, it must create and own that table locally.

## Tour Resources

Catalog methods also have typed variants for the common response shapes:

```php
$tours = $client->tours()->listResources(['take' => 12]);
$tour = $client->tours()->showResource('IBTCARSGN3181');
$featured = $client->tours()->featuredResources(['take' => 6]);
$seasonal = $client->tours()->seasonalResources(['take' => 6]);
$similar = $client->tours()->similarResources(['tour_code' => 'IBTCARSGN3181']);
$calendars = $client->tours()->calendarsResources('IBTCARSGN3181');
$date = $client->tours()->calendarByDateResource('IBTCARSGN3181', [
    'date' => '2026-08-01',
    'pax' => 2,
]);
```

Resource example:

```php
$tour = $client->tours()->showResource('IBTCARSGN3181');

$tour->code;
$tour->name;
$tour->currency;
$tour->basePrice;
$tour->prices[0]->adultPrice;

$date->isAvailable;
$date->remainingSlots;
$date->tourPriceGroupId;
```

The array methods remain available for consumers that prefer raw payloads or need
fields not yet modeled by the SDK.

Generated response resources are available under
`TheOneDigi\TourSdk\Generated\Resource`. They mirror OpenAPI component schemas
and keep the original payload through `toArray()` / `get()`:

```php
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingResource;

$resource = new PartnerBookingResource($booking->toArray());

$resource->orderCode;
$resource->status;
$resource->get('future_field_from_api');
```

## Contract And DTO Evolution

The Partner API contract served by `travelo-api` is the source of truth. The SDK
is a typed client for that contract, so API changes and SDK changes should move
together deliberately.

The SDK has two DTO layers:

- `src/Generated/Request` and `src/Generated/Resource` are generated from the
  OpenAPI fixture. Do not edit these files by hand.
- `src/Request` and `src/Resource` are stable convenience wrappers with nicer
  names, guardrails, and helper methods for common integration flows.

Generate the contract DTOs after refreshing the OpenAPI fixture:

```bash
curl -s http://localhost:8000/docs/partner/api.json \
  -o tests/fixtures/partner-api.openapi.json

composer generate:contract
composer check:contract
```

### Compatibility Rules

- Generated request DTOs describe the current documented request contract.
- Stable hand-written request DTOs may expose only the fields we want to support
  as the recommended SDK API and can fail early for obvious invalid input.
- Response resources expose fields as typed properties but also keep the full raw
  payload through `toArray()` and `get()`. Additive response fields do not break
  old SDK consumers.
- Array payloads remain accepted by API methods as a temporary escape hatch.
- Removing or renaming request fields is a breaking change unless `travelo-api`
  accepts the old and new field names during a transition window.
- Adding a new required request field is also a breaking change unless
  `travelo-api` can default or infer it for old clients.

### Change Examples

At time `T1`, the API accepts field `A1`.

```text
T1 request: A1
SDK DTO:    A1
```

At time `T2`, the API adds optional field `A2`.

```text
T2 request: A1, A2
SDK DTO:    A1, A2 = null
```

Safe rollout:

1. Deploy `travelo-api` with `A2` optional.
2. Refresh `tests/fixtures/partner-api.openapi.json`.
3. Run `composer generate:contract`.
4. Inspect the generated diff under `src/Generated`.
5. Add `A2` to a stable wrapper in `src/Request` or `src/Resource` only when it
   should become part of the recommended SDK API.
6. Update README examples if the field is important for integrators.
7. Run `composer check:contract` and the SDK test suite, then release/tag the
   SDK.
8. Upgrade consumer backends when they need to send/read `A2`.

Old consumers keep working because `A1` is still accepted and `A2` is optional.
Consumers that need `A2` can use the generated DTO after the SDK release. If a
consumer cannot upgrade immediately, array payloads remain an escape hatch.

At time `T3`, the API changes from `A2` to `A3`.

```text
T3 request: A1, A3
old SDK:    A1, A2
```

This is not safe as a sudden change. Use a transition:

1. Deploy `travelo-api` accepting both `A2` and `A3`, marking `A2` deprecated.
2. Refresh the OpenAPI fixture and run `composer generate:contract`.
3. Release the SDK with `A3` and keep `A2` in stable wrappers only if the
   transition requires it.
4. Upgrade consumer backends to send `A3`.
5. After all supported consumers are upgraded, remove `A2` from the API and then
   from the SDK in a breaking release.

If a breaking API change cannot support old clients, bump the SDK version
accordingly, update the OpenAPI fixture, and make the API return clear `422`
validation errors so old consumers fail loudly instead of creating bad bookings.

### SDK Update Checklist

When `travelo-api` changes the Partner API contract:

1. Refresh `tests/fixtures/partner-api.openapi.json` from
   `/docs/partner/api.json`.
2. Run `composer generate:contract`.
3. Run `composer check:contract` to ensure generated files are committed and in
   sync with the fixture.
4. Update endpoint methods in `src/Api` if paths/actions changed.
5. Update stable convenience request DTOs in `src/Request` only when the field
   should be part of the recommended SDK API.
6. Update stable convenience resources in `src/Resource` only when helper
   properties or methods are needed.
7. Keep new request fields nullable/defaulted when the API field is optional.
8. Add or update tests for payload serialization, generated DTO sync, and
   resource mapping.
9. Update README examples and the request/resource tables.
10. Run the full PHPUnit suite.
11. Tag/release the SDK and upgrade consumer backends.

Contract drift should be caught by:

- `ContractCoverageTest` for endpoint/path coverage.
- `GeneratedDtoTest` for generated request/resource drift.
- Request payload tests for serialized field names.
- Resource mapping tests for typed response fields.
- Consumer backend tests that mock SDK APIs with request/resource objects.

## Laravel Catalog Proxy

The SDK can register opt-in, read-only catalog proxy routes:

```php
use Illuminate\Support\Facades\Route;

Route::traveloCatalog();
```

This creates a GET route under `catalog/{path}` and only allows `tours` paths.

Custom prefix and middleware:

```php
Route::traveloCatalog('catalog', ['throttle:60,1']);
Route::traveloCatalog('v2/content', ['auth:sanctum']);
```

The SDK intentionally does not register booking write routes. Booking writes and
payment policy belong in the consuming app.

## Webhooks

`travelo-api` sends partner lifecycle events to the partner's configured
`webhook_url`.

Known events:

- `booking.created`
- `booking.confirmed`
- `booking.expired`
- `booking.cancelled`
- `booking.completed`
- `booking.refunded`

The webhook signature is:

```text
hash_hmac('sha256', raw_json_body, partner_secret)
```

The signature is delivered in `X-Partner-Signature`, with
`X-Partner-Timestamp` for replay protection.

### Middleware Usage

```php
use Illuminate\Support\Facades\Route;
use TheOneDigi\TourSdk\Laravel\Http\Middleware\VerifyTraveloWebhook;

Route::post('/webhooks/travelo', WebhookController::class)
    ->middleware(VerifyTraveloWebhook::class);
```

### Manual Verification

```php
use TheOneDigi\TourSdk\WebhookVerifier;

$verifier = new WebhookVerifier($secret);

$valid = $verifier->isValid(
    $request->getContent(),
    (string) $request->header(WebhookVerifier::HEADER_SIGNATURE),
    $request->header(WebhookVerifier::HEADER_TIMESTAMP),
);

if (! $valid) {
    abort(401);
}

$payload = json_decode($request->getContent(), true);
$dedupeKey = WebhookVerifier::eventHash($payload);
```

Always verify the raw request body. Re-encoding decoded JSON can change the byte
string and break signature verification.

The webhook payload does not carry a unique event id. Use
`WebhookVerifier::eventHash()` or your own unique inbox key to de-duplicate.
Return `200` quickly and process asynchronously.

## Error Handling

`ApiException` means `travelo-api` returned an HTTP error response.

```php
try {
    $client->bookings()->create($payload, $idempotencyKey);
} catch (\TheOneDigi\TourSdk\Exception\ApiException $e) {
    $status = $e->status();
    $payload = $e->payload();
    $correlationId = $e->correlationId();

    if ($e->isAuthFailure()) {
        // 401: bad signature, stale timestamp, inactive credential.
    }

    if ($e->isScopeFailure()) {
        // 403: credential missing tour:read or tour:book.
    }

    if ($e->isConflict()) {
        // 409: sold out or invalid state.
    }
}
```

`TransportException` means no HTTP response was received. Treat write outcomes as
unknown. For `create()`, retry with the same idempotency key. The exception
also exposes `method`, `path`, and `url`, so consumer backends can log the
network target and return a clear `503 Service Unavailable` response.

`ConfigurationException` means required client config is missing.

## Security Notes

- Keep `TRAVELO_PARTNER_SECRET` private.
- The same secret signs outbound SDK calls and verifies inbound webhooks.
- Keep server time synced. Requests outside the allowed skew are rejected.
- Signatures cover the exact raw JSON bytes sent by the SDK.
- Query strings are sent normally but are not included in the signed path.
- Use the same idempotency key for retries after timeout or connection failure.

## Testing

Install dependencies:

```bash
composer install
```

Run tests:

```bash
php vendor/bin/phpunit
```

If the resolved PHPUnit version requires PHP `>= 8.2`, run with a newer PHP
binary:

```bash
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit
```

Useful focused tests:

```bash
php vendor/bin/phpunit --filter=PartnerClientTest
php vendor/bin/phpunit --filter=PartnerSignerTest
php vendor/bin/phpunit --filter=WebhookVerifierTest
php vendor/bin/phpunit --filter=ContractCoverageTest
php vendor/bin/phpunit --filter=GeneratedDtoTest
php vendor/bin/phpunit --filter=RequestPayloadTest
php vendor/bin/phpunit --filter=ResourceMappingTest
```

Generate or verify contract DTOs:

```bash
composer generate:contract
composer check:contract
```

Fixtures:

- `tests/fixtures/partner-hmac-fixtures.json` keeps HMAC signing aligned with
  the TypeScript SDK.
- `tests/fixtures/partner-api.openapi.json` keeps SDK endpoint coverage and
  generated DTOs aligned with the Partner API contract.

## Development Checklist

When adding or changing Partner API endpoints:

1. Refresh `tests/fixtures/partner-api.openapi.json` from `travelo-api` when the
   public contract changes.
2. Run `composer generate:contract`.
3. Update the matching API class in `src/Api` if the path/action changed.
4. Update stable hand-written request DTOs/resources only when the field should
   be part of the recommended SDK API.
5. Update contract coverage, generated DTO, request payload, and resource
   mapping tests.
6. Keep payment-local fields out of the SDK-facing booking schema.
7. Run `composer check:contract` and the PHPUnit suite.

## License

Proprietary. Internal use by The One Digi and approved partners only.
