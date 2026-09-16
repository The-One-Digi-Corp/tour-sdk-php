<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema TourReviewResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourReviewResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly int $tourId;
    public readonly int $userId;
    public readonly float $rating;
    public readonly string $review;
    public readonly ?string $createdAt;
    public readonly ?string $updatedAt;
    /** @var list<TourReviewImageResource> */
    public readonly array $images;
    /** @var array<string, mixed>|list<mixed>|null */
    public readonly ?array $user;
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
        $this->userId = $this->int('user_id');
        $this->rating = $this->float('rating');
        $this->review = $this->string('review');
        $this->createdAt = $this->nullableString('created_at');
        $this->updatedAt = $this->nullableString('updated_at');
        $this->images = self::resourceList($this->array('images'), TourReviewImageResource::class);
        $this->user = is_array($this->get('user')) ? $this->get('user') : null;
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
