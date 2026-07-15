<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourPriceGroupRangeResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourPriceGroupRangeResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly int $tourPriceGroupId;
    public readonly int $minPax;
    public readonly ?int $maxPax;
    public readonly int $sortOrder;
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
        $this->tourPriceGroupId = $this->int('tour_price_group_id');
        $this->minPax = $this->int('min_pax');
        $this->maxPax = $this->nullableInt('max_pax');
        $this->sortOrder = $this->int('sort_order');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
