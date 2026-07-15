<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class BookingQuoteResource extends ArrayBackedResource
{
    public readonly float $subTotal;
    public readonly float $discount;
    public readonly float $total;
    public readonly float $cost;
    public readonly string $currency;
    public readonly bool $isAvailable;
    public readonly int $remainingSlots;

    /**
     * @var array<string, mixed>
     */
    public readonly array $prices;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->subTotal = $this->float('sub_total');
        $this->discount = $this->float('discount');
        $this->total = $this->float('total');
        $this->cost = $this->float('cost');
        $this->currency = $this->string('currency');
        $this->isAvailable = $this->bool('is_available');
        $this->remainingSlots = $this->int('remaining_slots');
        $this->prices = $this->array('prices');
    }
}
