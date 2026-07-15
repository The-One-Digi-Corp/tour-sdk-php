<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourCalendarDetailResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourCalendarDetailResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly int $tourId;
    public readonly int $tourPriceGroupId;
    public readonly ?string $startDate;
    public readonly ?string $endDate;
    public readonly ?int $maxSlotsPerDay;
    public readonly int $isActive;
    public readonly ?string $createdAt;
    public readonly ?string $updatedAt;
    /** @var array<string, mixed>|list<mixed>|null */
    public readonly ?array $priceGroup;
    /** @var list<TourCalendarDetailPriceResource> */
    public readonly array $prices;
    /** @var array<string, mixed>|list<mixed> */
    public readonly array $daySlots;
    /* END AUTO FIELDS */

    /* BEGIN MANUAL FIELDS */
    
    /* END MANUAL FIELDS */

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        /* BEGIN AUTO HYDRATION */
        $this->id = $this->int('id');
        $this->tourId = $this->int('tour_id');
        $this->tourPriceGroupId = $this->int('tour_price_group_id');
        $this->startDate = $this->nullableString('start_date');
        $this->endDate = $this->nullableString('end_date');
        $this->maxSlotsPerDay = $this->nullableInt('max_slots_per_day');
        $this->isActive = $this->int('is_active');
        $this->createdAt = $this->nullableString('created_at');
        $this->updatedAt = $this->nullableString('updated_at');
        $this->priceGroup = is_array($this->get('price_group')) ? $this->get('price_group') : null;
        $this->prices = self::resourceList($this->array('prices'), TourCalendarDetailPriceResource::class);
        $this->daySlots = $this->array('day_slots');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    
    /* END MANUAL HYDRATION */
}
