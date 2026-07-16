<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerToursSearch (GET /tours).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerToursSearchRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $q = null,
        public readonly ?string $goingTo = null,
        public readonly ?int $travelStyle = null,
        public readonly ?string $listTravelStyles = null,
        public readonly ?string $category = null,
        public readonly ?string $subType = null,
        public readonly ?string $listSubTypes = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly ?string $budget = null,
        public readonly ?string $departureDate = null,
        public readonly ?string $date = null,
        public readonly ?string $sortBy = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        /**
         * Payload keys fromArray() actually saw, so an explicit null survives
         * toArray(). Empty when the request is built with named arguments.
         *
         * @var list<string>
         */
        protected readonly array $providedKeys = [],
    ) {
        $this->validateManual();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): static
    {
        $payload = static::normalizeManual($payload);

        return new static(
            search: (array_key_exists('search', $payload) && $payload['search'] !== null ? (string) $payload['search'] : null),
            q: (array_key_exists('q', $payload) && $payload['q'] !== null ? (string) $payload['q'] : null),
            goingTo: (array_key_exists('going_to', $payload) && $payload['going_to'] !== null ? (string) $payload['going_to'] : null),
            travelStyle: (array_key_exists('travel_style', $payload) && $payload['travel_style'] !== null ? (int) $payload['travel_style'] : null),
            listTravelStyles: (array_key_exists('list_travel_styles', $payload) && $payload['list_travel_styles'] !== null ? (string) $payload['list_travel_styles'] : null),
            category: (array_key_exists('category', $payload) && $payload['category'] !== null ? (string) $payload['category'] : null),
            subType: (array_key_exists('sub_type', $payload) && $payload['sub_type'] !== null ? (string) $payload['sub_type'] : null),
            listSubTypes: (array_key_exists('list_sub_types', $payload) && $payload['list_sub_types'] !== null ? (string) $payload['list_sub_types'] : null),
            minPrice: (array_key_exists('minPrice', $payload) && $payload['minPrice'] !== null ? (float) $payload['minPrice'] : null),
            maxPrice: (array_key_exists('maxPrice', $payload) && $payload['maxPrice'] !== null ? (float) $payload['maxPrice'] : null),
            priceMin: (array_key_exists('price_min', $payload) && $payload['price_min'] !== null ? (float) $payload['price_min'] : null),
            priceMax: (array_key_exists('price_max', $payload) && $payload['price_max'] !== null ? (float) $payload['price_max'] : null),
            budget: (array_key_exists('budget', $payload) && $payload['budget'] !== null ? (string) $payload['budget'] : null),
            departureDate: (array_key_exists('departure_date', $payload) && $payload['departure_date'] !== null ? (string) $payload['departure_date'] : null),
            date: (array_key_exists('date', $payload) && $payload['date'] !== null ? (string) $payload['date'] : null),
            sortBy: (array_key_exists('sortBy', $payload) && $payload['sortBy'] !== null ? (string) $payload['sortBy'] : null),
            page: (array_key_exists('page', $payload) && $payload['page'] !== null ? (int) $payload['page'] : null),
            perPage: (array_key_exists('per_page', $payload) && $payload['per_page'] !== null ? (int) $payload['per_page'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'search' => $this->search,
            'q' => $this->q,
            'going_to' => $this->goingTo,
            'travel_style' => $this->travelStyle,
            'list_travel_styles' => $this->listTravelStyles,
            'category' => $this->category,
            'sub_type' => $this->subType,
            'list_sub_types' => $this->listSubTypes,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'budget' => $this->budget,
            'departure_date' => $this->departureDate,
            'date' => $this->date,
            'sortBy' => $this->sortBy,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /**
     * travelo-api accepts several spellings of the same filter for backward
     * compatibility — it validates `search` and `q`, `minPrice` and `price_min`,
     * and so on — so the contract lists each alias as its own parameter. Sending
     * two spellings of one filter is ambiguous, so they are folded onto the
     * canonical key here and the aliases dropped.
     *
     * This is the class of bug that bit the TypeScript SDK (`sort` sent where the
     * API wanted `sortBy`): the request looked valid and the filter silently did
     * nothing.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected static function normalizeManual(array $payload): array
    {
        $aliases = [
            'search' => 'q',
            'sub_type' => 'list_sub_types',
            'minPrice' => 'price_min',
            'maxPrice' => 'price_max',
            'departure_date' => 'date',
        ];

        foreach ($aliases as $canonical => $alias) {
            if (! isset($payload[$canonical]) && isset($payload[$alias])) {
                $payload[$canonical] = $payload[$alias];
            }

            unset($payload[$alias]);
        }

        return $payload;
    }
    /* END MANUAL BODY */
}
