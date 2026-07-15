<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema CategoryResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class CategoryResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly ?string $icon;
    public readonly string $type;
    public readonly string $name;
    public readonly string $slug;
    public readonly ?string $description;
    public readonly ?string $remainingTranslationSlug;
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
        $this->icon = $this->nullableString('icon');
        $this->type = $this->string('type');
        $this->name = $this->string('name');
        $this->slug = $this->string('slug');
        $this->description = $this->nullableString('description');
        $this->remainingTranslationSlug = $this->nullableString('remaining_translation_slug');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
