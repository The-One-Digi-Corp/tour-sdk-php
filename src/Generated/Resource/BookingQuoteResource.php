<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema the `data` of POST /bookings/quote (partnerCheckoutQuote).
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class BookingQuoteResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly bool $isAvailable;
    /* END AUTO FIELDS */

    /* BEGIN MANUAL FIELDS */
    // The contract gives these no type at all — PartnerBookingController::quote
    // returns the service's array straight to success(), and Scramble will not
    // resolve app(Service::class)->method(). Everything below is `mixed` in AUTO,
    // which is useless to a caller doing arithmetic on a price.
    //
    // Shape verified against travelo-api's own response by
    // tests/Feature/PartnerContractSnapshotTest (POST /bookings/quote).
    public readonly float $subTotal;
    public readonly float $discount;
    public readonly float $total;
    public readonly float $cost;
    public readonly string $currency;
    public readonly int $remainingSlots;
    /** @var array<string, mixed> */
    public readonly array $prices;
    /* END MANUAL FIELDS */

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        /* BEGIN AUTO HYDRATION */
        $this->isAvailable = $this->bool('is_available');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    protected function hydrateManual(): void
    {
        $this->subTotal = $this->float('sub_total');
        $this->discount = $this->float('discount');
        $this->total = $this->float('total');
        $this->cost = $this->float('cost');
        $this->currency = $this->string('currency');
        $this->remainingSlots = $this->int('remaining_slots');
        $this->prices = $this->array('prices');
    }
    /* END MANUAL HYDRATION */
}
