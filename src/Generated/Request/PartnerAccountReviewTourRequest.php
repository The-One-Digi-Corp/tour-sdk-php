<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerAccountReviewTour (POST /bookings/{code}/review).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerAccountReviewTourRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly float $rating,
        public readonly string $review,
        public readonly ?string $reviewerName = null,
        public readonly ?array $images = null,
        /**
         * Payload keys fromArray() actually saw, so an explicit null survives
         * toArray(). Empty when the request is built with named arguments.
         *
         * @var list<string>
         */
        protected readonly array $providedKeys = [],
    ) {
        $this->validateManual();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): static
    {
        $payload = static::normalizeManual($payload);

        return new static(
            rating: (float) ($payload['rating'] ?? 0),
            review: (string) ($payload['review'] ?? ''),
            reviewerName: (array_key_exists('reviewer_name', $payload) && $payload['reviewer_name'] !== null ? (string) $payload['reviewer_name'] : null),
            images: (array_key_exists('images', $payload) && is_array($payload['images']) ? $payload['images'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'rating' => $this->rating,
            'review' => $this->review,
            'reviewer_name' => $this->reviewerName,
            'images' => $this->images,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /* END MANUAL BODY */
}
