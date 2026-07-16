<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerAccountListBookings (GET /bookings).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerAccountListBookingsRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?int $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $q = null,
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
            status: (array_key_exists('status', $payload) && $payload['status'] !== null ? (int) $payload['status'] : null),
            dateFrom: (array_key_exists('date_from', $payload) && $payload['date_from'] !== null ? (string) $payload['date_from'] : null),
            dateTo: (array_key_exists('date_to', $payload) && $payload['date_to'] !== null ? (string) $payload['date_to'] : null),
            q: (array_key_exists('q', $payload) && $payload['q'] !== null ? (string) $payload['q'] : null),
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
            'status' => $this->status,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'q' => $this->q,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /* END MANUAL BODY */
}
