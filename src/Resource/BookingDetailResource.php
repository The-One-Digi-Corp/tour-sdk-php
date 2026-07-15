<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class BookingDetailResource extends ArrayBackedResource
{
    public readonly int|string|null $id;
    public readonly int|string|null $tourId;
    public readonly int|string|null $tourPriceGroupId;
    public readonly ?string $departureDate;
    public readonly int $adultQuantity;
    public readonly int $childQuantity;
    public readonly int $infantQuantity;
    public readonly float $adultPrice;
    public readonly float $childPrice;
    public readonly float $infantPrice;
    public readonly float $groupPrice;
    public readonly float $discountPrice;
    public readonly float $inputAdultPrice;
    public readonly float $inputChildPrice;
    public readonly float $inputInfantPrice;
    public readonly float $inputGroupPrice;
    public readonly float $inputDiscountPrice;
    public readonly string $inputCurrency;
    public readonly int $inputCurrencyVersion;
    public readonly float $inputCurrencyExchangeRate;
    public readonly ?string $specialRequest;

    /**
     * @var array<string, mixed>|null
     */
    public readonly ?array $tour;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->id = $this->scalar('id');
        $this->tourId = $this->scalar('tour_id');
        $this->tourPriceGroupId = $this->scalar('tour_price_group_id');
        $this->departureDate = $this->nullableString('departure_date');
        $this->adultQuantity = $this->int('adult_quantity');
        $this->childQuantity = $this->int('child_quantity');
        $this->infantQuantity = $this->int('infant_quantity');
        $this->adultPrice = $this->float('adult_price');
        $this->childPrice = $this->float('child_price');
        $this->infantPrice = $this->float('infant_price');
        $this->groupPrice = $this->float('group_price');
        $this->discountPrice = $this->float('discount_price');
        $this->inputAdultPrice = $this->float('input_adult_price', $this->adultPrice);
        $this->inputChildPrice = $this->float('input_child_price', $this->childPrice);
        $this->inputInfantPrice = $this->float('input_infant_price', $this->infantPrice);
        $this->inputGroupPrice = $this->float('input_group_price', $this->groupPrice);
        $this->inputDiscountPrice = $this->float('input_discount_price', $this->discountPrice);
        $this->inputCurrency = $this->string('input_currency');
        $this->inputCurrencyVersion = $this->int('input_currency_version', 1);
        $this->inputCurrencyExchangeRate = $this->float('input_currency_exchange_rate', 1.0);
        $this->specialRequest = $this->nullableString('special_request');
        $tour = $attributes['tour'] ?? null;
        $this->tour = is_array($tour) ? $tour : null;
    }
}
