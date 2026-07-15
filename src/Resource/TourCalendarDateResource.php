<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class TourCalendarDateResource extends ArrayBackedResource
{
    public readonly bool $isAvailable;
    public readonly int $remainingSlots;
    public readonly int|string|null $tourPriceGroupId;

    /**
     * @var array<string, mixed>|null
     */
    public readonly ?array $price;

    /**
     * @var list<TourPriceResource>
     */
    public readonly array $allPrices;

    /**
     * @var array<string, mixed>|null
     */
    public readonly ?array $matchedRange;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->isAvailable = $this->bool('is_available');
        $this->remainingSlots = $this->int('remaining_slots');
        $this->tourPriceGroupId = $this->scalar('tour_price_group_id');

        $price = $attributes['price'] ?? null;
        $this->price = is_array($price) ? $price : null;
        $this->allPrices = self::resourceList($this->array('all_prices'), TourPriceResource::class);

        $matchedRange = $attributes['matched_range'] ?? null;
        $this->matchedRange = is_array($matchedRange) ? $matchedRange : null;
    }
}
