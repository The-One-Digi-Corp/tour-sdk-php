<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema BookingQuoteResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class BookingQuoteResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly float $subTotal;
    public readonly float $discount;
    public readonly float $total;
    public readonly float $cost;
    public readonly string $currency;
    public readonly ?BookingQuotePriceResource $prices;
    public readonly bool $isAvailable;
    public readonly ?int $remainingSlots;
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
        $this->subTotal = $this->float('sub_total');
        $this->discount = $this->float('discount');
        $this->total = $this->float('total');
        $this->cost = $this->float('cost');
        $this->currency = $this->string('currency');
        $this->prices = is_array($this->get('prices')) ? BookingQuotePriceResource::fromArray($this->get('prices')) : null;
        $this->isAvailable = $this->bool('is_available');
        $this->remainingSlots = $this->nullableInt('remaining_slots');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
