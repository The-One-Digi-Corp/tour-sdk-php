<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class TourPriceResource extends ArrayBackedResource
{
    public readonly int|string|null $id;
    public readonly float $adultPrice;
    public readonly float $childPrice;
    public readonly float $infantPrice;
    public readonly float $costAdult;
    public readonly float $costChild;
    public readonly float $costInfant;
    public readonly int|string|null $rangeId;
    public readonly int|string|null $minPax;
    public readonly int|string|null $maxPax;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->id = $this->scalar('id');
        $this->adultPrice = $this->float('adult_price');
        $this->childPrice = $this->float('child_price');
        $this->infantPrice = $this->float('infant_price');
        $this->costAdult = $this->float('cost_adult');
        $this->costChild = $this->float('cost_child');
        $this->costInfant = $this->float('cost_infant');
        $this->rangeId = $this->scalar('range_id');
        $this->minPax = $this->scalar('min_pax');
        $this->maxPax = $this->scalar('max_pax');
    }
}
