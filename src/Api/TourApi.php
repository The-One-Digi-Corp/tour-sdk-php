<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Api;

use TheOneDigi\TourSdk\Common\ApiPaths;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Common\RequestPayload;
use TheOneDigi\TourSdk\Generated\Resource\TourCalendarDateResource;
use TheOneDigi\TourSdk\Generated\Resource\TourCalendarDetailResource;
use TheOneDigi\TourSdk\Generated\Resource\TourListResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerTourResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerTourSyncResource;

/**
 * Tour catalog endpoints. All read-only and all requiring the `tour:read` scope.
 *
 * Every method returns the unwrapped `data` object. Paginated responses carry
 * their metadata inside it (current_page / total / per_page / last_page next to
 * the collection), so nothing is lost by dropping the envelope.
 *
 * Not final: consumers inject and double this directly.
 */
class TourApi
{
    /** Public so controller mode can build the same paths without restating them. */
    public const BASE = ApiPaths::TOURS;

    public function __construct(private readonly PartnerClient $client)
    {
    }

    /**
     * Paginated tour search. Returns `{current_page, total, per_page, last_page, tours}`.
     *
     * @return array<string, mixed>
     */
    public function list(array|RequestPayload $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE, $this->payload($params)));
    }

    public function listResources(array|RequestPayload $params = []): TourListResource
    {
        return TourListResource::fromArray($this->list($params));
    }

    /**
     * The ids of every tour matching the same filters as list() (search, going_to,
     * category, type, tour_direction, travel styles, sub types, dates…), unpaged and
     * unsorted. Returns `{total, ids}`. For a partner that keeps its own copy (sync()) and
     * filters, sorts and pages on its own prices.
     *
     * @return array<string, mixed>
     */
    public function ids(array|RequestPayload $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . ApiPaths::IDS, $this->payload($params)));
    }

    /**
     * Just the ids of ids().
     *
     * @return list<int>
     */
    public function idList(array|RequestPayload $params = []): array
    {
        $ids = $this->ids($params)['ids'] ?? [];

        return array_values(array_map('intval', is_array($ids) ? $ids : []));
    }

    /**
     * Filter vocabulary: categories, types, sub_types, travel_styles, tags, destinations.
     *
     * @return array<string, mixed>
     */
    public function references(): array
    {
        return $this->client->data($this->client->get(self::BASE . ApiPaths::REFERENCES));
    }

    /**
     * Every tour whitelisted for the partner, page by page, with what a local copy needs:
     * names in every language, base prices, calendars with their prices and overrides.
     * Returns `{current_page, total, per_page, last_page, tours}`. Meant for a background
     * sync, not a storefront.
     *
     * @return array<string, mixed>
     */
    public function sync(array|RequestPayload $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . ApiPaths::SYNC, $this->payload($params)));
    }

    /**
     * The tours of one sync() page as typed resources.
     *
     * @return list<PartnerTourSyncResource>
     */
    public function syncResources(array|RequestPayload $params = []): array
    {
        $tours = $this->sync($params)['tours'] ?? [];

        return array_values(array_map(
            static fn (array $tour): PartnerTourSyncResource => PartnerTourSyncResource::fromArray($tour),
            array_filter(is_array($tours) ? $tours : [], 'is_array'),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function seasonal(array|RequestPayload $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . ApiPaths::SEASONAL, $this->payload($params)));
    }

    public function seasonalResources(array|RequestPayload $params = []): TourListResource
    {
        return TourListResource::fromArray($this->seasonal($params));
    }

    /**
     * @return array<string, mixed>
     */
    public function featured(array|RequestPayload $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . ApiPaths::FEATURED, $this->payload($params)));
    }

    public function featuredResources(array|RequestPayload $params = []): TourListResource
    {
        return TourListResource::fromArray($this->featured($params));
    }

    /**
     * @return array<string, mixed>
     */
    public function similar(array|RequestPayload $params): array
    {
        return $this->client->data($this->client->get(self::BASE . ApiPaths::SIMILAR, $this->payload($params)));
    }

    public function similarResources(array|RequestPayload $params): TourListResource
    {
        return TourListResource::fromArray($this->similar($params));
    }

    /**
     * Addressed by the numeric tour **id**, like every tour endpoint but search; a tour
     * code is a 404.
     *
     * @return array<string, mixed>
     */
    public function show(int|string $tourId): array
    {
        return $this->client->data($this->client->get(self::BASE . '/' . rawurlencode((string) $tourId)));
    }

    public function showResource(int|string $tourId): PartnerTourResource
    {
        return PartnerTourResource::fromArray($this->show($tourId));
    }

    /**
     * Departure calendar for a tour, addressed by tour **id**.
     *
     * @return array<string, mixed>
     */
    public function calendars(int|string $tourId, array|RequestPayload $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . ApiPaths::CALENDARS, $this->payload($params)),
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return list<TourCalendarDetailResource>
     */
    public function calendarsResources(int|string $tourId, array|RequestPayload $params = []): array
    {
        $data = $this->calendars($tourId, $params);
        $items = array_is_list($data) ? $data : ($data['calendars'] ?? []);

        if (! is_array($items)) {
            return [];
        }

        $resources = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $resources[] = TourCalendarDetailResource::fromArray($item);
            }
        }

        return $resources;
    }

    /**
     * Alias for calendarsResources().
     *
     * @param array<string, mixed> $params
     * @return list<TourCalendarDetailResource>
     */
    public function calendarResources(int|string $tourId, array|RequestPayload $params = []): array
    {
        return $this->calendarsResources($tourId, $params);
    }

    /**
     * Remaining seats and unit prices for one departure date. This is what the
     * "availability" check in a storefront is built on.
     *
     * Reads only — it reserves nothing. Seats are held by creating a booking.
     * Addressed by tour **id**.
     *
     * @return array<string, mixed>
     */
    public function calendarByDate(int|string $tourId, array|RequestPayload $params): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . ApiPaths::CALENDAR_BY_DATE, $this->payload($params)),
        );
    }

    public function calendarByDateResource(int|string $tourId, array|RequestPayload $params): TourCalendarDateResource
    {
        return TourCalendarDateResource::fromArray($this->calendarByDate($tourId, $params));
    }

    /**
     * Note the path segment is the tour **id**, not the code, unlike the calendar
     * endpoints above.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function reviews(int|string $tourId, array $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . ApiPaths::REVIEWS, $params),
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function reviewImages(int|string $tourId, array $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . ApiPaths::REVIEW_IMAGES, $params),
        );
    }

    /**
     * Day-by-day itinerary. Addressed by tour **id**, like the review endpoints.
     *
     * @return list<array<string, mixed>>
     */
    public function itinerary(int|string $tourId): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . ApiPaths::ITINERARY . '/' . rawurlencode((string) $tourId)),
        );
    }

    /**
     * Bookable departures from $params['date'] onward, one entry per date.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function schedule(int|string $tourId, array $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . ApiPaths::SCHEDULE, $params),
        );
    }


    /**
     * @return array<string, mixed>
     */
    private function payload(array|RequestPayload $payload): array
    {
        return $payload instanceof RequestPayload ? $payload->toArray() : $payload;
    }
}
