<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerTourResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerTourResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly string $currency;
    public readonly string $code;
    public readonly string $name;
    public readonly ?string $createdAt;
    public readonly string $duration;
    /** @var array<string, mixed>|list<mixed> */
    public readonly array $translations;
    public readonly ?string $durationText;
    public readonly ?string $vehicle;
    public readonly ?string $slug;
    public readonly ?string $overview;
    public readonly ?string $priceInclude;
    public readonly ?string $priceExclude;
    public readonly ?string $transportation;
    public readonly ?string $tourGuide;
    public readonly ?string $accommodation;
    public readonly ?string $meals;
    public readonly ?string $entranceTickets;
    public readonly ?string $cancellationPolicy;
    public readonly ?float $basePrice;
    public readonly ?string $minAge;
    public readonly ?string $maxAge;
    public readonly ?string $featured;
    public readonly ?string $seasonalHighlight;
    public readonly ?string $averageRating;
    public readonly ?string $totalReviews;
    public readonly ?string $day;
    public readonly ?string $night;
    public readonly ?string $thumbnail;
    public readonly ?string $isWishlist;
    public readonly ?string $remainingTranslationSlug;
    /** @var list<TourPriceResource> */
    public readonly array $prices;
    /** @var list<ImageResource> */
    public readonly array $images;
    public readonly ?TourRouteResource $endLocation;
    public readonly ?TourRouteResource $endLocation2;
    public readonly ?TourRouteResource $startLocation;
    public readonly ?TourRouteResource $startLocation2;
    /** @var list<TourItineraryResource> */
    public readonly array $itineraries;
    /** @var list<TourCalendarResource> */
    public readonly array $calendars;
    /** @var list<TagResource> */
    public readonly array $tags;
    /** @var list<TagResource> */
    public readonly array $travelStyles;
    /** @var list<TagResource> */
    public readonly array $travelStyles2;
    public readonly ?CategoryResource $category;
    public readonly ?TourTypeResource $type;
    public readonly ?TourSubTypeResource $subType;
    public readonly ?TourSubTypeResource $subType2;
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
        $this->currency = $this->string('currency');
        $this->code = $this->string('code');
        $this->name = $this->string('name');
        $this->createdAt = $this->nullableString('created_at');
        $this->duration = $this->string('duration');
        $this->translations = $this->array('translations');
        $this->durationText = $this->nullableString('duration_text');
        $this->vehicle = $this->nullableString('vehicle');
        $this->slug = $this->nullableString('slug');
        $this->overview = $this->nullableString('overview');
        $this->priceInclude = $this->nullableString('price_include');
        $this->priceExclude = $this->nullableString('price_exclude');
        $this->transportation = $this->nullableString('transportation');
        $this->tourGuide = $this->nullableString('tour_guide');
        $this->accommodation = $this->nullableString('accommodation');
        $this->meals = $this->nullableString('meals');
        $this->entranceTickets = $this->nullableString('entrance_tickets');
        $this->cancellationPolicy = $this->nullableString('cancellation_policy');
        $this->basePrice = is_numeric($this->get('base_price')) ? (float) $this->get('base_price') : null;
        $this->minAge = $this->nullableString('min_age');
        $this->maxAge = $this->nullableString('max_age');
        $this->featured = $this->nullableString('featured');
        $this->seasonalHighlight = $this->nullableString('seasonal_highlight');
        $this->averageRating = $this->nullableString('average_rating');
        $this->totalReviews = $this->nullableString('total_reviews');
        $this->day = $this->nullableString('day');
        $this->night = $this->nullableString('night');
        $this->thumbnail = $this->nullableString('thumbnail');
        $this->isWishlist = $this->nullableString('is_wishlist');
        $this->remainingTranslationSlug = $this->nullableString('remaining_translation_slug');
        $this->prices = self::resourceList($this->array('prices'), TourPriceResource::class);
        $this->images = self::resourceList($this->array('images'), ImageResource::class);
        $this->endLocation = is_array($this->get('endLocation')) ? TourRouteResource::fromArray($this->get('endLocation')) : null;
        $this->endLocation2 = is_array($this->get('end_location')) ? TourRouteResource::fromArray($this->get('end_location')) : null;
        $this->startLocation = is_array($this->get('startLocation')) ? TourRouteResource::fromArray($this->get('startLocation')) : null;
        $this->startLocation2 = is_array($this->get('start_location')) ? TourRouteResource::fromArray($this->get('start_location')) : null;
        $this->itineraries = self::resourceList($this->array('itineraries'), TourItineraryResource::class);
        $this->calendars = self::resourceList($this->array('calendars'), TourCalendarResource::class);
        $this->tags = self::resourceList($this->array('tags'), TagResource::class);
        $this->travelStyles = self::resourceList($this->array('travelStyles'), TagResource::class);
        $this->travelStyles2 = self::resourceList($this->array('travel_styles'), TagResource::class);
        $this->category = is_array($this->get('category')) ? CategoryResource::fromArray($this->get('category')) : null;
        $this->type = is_array($this->get('type')) ? TourTypeResource::fromArray($this->get('type')) : null;
        $this->subType = is_array($this->get('subType')) ? TourSubTypeResource::fromArray($this->get('subType')) : null;
        $this->subType2 = is_array($this->get('sub_type')) ? TourSubTypeResource::fromArray($this->get('sub_type')) : null;
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
