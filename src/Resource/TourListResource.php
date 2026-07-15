<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class TourListResource extends ArrayBackedResource
{
    public readonly int $currentPage;
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $lastPage;

    /**
     * @var list<TourResource>
     */
    public readonly array $tours;

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $attributes
     */
    public function __construct(array $attributes)
    {
        $normalized = array_is_list($attributes) ? ['tours' => $attributes] : $attributes;

        parent::__construct($normalized);

        $this->currentPage = $this->int('current_page', 1);
        $this->total = $this->int('total', count($this->array('tours')));
        $this->perPage = $this->int('per_page', $this->total);
        $this->lastPage = $this->int('last_page', 1);
        $this->tours = self::resourceList($this->array('tours'), TourResource::class);
    }
}
