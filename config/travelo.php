<?php

return [
    /*
     | Add these values to the consumer application's .env:
     |
     | TRAVELO_API_URL=http://localhost:8000
     | TRAVELO_PARTNER_CLIENT_ID=partner_client_id
     | TRAVELO_PARTNER_SECRET=partner_secret
     | TRAVELO_DEFAULT_CURRENCY=USD
     | TRAVELO_API_TIMEOUT=10
     | TRAVELO_WEBHOOK_MAX_SKEW_SECONDS=300
     | TRAVELO_INTEGRATION_NAME=be-travelo-partner
     | TRAVELO_INTEGRATION_VERSION=dev
     | TRAVELO_HOLD_TTL_MINUTES=30
     |
     | TRAVELO_API_URL, TRAVELO_PARTNER_CLIENT_ID, and TRAVELO_PARTNER_SECRET
     | are required. Keep TRAVELO_PARTNER_SECRET out of source control.
     */

    /*
     | Origin only — a trailing /api is stripped. Paths are appended as travelo-api
     | sees them, so the signed path matches the server's own.
     */
    'base_url' => env('TRAVELO_API_URL'),

    /*
     | The secret signs outbound API calls AND verifies inbound webhooks. Rotating
     | it rotates both directions — coordinate with travelo-api.
     */
    'client_id' => env('TRAVELO_PARTNER_CLIENT_ID'),
    'secret' => env('TRAVELO_PARTNER_SECRET'),

    'currency' => env('TRAVELO_DEFAULT_CURRENCY', 'USD'),
    'timeout' => (float) env('TRAVELO_API_TIMEOUT', 10),

    /*
     | Customer accounts. travelo-api used to create the customer's account and
     | email them the password on every partner booking; that now belongs here, on
     | the app the customer actually logs into.
     |
     |   create_customer      Look up / create a local user from the booking's email
     |                        so a guest checkout leaves an account behind. Off means
     |                        guest bookings stay owner-less, as before.
     |   user_model           The Eloquent model to create. Null resolves the app's
     |                        own auth model — the table this SDK's users migration
     |                        matches — so a consumer normally leaves it alone.
     */
    'account' => [
        'create_customer' => (bool) env('TRAVELO_CREATE_CUSTOMER', true),
        'user_model' => env('TRAVELO_USER_MODEL'),
    ],

    /*
     | Signatures are rejected outside this skew (travelo-api's own limit is 300s).
     | Keep NTP running or every call 401s.
     */
    'webhook_max_skew_seconds' => (int) env('TRAVELO_WEBHOOK_MAX_SKEW_SECONDS', 300),

    /*
     | Sent as X-Travelo-Integration-* so upstream can tell partners apart in logs.
     */
    'integration' => [
        'name' => env('TRAVELO_INTEGRATION_NAME', env('APP_NAME', 'laravel')),
        'version' => env('TRAVELO_INTEGRATION_VERSION', 'dev'),
    ],

    /*
     | Mirrors config('partner.hold_ttl_minutes') on travelo-api: how long a created
     | booking keeps its seats before upstream releases them. Consumers that take
     | payment must close their payment window before this. If travelo-api changes
     | PARTNER_HOLD_TTL_MINUTES, change this with it.
     */
    'hold_ttl_minutes' => (int) env('TRAVELO_HOLD_TTL_MINUTES', 30),

    /*
     | Controller mode — the SDK registers the endpoints itself, so a consumer writes
     | no controllers. Off by default: installing a package must never open HTTP
     | routes on someone's app without them asking.
     |
     |   TRAVELO_CONTROLLER_MODE=true
     |   TRAVELO_ROUTE_PREFIX=travelo
     |
     | `auth_middleware` guards the per-customer booking routes. Leaving it empty
     | makes `GET /bookings` list every booking the partner has — set it.
     |
     | Ownership is resolved with Auth::id() by default. To use another guard:
     |
     |   BookingOwner::resolveUsing(fn () => Auth::guard('web')->id());
     |
     | (in a service provider — not here, because config may be cached and
     | `config:cache` cannot serialise a closure.)
     */
    'controller_mode' => [
        'enabled' => (bool) env('TRAVELO_CONTROLLER_MODE', false),
        'prefix' => env('TRAVELO_ROUTE_PREFIX', 'travelo'),
        'middleware' => ['api'],
        'auth_middleware' => ['auth:sanctum'],
    ],
];
