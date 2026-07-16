<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TheOneDigi\TourSdk\Exception\ApiException;
use TheOneDigi\TourSdk\Exception\TransportException;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * Signed pass-through for tour catalog reads, registered only when a consumer
 * opts in with `Route::traveloCatalog()`.
 *
 * Responses are forwarded verbatim so callers keep travelo-api's shape — and the
 * generated TypeScript contract types that describe it stay valid.
 *
 * Read-only by construction: the route constrains the path to `tours*`, and this
 * re-checks rather than trusting it. Anything that moves money or state belongs
 * in the consumer's own controller, where its payment policy lives.
 */
class CatalogProxyController
{
    private const ALLOWED_PREFIX = 'tours';

    public function __construct(private readonly PartnerClient $client)
    {
    }

    public function proxy(Request $request, string $path): JsonResponse
    {
        $path = ltrim($path, '/');

        // Defence in depth: a loosened route constraint must not turn this into a
        // way to reach booking writes.
        if ($path !== self::ALLOWED_PREFIX && ! str_starts_with($path, self::ALLOWED_PREFIX . '/')) {
            Log::warning('CatalogProxyController@proxy blocked a non-catalog path', ['path' => $path]);

            return response()->json([
                'status' => 404,
                'message' => 'Not found.',
                'errors' => [],
                'data' => null,
            ], 404);
        }

        try {
            $payload = $this->client->get('api/partner/' . $path, $request->query());
        } catch (ApiException $e) {
            return response()->json(
                $e->payload() !== [] ? $e->payload() : [
                    'status' => $e->status(),
                    'message' => $e->getMessage(),
                    'errors' => [],
                    'data' => null,
                ],
                $e->status(),
            );
        } catch (TransportException $e) {
            Log::error('CatalogProxyController@proxy travelo unreachable', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 503,
                'message' => 'Tour service is unavailable.',
                'errors' => ['reason' => 'travelo_unreachable'],
                'data' => null,
            ], 503);
        }

        return response()->json($payload);
    }
}
