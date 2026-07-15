<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourCalendarAvailabilityResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourCalendarAvailabilityResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $calendarId;
    public readonly ?int $tourPriceGroupId;
    public readonly ?string $startDate;
    public readonly ?string $endDate;
    public readonly ?int $maxSlotsPerDay;
    public readonly ?int $remainingSlots;
    public readonly bool $isAvailable;
    public readonly bool $isPriceOverridden;
    public readonly ?TourCalendarPriceResource $price;
    /** @var list<TourCalendarPriceResource> */
    public readonly array $allPrices;
    public readonly ?TourPriceGroupRangeResource $matchedRange;
    public readonly ?TourPriceGroupResource $priceGroup;
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
        $this->calendarId = $this->int('calendar_id');
        $this->tourPriceGroupId = $this->nullableInt('tour_price_group_id');
        $this->startDate = $this->nullableString('start_date');
        $this->endDate = $this->nullableString('end_date');
        $this->maxSlotsPerDay = $this->nullableInt('max_slots_per_day');
        $this->remainingSlots = $this->nullableInt('remaining_slots');
        $this->isAvailable = $this->bool('is_available');
        $this->isPriceOverridden = $this->bool('is_price_overridden');
        $this->price = is_array($this->get('price')) ? TourCalendarPriceResource::fromArray($this->get('price')) : null;
        $this->allPrices = self::resourceList($this->array('all_prices'), TourCalendarPriceResource::class);
        $this->matchedRange = is_array($this->get('matched_range')) ? TourPriceGroupRangeResource::fromArray($this->get('matched_range')) : null;
        $this->priceGroup = is_array($this->get('price_group')) ? TourPriceGroupResource::fromArray($this->get('price_group')) : null;
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
