<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema the `data` of GET /tours (partnerToursSearch).
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class TourListResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $currentPage;
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $lastPage;
    /** @var list<PartnerTourResource> */
    public readonly array $tours;
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
        $this->currentPage = $this->int('current_page');
        $this->total = $this->int('total');
        $this->perPage = $this->int('per_page');
        $this->lastPage = $this->int('last_page');
        $this->tours = self::resourceList($this->array('tours'), PartnerTourResource::class);
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /* END MANUAL HYDRATION */
}
