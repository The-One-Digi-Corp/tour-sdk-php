<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourPriceResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourPriceResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly string $id;
    public readonly float $adultPrice;
    public readonly float $childPrice;
    public readonly float $infantPrice;
    public readonly ?string $rangeId;
    public readonly ?string $minPax;
    public readonly ?string $maxPax;
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
        $this->id = $this->string('id');
        $this->adultPrice = $this->float('adult_price');
        $this->childPrice = $this->float('child_price');
        $this->infantPrice = $this->float('infant_price');
        $this->rangeId = $this->nullableString('range_id');
        $this->minPax = $this->nullableString('min_pax');
        $this->maxPax = $this->nullableString('max_pax');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
