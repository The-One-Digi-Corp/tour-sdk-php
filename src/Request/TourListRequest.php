<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class TourListRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $goingTo = null,
        public readonly ?int $travelStyle = null,
        public readonly ?string $listTravelStyles = null,
        public readonly ?string $category = null,
        public readonly ?string $subType = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?string $budget = null,
        public readonly ?string $departureDate = null,
        public readonly ?string $sortBy = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            search: isset($payload['search']) ? (string) $payload['search'] : (isset($payload['q']) ? (string) $payload['q'] : null),
            goingTo: isset($payload['going_to']) ? (string) $payload['going_to'] : null,
            travelStyle: isset($payload['travel_style']) ? (int) $payload['travel_style'] : null,
            listTravelStyles: isset($payload['list_travel_styles']) ? (string) $payload['list_travel_styles'] : null,
            category: isset($payload['category']) ? (string) $payload['category'] : null,
            subType: isset($payload['sub_type']) ? (string) $payload['sub_type'] : (isset($payload['list_sub_types']) ? (string) $payload['list_sub_types'] : null),
            minPrice: isset($payload['minPrice']) ? (float) $payload['minPrice'] : (isset($payload['price_min']) ? (float) $payload['price_min'] : null),
            maxPrice: isset($payload['maxPrice']) ? (float) $payload['maxPrice'] : (isset($payload['price_max']) ? (float) $payload['price_max'] : null),
            budget: isset($payload['budget']) ? (string) $payload['budget'] : null,
            departureDate: isset($payload['departure_date']) ? (string) $payload['departure_date'] : (isset($payload['date']) ? (string) $payload['date'] : null),
            sortBy: isset($payload['sortBy']) ? (string) $payload['sortBy'] : null,
            page: isset($payload['page']) ? (int) $payload['page'] : null,
            perPage: isset($payload['per_page']) ? (int) $payload['per_page'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'search' => $this->search,
            'going_to' => $this->goingTo,
            'travel_style' => $this->travelStyle,
            'list_travel_styles' => $this->listTravelStyles,
            'category' => $this->category,
            'sub_type' => $this->subType,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'budget' => $this->budget,
            'departure_date' => $this->departureDate,
            'sortBy' => $this->sortBy,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ]);
    }
}
