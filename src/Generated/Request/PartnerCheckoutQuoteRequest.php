<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
use InvalidArgumentException;
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerCheckoutQuote (POST /bookings/quote).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerCheckoutQuoteRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly string $tourCode,
        public readonly string $departureDate,
        public readonly int $adultQuantity,
        public readonly ?int $childQuantity = null,
        public readonly ?int $infantQuantity = null,
        public readonly ?string $promotionCode = null,
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
            childQuantity: (array_key_exists('child_quantity', $payload) && $payload['child_quantity'] !== null ? (int) $payload['child_quantity'] : null),
            infantQuantity: (array_key_exists('infant_quantity', $payload) && $payload['infant_quantity'] !== null ? (int) $payload['infant_quantity'] : null),
            promotionCode: (array_key_exists('promotion_code', $payload) && $payload['promotion_code'] !== null ? (string) $payload['promotion_code'] : null),
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
            'child_quantity' => $this->childQuantity,
            'infant_quantity' => $this->infantQuantity,
            'promotion_code' => $this->promotionCode,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /**
     * Rejects what travelo-api would reject anyway, here rather than after a round
     * trip. These are cross-field rules; OpenAPI cannot express them, so AUTO will
     * never carry them and this region is their only home.
     *
     * The infant rule mirrors travelo-api's own guard (PartnerApiTest::
     * test_quote_rejects_infants_exceeding_adults): every infant travels on an
     * adult's lap.
     */
    protected function validateManual(): void
    {
        if ($this->tourCode === '') {
            throw new InvalidArgumentException('tourCode is required.');
        }

        if ($this->departureDate === '') {
            throw new InvalidArgumentException('departureDate is required.');
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
    }
    /* END MANUAL BODY */
}
