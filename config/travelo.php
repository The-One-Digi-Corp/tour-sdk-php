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
];
