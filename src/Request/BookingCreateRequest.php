<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use InvalidArgumentException;
use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class BookingCreateRequest implements RequestPayload
{
    use BuildsPayload;

    /**
     * @param list<BookingApplicantRequest|array<string, mixed>> $applicants
     */
    public function __construct(
        public readonly string $tourCode,
        public readonly string $departureDate,
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly array $applicants,
        public readonly int $adultQuantity = 1,
        public readonly int $childQuantity = 0,
        public readonly int $infantQuantity = 0,
        public readonly ?string $dialCode = null,
        public readonly ?string $email2 = null,
        public readonly ?string $promotionCode = null,
        public readonly ?string $specialRequest = null,
    ) {
        $this->assertValid();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $applicants = $payload['applicants'] ?? [];

        return new self(
            tourCode: (string) ($payload['tour_code'] ?? ''),
            departureDate: (string) ($payload['departure_date'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            phone: (string) ($payload['phone'] ?? ''),
            email: (string) ($payload['email'] ?? ''),
            applicants: is_array($applicants) ? $applicants : [],
            adultQuantity: (int) ($payload['adult_quantity'] ?? 1),
            childQuantity: (int) ($payload['child_quantity'] ?? 0),
            infantQuantity: (int) ($payload['infant_quantity'] ?? 0),
            dialCode: isset($payload['dial_code']) ? (string) $payload['dial_code'] : null,
            email2: isset($payload['email2']) ? (string) $payload['email2'] : null,
            promotionCode: isset($payload['promotion_code']) ? (string) $payload['promotion_code'] : null,
            specialRequest: isset($payload['special_request']) ? (string) $payload['special_request'] : null,
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
            'name' => $this->name,
            'dial_code' => $this->dialCode,
            'phone' => $this->phone,
            'email' => $this->email,
            'email2' => $this->email2,
            'special_request' => $this->specialRequest,
            'applicants' => $this->applicants(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applicants(): array
    {
        return array_values(array_map(
            static fn (BookingApplicantRequest|array $applicant): array => $applicant instanceof BookingApplicantRequest
                ? $applicant->toArray()
                : $applicant,
            $this->applicants,
        ));
    }

    private function assertValid(): void
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

        if ($this->childQuantity < 0 || $this->infantQuantity < 0) {
            throw new InvalidArgumentException('childQuantity and infantQuantity must be zero or greater.');
        }

        if ($this->infantQuantity > $this->adultQuantity) {
            throw new InvalidArgumentException('infantQuantity must be less than or equal to adultQuantity.');
        }

        if ($this->applicants === []) {
            throw new InvalidArgumentException('At least one applicant is required.');
        }
    }
}
