<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class BookingApplicantResource extends ArrayBackedResource
{
    public readonly int|string|null $id;
    public readonly int|string|null $type;
    public readonly string $fullName;
    public readonly int|string|null $gender;
    public readonly ?string $dateOfBirth;
    public readonly ?string $nationality;
    public readonly ?string $passportPhoto;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->id = $this->scalar('id');
        $this->type = $this->scalar('type');
        $this->fullName = $this->string('full_name');
        $this->gender = $this->scalar('gender');
        $this->dateOfBirth = $this->nullableString('date_of_birth');
        $this->nationality = $this->nullableString('nationality');
        $this->passportPhoto = $this->nullableString('passport_photo');
    }
}
