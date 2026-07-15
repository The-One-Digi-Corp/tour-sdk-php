<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use InvalidArgumentException;
use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class BookingQuoteRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly string $tourCode,
        public readonly string $departureDate,
        public readonly int $adultQuantity = 1,
        public readonly int $childQuantity = 0,
        public readonly int $infantQuantity = 0,
        public readonly ?string $promotionCode = null,
    ) {
        $this->assertQuantities();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            tourCode: (string) ($payload['tour_code'] ?? ''),
            departureDate: (string) ($payload['departure_date'] ?? ''),
            adultQuantity: (int) ($payload['adult_quantity'] ?? 1),
            childQuantity: (int) ($payload['child_quantity'] ?? 0),
            infantQuantity: (int) ($payload['infant_quantity'] ?? 0),
            promotionCode: isset($payload['promotion_code']) ? (string) $payload['promotion_code'] : null,
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

    private function assertQuantities(): void
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

        if ($this->childQuantity < 0 || $this->infantQuantity < 0) {
            throw new InvalidArgumentException('childQuantity and infantQuantity must be zero or greater.');
        }

        if ($this->infantQuantity > $this->adultQuantity) {
            throw new InvalidArgumentException('infantQuantity must be less than or equal to adultQuantity.');
        }
    }
}
