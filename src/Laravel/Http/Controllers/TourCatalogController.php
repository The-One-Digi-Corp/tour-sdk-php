<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TheOneDigi\TourSdk\Api\TourApi;
use TheOneDigi\TourSdk\Common\ApiPaths;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsApiErrors;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsRequestContext;
use TheOneDigi\TourSdk\Laravel\Support\TourCatalogDecorator;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * Read-only tour catalog endpoints proxied to travelo-api.
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
    use ForwardsRequestContext;

    public function __construct(private readonly PartnerClient $client)
    {
    }

    /**
     * List/search all whitelisted tours.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'q' => 'nullable|string|max:255',
            'going_to' => 'nullable|string|max:20',
            'travel_style' => 'nullable|integer',
            'list_travel_styles' => 'nullable|string',
            'category' => 'nullable|string',
            'sub_type' => 'nullable|string',
            'list_sub_types' => 'nullable|string',
            'minPrice' => 'nullable|numeric',
            'maxPrice' => 'nullable|numeric',
            'price_min' => 'nullable|numeric',
            'price_max' => 'nullable|numeric',
            'budget' => 'nullable|string|in:under-500,500-1000,1000-2000,2000-3000,above-3000',
            'departure_date' => 'nullable|date',
            'date' => 'nullable|date',
            'sortBy' => 'nullable|string|in:shortest_duration,lowest,highest,newest',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
        ]);

        return $this->forward(
            fn () => $this->client->get(TourApi::BASE, $request->query(), $this->contextHeaders($request)),
            'tours.index',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    /**
     * Filter/reference taxonomy for building tour catalog filters.
     */
    public function references(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . ApiPaths::REFERENCES, [], $this->contextHeaders($request)),
            'tours.references',
        );
    }

    /**
     * List seasonal tours.
     */
    public function seasonal(Request $request): JsonResponse
    {
        $request->validate([
            'take' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . ApiPaths::SEASONAL, $request->query(), $this->contextHeaders($request)),
            'tours.seasonal',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    /**
     * List featured tours.
     */
    public function featured(Request $request): JsonResponse
    {
        $request->validate([
            'take' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . ApiPaths::FEATURED, $request->query(), $this->contextHeaders($request)),
            'tours.featured',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    /**
     * List tours similar to a given tour.
     */
    public function similar(Request $request): JsonResponse
    {
        $request->validate([
            'tour_code' => 'nullable|string|max:80',
            'tour_id' => 'nullable|integer|min:1',
            'take' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . ApiPaths::SIMILAR, $request->query(), $this->contextHeaders($request)),
            'tours.similar',
            fn (array $p) => $this->decorateList($request, $p),
        );
    }

    /**
     * Get detailed information of one tour.
     */
    public function show(Request $request, string $code): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/' . rawurlencode($code), [], $this->contextHeaders($request)),
            'tours.show',
            fn (array $p) => $this->decorateOne($request, $p),
        );
    }

    /**
     * List active departure calendars for one tour.
     */
    public function calendars(Request $request, string $code): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(TourApi::BASE . '/' . rawurlencode($code) . ApiPaths::CALENDARS, $request->query(), $this->contextHeaders($request)),
            'tours.calendars',
        );
    }

    /**
     * Check availability and price for one departure date.
     */
    public function calendarByDate(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'pax' => 'nullable|integer|min:1',
        ]);

        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($code) . ApiPaths::CALENDAR_BY_DATE,
                $request->query(),
                $this->contextHeaders($request),
            ),
            'tours.calendar-by-date',
        );
    }

    /**
     * List reviews of a whitelisted tour.
     *
     * Note: the path segment is the tour id, not the code — upstream's choice, mirrored.
     */
    public function reviews(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($id) . ApiPaths::REVIEWS,
                $request->query(),
                $this->contextHeaders($request),
            ),
            'tours.reviews',
        );
    }

    /**
     * List review images of a whitelisted tour.
     */
    public function reviewImages(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($id) . ApiPaths::REVIEW_IMAGES,
                $request->query(),
                $this->contextHeaders($request),
            ),
            'tours.review-images',
        );
    }

    /**
     * List bookable departures of a whitelisted tour from a date onward.
     *
     * One entry per departure date with remaining slots and price.
     */
    public function schedule(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . '/' . rawurlencode($id) . ApiPaths::SCHEDULE,
                $request->query(),
                $this->contextHeaders($request),
            ),
            'tours.schedule',
        );
    }

    /**
     * Get the day-by-day itinerary of a whitelisted tour.
     */
    public function itinerary(Request $request, string $id): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->get(
                TourApi::BASE . ApiPaths::ITINERARY . '/' . rawurlencode($id),
                [],
                $this->contextHeaders($request),
            ),
            'tours.itinerary',
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
