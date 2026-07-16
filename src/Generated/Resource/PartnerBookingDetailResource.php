<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerBookingDetailResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerBookingDetailResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly int $tourId;
    public readonly int $tourPriceGroupId;
    public readonly ?string $departureDate;
    public readonly int $adultQuantity;
    public readonly int $childQuantity;
    public readonly int $infantQuantity;
    public readonly float $adultPrice;
    public readonly float $childPrice;
    public readonly float $infantPrice;
    public readonly float $groupPrice;
    public readonly float $discountPrice;
    public readonly int $discountType;
    public readonly int $discountCount;
    public readonly ?string $specialRequest;
    public readonly ?PartnerBookingDetailTourResource $tour;
    public readonly string $baseCurrency;
    public readonly float $inputAdultPrice;
    public readonly float $inputChildPrice;
    public readonly float $inputInfantPrice;
    public readonly float $inputGroupPrice;
    public readonly float $inputDiscountPrice;
    public readonly string $inputCurrency;
    public readonly int $inputCurrencyVersion;
    public readonly float $inputCurrencyExchangeRate;
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
        $this->tourId = $this->int('tour_id');
        $this->tourPriceGroupId = $this->int('tour_price_group_id');
        $this->departureDate = $this->nullableString('departure_date');
        $this->adultQuantity = $this->int('adult_quantity');
        $this->childQuantity = $this->int('child_quantity');
        $this->infantQuantity = $this->int('infant_quantity');
        $this->adultPrice = $this->float('adult_price');
        $this->childPrice = $this->float('child_price');
        $this->infantPrice = $this->float('infant_price');
        $this->groupPrice = $this->float('group_price');
        $this->discountPrice = $this->float('discount_price');
        $this->discountType = $this->int('discount_type');
        $this->discountCount = $this->int('discount_count');
        $this->specialRequest = $this->nullableString('special_request');
        $this->tour = is_array($this->get('tour')) ? PartnerBookingDetailTourResource::fromArray($this->get('tour')) : null;
        $this->baseCurrency = $this->string('base_currency');
        $this->inputAdultPrice = $this->float('input_adult_price');
        $this->inputChildPrice = $this->float('input_child_price');
        $this->inputInfantPrice = $this->float('input_infant_price');
        $this->inputGroupPrice = $this->float('input_group_price');
        $this->inputDiscountPrice = $this->float('input_discount_price');
        $this->inputCurrency = $this->string('input_currency');
        $this->inputCurrencyVersion = $this->int('input_currency_version');
        $this->inputCurrencyExchangeRate = $this->float('input_currency_exchange_rate');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
