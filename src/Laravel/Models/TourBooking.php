<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Partner-side mirror of a booking held on travelo-api.
 *
 * The status constants match travelo-api's own, deliberately: this row and the
 * upstream reservation describe the same booking, and two different numbering
 * schemes for one state is a bug waiting to happen.
 *
 * @property string $order_code
 * @property int $status
 * @property array<string, mixed>|null $upstream_payload
 */
class TourBooking extends Model
{
    public const PENDING_PAYMENT = 1;
    public const IN_PROGRESS = 2;
    public const COMPLETED = 3;
    public const CANCELED = 4;
    public const REFUNDED = 5;
    public const EXPIRED = 6;

    /** Supplier-call state for the upstream confirm. Unused until payment lands. */
    public const API_NOT_RUN = 0;
    public const API_SUCCESS = 1;
    public const API_FAILED = 2;

    protected $table = 'tour_bookings';

    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'api_status' => 'integer',
        'api_response' => 'array',
        'upstream_payload' => 'array',
        'total' => 'float',
        'input_total' => 'float',
        'held_until' => 'datetime',
    ];

    /**
     * @return HasMany<TourBookingApplicant>
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(TourBookingApplicant::class);
    }

    /**
     * @return HasOne<TourBookingDetail>
     */
    public function detail(): HasOne
    {
        return $this->hasOne(TourBookingDetail::class, 'tour_booking_id');
    }
}
