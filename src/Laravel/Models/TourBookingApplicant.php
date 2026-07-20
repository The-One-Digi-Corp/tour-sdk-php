<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $upstream_applicant_id
 */
class TourBookingApplicant extends Model
{
    protected $table = 'tour_booking_applicants';

    protected $guarded = [];

    protected $casts = [
        'type' => 'integer',
        'gender' => 'integer',
        'date_of_birth' => 'date:Y-m-d',
    ];

    /**
     * @return BelongsTo<TourBooking, TourBookingApplicant>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'tour_booking_id');
    }

    /**
     * The applicant as the partner API contract shapes it.
     *
     * `id` is the mirror's own id, on purpose: it is the handle the update-applicant
     * route takes in its URL, then resolves to `upstream_applicant_id` before calling
     * travelo-api. Callers address applicants by our id and never see theirs.
     *
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'nationality' => $this->nationality,
            'passport_photo' => $this->passport_photo,
        ];
    }
}
