<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partner-side mirror of a refund request travelo-api holds against a booking.
 *
 * The workflow stays upstream: `status` and `refund_total` are copies of
 * travelo-api's decision, never a judgement made here. The partner contract only
 * carries those two — `reasons` and the `feedback_*` columns exist for parity with
 * travelo-api's own table and stay null until an endpoint exposes them.
 *
 * @property int $status
 * @property float $refund_total
 */
class TourBookingRefund extends Model
{
    public const NEW = 1;
    public const PENDING = 2;
    public const APPROVED = 3;
    public const DISAPPROVED = 4;

    protected $table = 'tour_booking_refunds';

    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'refund_total' => 'float',
    ];

    /**
     * @return BelongsTo<TourBooking, TourBookingRefund>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'tour_booking_id');
    }

    /**
     * The refund as the partner API contract shapes it.
     *
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'refund_total' => $this->refund_total,
            'status' => $this->status,
        ];
    }
}
