<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerToursGetAvailability (GET /tours/{code}/calendar-by-date).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerToursGetAvailabilityRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly string $date,
        public readonly ?int $pax = null,
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
            date: (string) ($payload['date'] ?? ''),
            pax: (array_key_exists('pax', $payload) && $payload['pax'] !== null ? (int) $payload['pax'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'date' => $this->date,
            'pax' => $this->pax,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /* END MANUAL BODY */
}
