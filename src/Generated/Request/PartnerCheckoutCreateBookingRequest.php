<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
use InvalidArgumentException;
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerCheckoutCreateBooking (POST /bookings).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerCheckoutCreateBookingRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly string $tourCode,
        public readonly string $departureDate,
        public readonly int $adultQuantity,
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly array $applicants,
        public readonly ?int $childQuantity = null,
        public readonly ?int $infantQuantity = null,
        public readonly ?string $promotionCode = null,
        public readonly ?string $dialCode = null,
        public readonly ?string $email2 = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $specialRequest = null,
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
            tourCode: (string) ($payload['tour_code'] ?? ''),
            departureDate: (string) ($payload['departure_date'] ?? ''),
            adultQuantity: (int) ($payload['adult_quantity'] ?? 0),
            name: (string) ($payload['name'] ?? ''),
            phone: (string) ($payload['phone'] ?? ''),
            email: (string) ($payload['email'] ?? ''),
            applicants: self::payloadListFromArray((isset($payload['applicants']) && is_array($payload['applicants']) ? $payload['applicants'] : []), PartnerCheckoutCreateBookingApplicantRequest::class),
            childQuantity: (array_key_exists('child_quantity', $payload) && $payload['child_quantity'] !== null ? (int) $payload['child_quantity'] : null),
            infantQuantity: (array_key_exists('infant_quantity', $payload) && $payload['infant_quantity'] !== null ? (int) $payload['infant_quantity'] : null),
            promotionCode: (array_key_exists('promotion_code', $payload) && $payload['promotion_code'] !== null ? (string) $payload['promotion_code'] : null),
            dialCode: (array_key_exists('dial_code', $payload) && $payload['dial_code'] !== null ? (string) $payload['dial_code'] : null),
            email2: (array_key_exists('email2', $payload) && $payload['email2'] !== null ? (string) $payload['email2'] : null),
            idempotencyKey: (array_key_exists('idempotency_key', $payload) && $payload['idempotency_key'] !== null ? (string) $payload['idempotency_key'] : null),
            specialRequest: (array_key_exists('special_request', $payload) && $payload['special_request'] !== null ? (string) $payload['special_request'] : null),
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
            'departure_date' => $this->departureDate,
            'adult_quantity' => $this->adultQuantity,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'applicants' => $this->payloadListToArray($this->applicants, PartnerCheckoutCreateBookingApplicantRequest::class),
            'child_quantity' => $this->childQuantity,
            'infant_quantity' => $this->infantQuantity,
            'promotion_code' => $this->promotionCode,
            'dial_code' => $this->dialCode,
            'email2' => $this->email2,
            'idempotency_key' => $this->idempotencyKey,
            'special_request' => $this->specialRequest,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /**
     * Rejects what travelo-api would reject anyway, here rather than after a round
     * trip. Cross-field rules OpenAPI cannot express, so AUTO will never carry them.
     */
    protected function validateManual(): void
    {
        if ($this->tourCode === '') {
            throw new InvalidArgumentException('tourCode is required.');
        }

        if ($this->departureDate === '') {
            throw new InvalidArgumentException('departureDate is required.');
        }

        if ($this->name === '' || $this->phone === '' || $this->email === '') {
            throw new InvalidArgumentException('name, phone, and email are required.');
        }

        if ($this->adultQuantity < 1) {
            throw new InvalidArgumentException('adultQuantity must be at least 1.');
        }

        if (($this->childQuantity ?? 0) < 0 || ($this->infantQuantity ?? 0) < 0) {
            throw new InvalidArgumentException('childQuantity and infantQuantity must be zero or greater.');
        }

        if (($this->infantQuantity ?? 0) > $this->adultQuantity) {
            throw new InvalidArgumentException('infantQuantity must be less than or equal to adultQuantity.');
        }

        if ($this->applicants === []) {
            throw new InvalidArgumentException('At least one applicant is required.');
        }
    }
    /* END MANUAL BODY */
}
