<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerToursGetSimilar (GET /tours/get-similar).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerToursGetSimilarRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?string $tourCode = null,
        public readonly ?int $tourId = null,
        public readonly ?int $take = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
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
            tourCode: (array_key_exists('tour_code', $payload) && $payload['tour_code'] !== null ? (string) $payload['tour_code'] : null),
            tourId: (array_key_exists('tour_id', $payload) && $payload['tour_id'] !== null ? (int) $payload['tour_id'] : null),
            take: (array_key_exists('take', $payload) && $payload['take'] !== null ? (int) $payload['take'] : null),
            page: (array_key_exists('page', $payload) && $payload['page'] !== null ? (int) $payload['page'] : null),
            perPage: (array_key_exists('per_page', $payload) && $payload['per_page'] !== null ? (int) $payload['per_page'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'tour_code' => $this->tourCode,
            'tour_id' => $this->tourId,
            'take' => $this->take,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /* END MANUAL BODY */
}
