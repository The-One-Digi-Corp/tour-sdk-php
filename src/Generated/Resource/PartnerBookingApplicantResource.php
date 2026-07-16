<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerBookingApplicantResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerBookingApplicantResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly int $tourBookingId;
    public readonly int $type;
    public readonly ?string $fullName;
    public readonly ?int $gender;
    public readonly string $dateOfBirth;
    public readonly ?string $nationality;
    public readonly string $passportPhoto;
    public readonly string $createdAt;
    public readonly string $updatedAt;
    /* END AUTO FIELDS */

    /* BEGIN MANUAL FIELDS */
    /* END MANUAL FIELDS */

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        /* BEGIN AUTO HYDRATION */
        $this->id = $this->int('id');
        $this->tourBookingId = $this->int('tour_booking_id');
        $this->type = $this->int('type');
        $this->fullName = $this->nullableString('full_name');
        $this->gender = $this->nullableInt('gender');
        $this->dateOfBirth = $this->string('date_of_birth');
        $this->nationality = $this->nullableString('nationality');
        $this->passportPhoto = $this->string('passport_photo');
        $this->createdAt = $this->string('created_at');
        $this->updatedAt = $this->string('updated_at');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
