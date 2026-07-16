<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TheOneDigi\TourSdk\Api\TourApi;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsApiErrors;
use TheOneDigi\TourSdk\Laravel\Support\TourCatalogDecorator;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * The ten read-only catalog endpoints, one route each.
 *
 * Envelopes are forwarded verbatim rather than unwrapped and rebuilt: callers keep
 * travelo-api's exact shape, and the generated contract types that describe it stay
 * valid. That is why this goes through PartnerClient rather than TourApi — TourApi
 * unwraps to `data`, which would silently drop `message` on the way out.
 *
 * Routes are declared explicitly instead of a `{path}` catch-all. A regex can
 * whitelist a GET path, but it cannot say anything about a POST body, and this
 * controller sits next to booking routes that do take one.
 */
class TourCatalogController
{
    use ForwardsApiErrors;

    public function __construct(private readonly PartnerClient $client)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE, $request->query()),
            'tours.index',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    public function references(): JsonResponse
    {
        return $this->forward(fn () => $this->client->get(TourApi::BASE . '/references'), 'tours.references');
    }

    public function seasonal(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/get-seasonal', $request->query()),
            'tours.seasonal',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    public function featured(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/get-featured', $request->query()),
            'tours.featured',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    public function similar(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/get-similar', $request->query()),
            'tours.similar',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    public function show(Request $request, string $code): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/' . rawurlencode($code)),
            'tours.show',
            fn (array $p) => $this->decorateOne($request, $p),
        );
    }

    public function calendars(Request $request, string $code): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/' . rawurlencode($code) . '/calendars', $request->query()),
            'tours.calendars',
        );
    }

    public function calendarByDate(Request $request, string $code): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($code) . '/calendar-by-date',
                $request->query(),
            ),
            'tours.calendar-by-date',
        );
    }

    /** Note the segment is the tour id here, not the code — upstream's choice, mirrored. */
    public function reviews(Request $request, string $id): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($id) . '/get-list-reviews',
                $request->query(),
            ),
            'tours.reviews',
        );
    }

    public function reviewImages(Request $request, string $id): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($id) . '/get-all-image-reviews',
                $request->query(),
            ),
            'tours.review-images',
        );
    }

    /**
     * List endpoints (index / seasonal / featured / similar) carry the tours at
     * `data.tours`, alongside pagination keys. Anything else is left untouched.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function decorateList(Request $request, array $payload): array
    {
        if (! TourCatalogDecorator::hasDecorators()) {
            return $payload;
        }

        $tours = $payload['data']['tours'] ?? null;

        if (! is_array($tours) || ! array_is_list($tours)) {
            return $payload;
        }

        $payload['data']['tours'] = TourCatalogDecorator::applyList($tours, $request);

        return $payload;
    }

    /**
     * `show` returns a single tour as `data`.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function decorateOne(Request $request, array $payload): array
    {
        if (! TourCatalogDecorator::hasDecorators()) {
            return $payload;
        }

        $tour = $payload['data'] ?? null;

        if (! is_array($tour) || $tour === [] || array_is_list($tour)) {
            return $payload;
        }

        $payload['data'] = TourCatalogDecorator::applyOne($tour, $request);

        return $payload;
    }
}
