<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class TourCalendarResource extends ArrayBackedResource
{
    public readonly int|string|null $id;
    public readonly ?string $startDate;
    public readonly ?string $endDate;
    public readonly int|string|null $maxSlotsPerDay;

    /**
     * @var list<TourPriceResource>
     */
    public readonly array $prices;

    /**
     * @var list<array<string, mixed>>
     */
    public readonly array $daySlots;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->id = $this->scalar('id');
        $this->startDate = $this->nullableString('start_date');
        $this->endDate = $this->nullableString('end_date');
        $this->maxSlotsPerDay = $this->scalar('max_slots_per_day');
        $this->prices = self::resourceList($this->array('prices'), TourPriceResource::class);
        $this->daySlots = array_values(array_filter(
            $this->array('day_slots'),
            static fn (mixed $item): bool => is_array($item),
        ));
    }
}
