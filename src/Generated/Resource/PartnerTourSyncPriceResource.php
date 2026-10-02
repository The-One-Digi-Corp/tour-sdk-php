<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerTourSyncPriceResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerTourSyncPriceResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $rangeId;
    public readonly ?int $minPax;
    public readonly ?int $maxPax;
    public readonly string $currency;
    public readonly float $adultPrice;
    public readonly float $childPrice;
    public readonly float $infantPrice;
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
        $this->rangeId = $this->int('range_id');
        $this->minPax = $this->nullableInt('min_pax');
        $this->maxPax = $this->nullableInt('max_pax');
        $this->currency = $this->string('currency');
        $this->adultPrice = $this->float('adult_price');
        $this->childPrice = $this->float('child_price');
        $this->infantPrice = $this->float('infant_price');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
