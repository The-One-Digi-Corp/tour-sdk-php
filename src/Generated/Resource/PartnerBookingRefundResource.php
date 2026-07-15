<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerBookingRefundResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerBookingRefundResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly float $refundTotal;
    public readonly int $status;
    public readonly string $statusText;
    public readonly string $currency;
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
        $this->refundTotal = $this->float('refund_total');
        $this->status = $this->int('status');
        $this->statusText = $this->string('status_text');
        $this->currency = $this->string('currency');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
