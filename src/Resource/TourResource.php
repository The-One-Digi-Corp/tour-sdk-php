<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class TourResource extends ArrayBackedResource
{
    public readonly int|string|null $id;
    public readonly string $code;
    public readonly string $name;
    public readonly string $currency;
    public readonly ?string $slug;
    public readonly ?string $durationText;
    public readonly ?string $duration;
    public readonly ?string $thumbnail;
    public readonly float $basePrice;
    public readonly ?string $averageRating;
    public readonly int $totalReviews;

    /**
     * @var array<string, mixed>
     */
    public readonly array $translations;

    /**
     * @var list<TourPriceResource>
     */
    public readonly array $prices;

    /**
     * @var list<TourCalendarResource>
     */
    public readonly array $calendars;

    /**
     * @var list<array<string, mixed>>
     */
    public readonly array $images;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->id = $this->scalar('id');
        $this->code = $this->string('code');
        $this->name = $this->string('name');
        $this->currency = $this->string('currency');
        $this->slug = $this->nullableString('slug');
        $this->durationText = $this->nullableString('duration_text');
        $this->duration = $this->nullableString('duration');
        $this->thumbnail = $this->nullableString('thumbnail');
        $this->basePrice = $this->float('base_price');
        $this->averageRating = $this->nullableString('average_rating');
        $this->totalReviews = $this->int('total_reviews');
        $this->translations = $this->array('translations');
        $this->prices = self::resourceList($this->array('prices'), TourPriceResource::class);
        $this->calendars = self::resourceList($this->array('calendars'), TourCalendarResource::class);
        $this->images = array_values(array_filter(
            $this->array('images'),
            static fn (mixed $item): bool => is_array($item),
        ));
    }
}
