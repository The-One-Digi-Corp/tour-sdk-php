<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerTourSyncResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerTourSyncResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly string $code;
    public readonly string $currency;
    public readonly string $tourDirection;
    /** @var array<string, mixed>|list<mixed> */
    public readonly array $routes;
    public readonly float $basePrice;
    public readonly int $day;
    public readonly int $night;
    public readonly string $thumbnail;
    public readonly ?string $createdAt;
    public readonly ?string $updatedAt;
    /** @var array<string, mixed>|list<mixed> */
    public readonly array $translations;
    /** @var array<string, mixed>|list<mixed>|null */
    public readonly ?array $category;
    /** @var array<string, mixed>|list<mixed>|null */
    public readonly ?array $type;
    /** @var array<string, mixed>|list<mixed>|null */
    public readonly ?array $subType;
    /** @var list<PartnerTourSyncPriceResource> */
    public readonly array $prices;
    /** @var list<PartnerTourSyncCalendarResource> */
    public readonly array $calendars;
    public readonly ?TourPriceGroupResource $priceGroup;
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
        $this->currency = $this->string('currency');
        $this->tourDirection = $this->string('tour_direction');
        $this->routes = $this->array('routes');
        $this->basePrice = $this->float('base_price');
        $this->day = $this->int('day');
        $this->night = $this->int('night');
        $this->thumbnail = $this->string('thumbnail');
        $this->createdAt = $this->nullableString('created_at');
        $this->updatedAt = $this->nullableString('updated_at');
        $this->translations = $this->array('translations');
        $this->category = is_array($this->get('category')) ? $this->get('category') : null;
        $this->type = is_array($this->get('type')) ? $this->get('type') : null;
        $this->subType = is_array($this->get('sub_type')) ? $this->get('sub_type') : null;
        $this->prices = self::resourceList($this->array('prices'), PartnerTourSyncPriceResource::class);
        $this->calendars = self::resourceList($this->array('calendars'), PartnerTourSyncCalendarResource::class);
        $this->priceGroup = is_array($this->get('price_group')) ? TourPriceGroupResource::fromArray($this->get('price_group')) : null;
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
