<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Common;

/**
 * Centralised API path segments.
 *
 * Every path used internally or exposed by the SDK is defined here so that
 * Api classes, HTTP controllers, and route registrations reference the same
 * constants instead of duplicating string literals.
 *
 * Constants are deliberately short (e.g. QUOTE, not PATH_QUOTE) because they
 * are always qualified by their class — `ApiPaths::QUOTE` already carries
 * "this is a path" in the reader's mind.
 */
class ApiPaths
{
    // ── Base paths ───────────────────────────────────────────────────────

    /** @var string Booking endpoints base. */
    public const BOOKINGS = 'api/partner/bookings';

    /** @var string Tour-catalog endpoints base. */
    public const TOURS = 'api/partner/tours';

    /** @var string Upload links for files kept in Travelo's storage. */
    public const UPLOADS = 'api/partner/uploads';

    /** POST /pre-signed-url */
    public const PRE_SIGNED_URL = '/pre-signed-url';

    // ── Booking sub-paths ────────────────────────────────────────────────

    /** POST /{code}/confirm */
    public const CONFIRM = '/confirm';

    /** POST /{code}/cancel */
    public const CANCEL = '/cancel';

    /** POST /{code}/complete */
    public const COMPLETE = '/complete';

    /** POST /{code}/applicant/{id} */
    public const APPLICANT = '/applicant';

    /** POST /quote */
    public const QUOTE = '/quote';

    /** POST /check-promotion */
    public const CHECK_PROMOTION = '/check-promotion';

    /** POST /refunds */
    public const REFUNDS = '/refunds';

    /** POST /{code}/request-refund */
    public const REQUEST_REFUND = '/request-refund';

    /** POST /{code}/refund */
    public const REFUND = '/refund';

    /** POST /{code}/disapprove-refund */
    public const DISAPPROVE_REFUND = '/disapprove-refund';

    /** POST /{code}/review */
    public const REVIEW = '/review';

    // ── Tour-catalog sub-paths ───────────────────────────────────────────

    /** GET /references */
    public const REFERENCES = '/references';

    /** GET /get-seasonal */
    public const SEASONAL = '/get-seasonal';

    /** GET /get-featured */
    public const FEATURED = '/get-featured';

    /** GET /get-similar */
    public const SIMILAR = '/get-similar';

    /** GET /ids */
    public const IDS = '/ids';

    /** GET /sync */
    public const SYNC = '/sync';

    /** GET /{id}/calendars */
    public const CALENDARS = '/calendars';

    /** GET /{id}/calendar-by-date */
    public const CALENDAR_BY_DATE = '/calendar-by-date';

    /** GET /{id}/get-list-reviews */
    public const REVIEWS = '/get-list-reviews';

    /** GET /{id}/get-all-image-reviews */
    public const REVIEW_IMAGES = '/get-all-image-reviews';

    /** GET /tour-itinerary/{id} */
    public const ITINERARY = '/tour-itinerary';

    /** GET /{id}/get-schedule-tour */
    public const SCHEDULE = '/get-schedule-tour';

}
