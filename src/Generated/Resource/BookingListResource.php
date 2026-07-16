<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema the `data` of GET /bookings (partnerAccountListBookings).
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class BookingListResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $currentPage;
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $lastPage;
    /** @var list<PartnerBookingResource> */
    public readonly array $bookings;
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
        $this->currentPage = $this->int('current_page');
        $this->total = $this->int('total');
        $this->perPage = $this->int('per_page');
        $this->lastPage = $this->int('last_page');
        $this->bookings = self::resourceList($this->array('bookings'), PartnerBookingResource::class);
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
