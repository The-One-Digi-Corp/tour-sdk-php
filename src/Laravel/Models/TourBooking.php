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
        'sub_total' => 'float',
        'discount' => 'float',
        'total' => 'float',
        'cost' => 'float',
        'input_sub_total' => 'float',
        'input_discount' => 'float',
        'input_total' => 'float',
        'input_cost' => 'float',
        'input_currency_version' => 'integer',
        'input_currency_exchange_rate' => 'float',
        'dial_code' => 'integer',
        'payment_gateway_id' => 'integer',
        'deleted' => 'boolean',
        'paid_at' => 'datetime',
        'upstream_tour_booking_id' => 'string'
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

    /**
     * @return HasOne<TourBookingRefund>
     */
    public function refund(): HasOne
    {
        return $this->hasOne(TourBookingRefund::class, 'tour_booking_id');
    }

    /**
     * The booking as the partner API contract shapes it, rebuilt from the mirror's
     * own columns and relations.
     *
     * This is what the read routes hand back to the frontend. It is a projection of
     * what we stored, not a re-fetch: the read endpoints must never call upstream,
     * which would return every partner booking, not just this customer's. Fields the
     * contract computes but the mirror does not persist — sub_total_amounts,
     * total_amounts, commission — are absent by design.
     *
     * Call with detail, applicants and refund eager-loaded to avoid an N+1.
     *
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'status' => $this->status,
            'sub_total' => $this->sub_total,
            'discount' => $this->discount,
            'total' => $this->total,
            'cost' => $this->cost,
            // currency is the canonical base (e.g. USD), stored in base_currency
            // by the mirror. Fall back to currency for backward compatibility.
            'currency' => $this->base_currency ?? $this->currency,
            'input_sub_total' => $this->input_sub_total,
            'input_discount' => $this->input_discount,
            'input_total' => $this->input_total,
            'input_cost' => $this->input_cost,
            'input_currency' => $this->input_currency,
            'input_currency_version' => $this->input_currency_version,
            'input_currency_exchange_rate' => $this->input_currency_exchange_rate,
            'promotion_code' => $this->promotion_code,
            'name' => $this->name,
            'email' => $this->email,
            'email2' => $this->email2,
            'dial_code' => $this->dial_code,
            'phone' => $this->phone,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'tour_booking_detail' => $this->detail?->toContractArray(),
            'tour_booking_applicants' => $this->applicants
                ->map(fn(TourBookingApplicant $applicant) => $applicant->toContractArray())
                ->all(),
            'tour_booking_refund' => $this->refund?->toContractArray(),
        ];
    }
}
