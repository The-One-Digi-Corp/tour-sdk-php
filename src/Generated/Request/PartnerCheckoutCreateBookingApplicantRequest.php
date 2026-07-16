<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation PartnerCheckoutCreateBookingApplicantRequest (SCHEMA PartnerCheckoutCreateBookingApplicantRequest).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerCheckoutCreateBookingApplicantRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly int $type,
        public readonly ?string $fullName = null,
        public readonly ?int $gender = null,
        public readonly ?string $nationality = null,
        public readonly ?string $dateOfBirth = null,
        public readonly ?string $passportPhoto = null,
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
            type: (int) ($payload['type'] ?? 0),
            fullName: (array_key_exists('full_name', $payload) && $payload['full_name'] !== null ? (string) $payload['full_name'] : null),
            gender: (array_key_exists('gender', $payload) && $payload['gender'] !== null ? (int) $payload['gender'] : null),
            nationality: (array_key_exists('nationality', $payload) && $payload['nationality'] !== null ? (string) $payload['nationality'] : null),
            dateOfBirth: (array_key_exists('date_of_birth', $payload) && $payload['date_of_birth'] !== null ? (string) $payload['date_of_birth'] : null),
            passportPhoto: (array_key_exists('passport_photo', $payload) && $payload['passport_photo'] !== null ? (string) $payload['passport_photo'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'type' => $this->type,
            'full_name' => $this->fullName,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'date_of_birth' => $this->dateOfBirth,
            'passport_photo' => $this->passportPhoto,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /* END MANUAL BODY */
}
