<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourPromotion.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourPromotion extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly string $code;
    public readonly string $name;
    public readonly int $type;
    public readonly int $quantity;
    public readonly int $quantitySold;
    public readonly int $valueCoupon;
    public readonly string $currency;
    public readonly ?string $startTime;
    public readonly ?string $endTime;
    public readonly int $status;
    public readonly ?string $createdAt;
    public readonly ?string $updatedAt;
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
        $this->code = $this->string('code');
        $this->name = $this->string('name');
        $this->type = $this->int('type');
        $this->quantity = $this->int('quantity');
        $this->quantitySold = $this->int('quantity_sold');
        $this->valueCoupon = $this->int('value_coupon');
        $this->currency = $this->string('currency');
        $this->startTime = $this->nullableString('start_time');
        $this->endTime = $this->nullableString('end_time');
        $this->status = $this->int('status');
        $this->createdAt = $this->nullableString('created_at');
        $this->updatedAt = $this->nullableString('updated_at');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
