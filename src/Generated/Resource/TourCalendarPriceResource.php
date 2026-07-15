<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourCalendarPriceResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourCalendarPriceResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly float $adultPrice;
    public readonly float $childPrice;
    public readonly float $infantPrice;
    public readonly ?int $rangeId;
    public readonly ?int $minPax;
    public readonly ?int $maxPax;
    public readonly ?float $costAdult;
    public readonly ?float $costChild;
    public readonly ?float $costInfant;
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
        $this->adultPrice = $this->float('adult_price');
        $this->childPrice = $this->float('child_price');
        $this->infantPrice = $this->float('infant_price');
        $this->rangeId = $this->nullableInt('range_id');
        $this->minPax = $this->nullableInt('min_pax');
        $this->maxPax = $this->nullableInt('max_pax');
        $this->costAdult = is_numeric($this->get('cost_adult')) ? (float) $this->get('cost_adult') : null;
        $this->costChild = is_numeric($this->get('cost_child')) ? (float) $this->get('cost_child') : null;
        $this->costInfant = is_numeric($this->get('cost_infant')) ? (float) $this->get('cost_infant') : null;
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
