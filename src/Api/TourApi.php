<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Api;

use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Common\RequestPayload;
use TheOneDigi\TourSdk\Generated\Resource\TourCalendarDateResource;
use TheOneDigi\TourSdk\Generated\Resource\TourCalendarDetailResource;
use TheOneDigi\TourSdk\Generated\Resource\TourListResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerTourResource;

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
    private const BASE = 'api/partner/tours';

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
     * Filter vocabulary: categories, types, sub_types, travel_styles, tags, destinations.
     *
     * @return array<string, mixed>
     */
    public function references(): array
    {
        return $this->client->data($this->client->get(self::BASE . '/references'));
    }

    /**
     * @return array<string, mixed>
     */
    public function seasonal(array|RequestPayload $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . '/get-seasonal', $this->payload($params)));
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
        return $this->client->data($this->client->get(self::BASE . '/get-featured', $this->payload($params)));
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
        return $this->client->data($this->client->get(self::BASE . '/get-similar', $this->payload($params)));
    }

    public function similarResources(array|RequestPayload $params): TourListResource
    {
        return TourListResource::fromArray($this->similar($params));
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $code): array
    {
        return $this->client->data($this->client->get(self::BASE . '/' . rawurlencode($code)));
    }

    public function showResource(string $code): PartnerTourResource
    {
        return PartnerTourResource::fromArray($this->show($code));
    }

    /**
     * Departure calendar for a tour.
     *
     * @return array<string, mixed>
     */
    public function calendars(string $code, array|RequestPayload $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode($code) . '/calendars', $this->payload($params)),
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return list<TourCalendarDetailResource>
     */
    public function calendarsResources(string $code, array|RequestPayload $params = []): array
    {
        $data = $this->calendars($code, $params);
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
    public function calendarResources(string $code, array|RequestPayload $params = []): array
    {
        return $this->calendarsResources($code, $params);
    }

    /**
     * Remaining seats and unit prices for one departure date. This is what the
     * "availability" check in a storefront is built on.
     *
     * Reads only — it reserves nothing. Seats are held by creating a booking.
     *
     * @return array<string, mixed>
     */
    public function calendarByDate(string $code, array|RequestPayload $params): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode($code) . '/calendar-by-date', $this->payload($params)),
        );
    }

    public function calendarByDateResource(string $code, array|RequestPayload $params): TourCalendarDateResource
    {
        return TourCalendarDateResource::fromArray($this->calendarByDate($code, $params));
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
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . '/get-list-reviews', $params),
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function reviewImages(int|string $tourId, array $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode((string) $tourId) . '/get-all-image-reviews', $params),
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
