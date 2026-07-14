<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

use TheOneDigi\TourSdk\PartnerClient;

/**
 * Tour catalog endpoints. All read-only and all requiring the `tour:read` scope.
 *
 * Every method returns the unwrapped `data` object. Paginated responses carry
 * their metadata inside it (current_page / total / per_page / last_page next to
 * the collection), so nothing is lost by dropping the envelope.
 */
final class TourResource
{
    private const BASE = 'api/partner/tours';

    public function __construct(private readonly PartnerClient $client)
    {
    }

    /**
     * Paginated tour search. Returns `{current_page, total, per_page, last_page, tours}`.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE, $params));
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
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function seasonal(array $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . '/get-seasonal', $params));
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function featured(array $params = []): array
    {
        return $this->client->data($this->client->get(self::BASE . '/get-featured', $params));
    }

    /**
     * @param array<string, mixed> $params Expects at least `tour_code`.
     * @return array<string, mixed>
     */
    public function similar(array $params): array
    {
        return $this->client->data($this->client->get(self::BASE . '/get-similar', $params));
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $code): array
    {
        return $this->client->data($this->client->get(self::BASE . '/' . rawurlencode($code)));
    }

    /**
     * Departure calendar for a tour.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function calendars(string $code, array $params = []): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode($code) . '/calendars', $params),
        );
    }

    /**
     * Remaining seats and unit prices for one departure date. This is what the
     * "availability" check in a storefront is built on.
     *
     * Reads only — it reserves nothing. Seats are held by creating a booking.
     *
     * @param array<string, mixed> $params Expects `date`, usually `pax`.
     * @return array<string, mixed>
     */
    public function calendarByDate(string $code, array $params): array
    {
        return $this->client->data(
            $this->client->get(self::BASE . '/' . rawurlencode($code) . '/calendar-by-date', $params),
        );
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
}
