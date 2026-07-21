<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourBookingDetail extends Model
{
    protected $table = 'tour_booking_details';

    protected $guarded = [];

    /**
     * Use the property form for Laravel 10 consumers. The casts() method is only
     * recognised by newer framework versions; on Laravel 10 departure_date was
     * left as a string and the booking email failed while calling ->format().
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_date' => 'date:Y-m-d',
        'adult_quantity' => 'integer',
        'child_quantity' => 'integer',
        'infant_quantity' => 'integer',
        'adult_price' => 'float',
        'child_price' => 'float',
        'infant_price' => 'float',
        'discount_price' => 'float',
        'input_adult_price' => 'float',
        'input_child_price' => 'float',
        'input_infant_price' => 'float',
        'input_discount_price' => 'float',
        'input_currency_version' => 'integer',
        'input_currency_exchange_rate' => 'float',
        'tour_id' => 'integer',
        'tour_price_group_id' => 'integer',
        'discount_type' => 'integer',
        'discount_count' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'tour_booking_id');
    }

    /**
     * The booked line as the partner API contract shapes it.
     *
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'tour_id' => $this->tour_id,
            'tour_price_group_id' => $this->tour_price_group_id,
            'departure_date' => $this->departure_date?->format('Y-m-d'),
            'adult_quantity' => $this->adult_quantity,
            'child_quantity' => $this->child_quantity,
            'infant_quantity' => $this->infant_quantity,
            'adult_price' => $this->adult_price,
            'child_price' => $this->child_price,
            'infant_price' => $this->infant_price,
            'discount_price' => $this->discount_price,
            'discount_type' => $this->discount_type,
            'discount_count' => $this->discount_count,
            'special_request' => $this->special_request,
            // currency is the canonical base (e.g. USD), stored as base_currency
            // in the mirror. Fall back to currency for backward compatibility.
            'currency' => $this->base_currency ?? $this->currency,
            'input_adult_price' => $this->input_adult_price,
            'input_child_price' => $this->input_child_price,
            'input_infant_price' => $this->input_infant_price,
            'input_discount_price' => $this->input_discount_price,
            'input_currency' => $this->input_currency,
            'input_currency_version' => $this->input_currency_version,
            'input_currency_exchange_rate' => $this->input_currency_exchange_rate,
        ];
    }
}
