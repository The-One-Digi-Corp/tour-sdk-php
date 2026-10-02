<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerTourSyncCalendarResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerTourSyncCalendarResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly ?int $maxSlotsPerDay;
    public readonly ?string $createdAt;
    public readonly ?string $updatedAt;
    /** @var list<PartnerTourSyncPriceResource> */
    public readonly array $prices;
    /** @var array<string, mixed>|list<mixed> */
    public readonly array $overrides;
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
        $this->id = $this->int('id');
        $this->startDate = $this->string('start_date');
        $this->endDate = $this->string('end_date');
        $this->maxSlotsPerDay = $this->nullableInt('max_slots_per_day');
        $this->createdAt = $this->nullableString('created_at');
        $this->updatedAt = $this->nullableString('updated_at');
        $this->prices = self::resourceList($this->array('prices'), PartnerTourSyncPriceResource::class);
        $this->overrides = $this->array('overrides');
        $this->priceGroup = is_array($this->get('price_group')) ? TourPriceGroupResource::fromArray($this->get('price_group')) : null;
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
