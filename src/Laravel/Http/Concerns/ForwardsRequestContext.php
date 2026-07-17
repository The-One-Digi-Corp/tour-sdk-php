<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Concerns;

use Illuminate\Http\Request;

/**
 * Passes the caller's currency and language on to travelo-api.
 *
 * PartnerClient builds `X-Currency` and `Accept-Language` from its own config,
 * because it is framework-agnostic and a queue job has no request to read. That
 * default is right for SDK mode and wrong for controller mode: here the caller is
 * a shopper, and travelo-api prices and localises off exactly these two headers.
 * Without this, a visitor switching to VND kept seeing USD — silently, because
 * every price still rendered, just in the wrong currency.
 *
 * Per-call headers override PartnerClient's config defaults, so an absent header
 * falls back to the configured currency rather than to nothing.
 */
trait ForwardsRequestContext
{
    /** @return array<string, string> */
    protected function contextHeaders(Request $request): array
    {
        $headers = [];

        $currency = $request->header('X-Currency');
        if (is_string($currency) && $currency !== '') {
            $headers['X-Currency'] = strtoupper($currency);
        }

        $locale = $request->header('Accept-Language');
        if (is_string($locale) && $locale !== '') {
            $headers['Accept-Language'] = $locale;
        }

        return $headers;
    }
}
