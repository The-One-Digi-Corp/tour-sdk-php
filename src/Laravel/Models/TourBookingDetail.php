<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourBookingDetail extends Model
{
    protected $table = 'tour_booking_details';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date:Y-m-d',
            'adult_quantity' => 'integer',
            'child_quantity' => 'integer',
            'infant_quantity' => 'integer',
            'adult_price' => 'float',
            'child_price' => 'float',
            'infant_price' => 'float',
            'group_price' => 'float',
            'discount_price' => 'float',
            'input_adult_price' => 'float',
            'input_child_price' => 'float',
            'input_infant_price' => 'float',
            'input_group_price' => 'float',
            'input_discount_price' => 'float',
            'input_currency_version' => 'integer',
            'input_currency_exchange_rate' => 'float',
            'tour_id' => 'integer',
            'tour_price_group_id' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'tour_booking_id');
    }
}
