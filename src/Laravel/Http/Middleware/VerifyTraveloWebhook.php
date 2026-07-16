<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use TheOneDigi\TourSdk\WebhookVerifier;

/**
 * Rejects travelo-api webhooks that are unsigned, mis-signed, or replayed.
 *
 * Ships here rather than in each consumer because getting it wrong is silent and
 * expensive: verify a re-encoded body instead of the raw one and every delivery
 * fails; skip the timestamp and old deliveries can be replayed.
 *
 * Usage:
 *   Route::post('/webhooks/travelo', ...)->middleware(VerifyTraveloWebhook::class);
 *
 * The route must stay outside any app-key/auth guard — a webhook proves itself
 * with its signature, and handing your app key to travelo-api would be worse.
 */
class VerifyTraveloWebhook
{
    public function __construct(private readonly WebhookVerifier $verifier)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Raw body, never $request->all(): the signature covers the exact bytes
        // sent, and re-encoding a decoded array can reorder keys or change escaping.
        $isValid = $this->verifier->isValid(
            $request->getContent(),
            (string) $request->header(WebhookVerifier::HEADER_SIGNATURE, ''),
            $request->header(WebhookVerifier::HEADER_TIMESTAMP),
        );

        if (! $isValid) {
            Log::warning('VerifyTraveloWebhook@handle rejected an unsigned, stale, or mis-signed webhook', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'received' => false,
                'error' => 'invalid_signature',
            ], 401);
        }

        return $next($request);
    }
}
