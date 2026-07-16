<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $upstream_applicant_id
 * @property array<string, mixed>|null $payload
 */
class TourBookingApplicant extends Model
{
    protected $table = 'tour_booking_applicants';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * @return BelongsTo<TourBooking, TourBookingApplicant>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'tour_booking_id');
    }
}
