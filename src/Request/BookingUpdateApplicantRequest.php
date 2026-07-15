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
        return new self(
            fullName: isset($payload['full_name']) ? (string) $payload['full_name'] : null,
            gender: isset($payload['gender']) ? (int) $payload['gender'] : null,
            dateOfBirth: isset($payload['date_of_birth']) ? (string) $payload['date_of_birth'] : null,
            nationality: isset($payload['nationality']) ? (string) $payload['nationality'] : null,
            passportPhoto: isset($payload['passport_photo']) ? (string) $payload['passport_photo'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'full_name' => $this->fullName,
            'gender' => $this->gender,
            'date_of_birth' => $this->dateOfBirth,
            'nationality' => $this->nationality,
            'passport_photo' => $this->passportPhoto,
        ]);
    }
}
