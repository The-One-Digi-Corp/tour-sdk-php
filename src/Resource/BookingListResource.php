<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class BookingListResource extends ArrayBackedResource
{
    public readonly int $currentPage;
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $lastPage;

    /**
     * @var list<BookingResource>
     */
    public readonly array $bookings;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->currentPage = $this->int('current_page', 1);
        $this->total = $this->int('total', count($this->array('bookings')));
        $this->perPage = $this->int('per_page', $this->total);
        $this->lastPage = $this->int('last_page', 1);
        $this->bookings = self::resourceList($this->array('bookings'), BookingResource::class);
    }
}
