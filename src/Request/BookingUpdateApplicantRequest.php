<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use InvalidArgumentException;
use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class BookingUpdateApplicantRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?string $fullName = null,
        public readonly ?int $gender = null,
        public readonly ?string $dateOfBirth = null,
        public readonly ?string $nationality = null,
        public readonly ?string $passportPhoto = null,
        private readonly ?array $provided = null,
    ) {
        if ($this->gender !== null && ! in_array($this->gender, [1, 2], true)) {
            throw new InvalidArgumentException('Applicant gender must be 1, 2, or null.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $has = static fn (string $key): bool => array_key_exists($key, $payload);

        return new self(
            fullName: $has('full_name') && $payload['full_name'] !== null ? (string) $payload['full_name'] : null,
            gender: $has('gender') && $payload['gender'] !== null ? (int) $payload['gender'] : null,
            dateOfBirth: $has('date_of_birth') && $payload['date_of_birth'] !== null ? (string) $payload['date_of_birth'] : null,
            nationality: $has('nationality') && $payload['nationality'] !== null ? (string) $payload['nationality'] : null,
            passportPhoto: $has('passport_photo') && $payload['passport_photo'] !== null ? (string) $payload['passport_photo'] : null,
            provided: array_values(array_intersect([
                'full_name',
                'gender',
                'date_of_birth',
                'nationality',
                'passport_photo',
            ], array_keys($payload))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'full_name' => $this->fullName,
            'gender' => $this->gender,
            'date_of_birth' => $this->dateOfBirth,
            'nationality' => $this->nationality,
            'passport_photo' => $this->passportPhoto,
        ];

        if ($this->provided === null) {
            return $this->withoutNulls($payload);
        }

        return array_intersect_key($payload, array_flip($this->provided));
    }
}
