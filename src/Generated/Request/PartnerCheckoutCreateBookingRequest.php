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
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly array $orderDetails,
        public readonly array $applicants,
        public readonly ?string $dialCode = null,
        public readonly ?string $email2 = null,
        public readonly ?string $promotionCode = null,
        public readonly ?string $idempotencyKey = null,
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
            name: (string) ($payload['name'] ?? ''),
            phone: (string) ($payload['phone'] ?? ''),
            email: (string) ($payload['email'] ?? ''),
            orderDetails: (isset($payload['order_details']) && is_array($payload['order_details']) ? $payload['order_details'] : []),
            applicants: self::payloadListFromArray((isset($payload['applicants']) && is_array($payload['applicants']) ? $payload['applicants'] : []), PartnerCheckoutCreateBookingApplicantRequest::class),
            dialCode: (array_key_exists('dial_code', $payload) && $payload['dial_code'] !== null ? (string) $payload['dial_code'] : null),
            email2: (array_key_exists('email2', $payload) && $payload['email2'] !== null ? (string) $payload['email2'] : null),
            promotionCode: (array_key_exists('promotion_code', $payload) && $payload['promotion_code'] !== null ? (string) $payload['promotion_code'] : null),
            idempotencyKey: (array_key_exists('idempotency_key', $payload) && $payload['idempotency_key'] !== null ? (string) $payload['idempotency_key'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'order_details' => $this->orderDetails,
            'applicants' => $this->payloadListToArray($this->applicants, PartnerCheckoutCreateBookingApplicantRequest::class),
            'dial_code' => $this->dialCode,
            'email2' => $this->email2,
            'promotion_code' => $this->promotionCode,
            'idempotency_key' => $this->idempotencyKey,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /**
     * Rejects what travelo-api would reject anyway, here rather than after a round
     * trip. Cross-field rules OpenAPI cannot express, so AUTO will never carry them.
     */
    protected function validateManual(): void
    {
        // The booking payload is the storefront shape: the tour and departure live
        // under order_details, addressed by numeric tour_id (travelo-api resolves it
        // to a code and enforces the partner whitelist).
        $details = $this->orderDetails;

        if ((int) ($details['tour_id'] ?? 0) < 1) {
            throw new InvalidArgumentException('order_details.tour_id is required.');
        }

        if ((string) ($details['departure_date'] ?? '') === '') {
            throw new InvalidArgumentException('order_details.departure_date is required.');
        }

        if ($this->name === '' || $this->phone === '' || $this->email === '') {
            throw new InvalidArgumentException('name, phone, and email are required.');
        }

        $adult = (int) ($details['adult_quantity'] ?? 0);
        $child = (int) ($details['child_quantity'] ?? 0);
        $infant = (int) ($details['infant_quantity'] ?? 0);

        if ($adult < 1) {
            throw new InvalidArgumentException('order_details.adult_quantity must be at least 1.');
        }

        if ($child < 0 || $infant < 0) {
            throw new InvalidArgumentException('child_quantity and infant_quantity must be zero or greater.');
        }

        if ($infant > $adult) {
            throw new InvalidArgumentException('infant_quantity must be less than or equal to adult_quantity.');
        }

        if ($this->applicants === []) {
            throw new InvalidArgumentException('At least one applicant is required.');
        }
    }
    /* END MANUAL BODY */
}
