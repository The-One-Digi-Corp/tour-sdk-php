<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingDetailResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingRefundResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingResource;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;
use TheOneDigi\TourSdk\Laravel\Support\BookingMirrorExtension;

/**
 * Copies an upstream booking into the local mirror.
 *
 * Every amount is taken from the response and nothing is recomputed here.
 * travelo-api is authoritative on price, discount and commission — arithmetic of
 * our own would be a second opinion, and two opinions about one price is how a
 * customer gets charged the wrong number.
 *
 * Keyed on order_code via firstOrNew so a replayed idempotency key, which returns
 * the same booking upstream, updates the same row instead of creating a twin.
 */
class BookingMirror
{
    public function __construct(private readonly int $holdTtlMinutes = 30) {}

    public function write(
        PartnerBookingResource $upstream,
        ?string $idempotencyKey = null,
        int|string|null $ownerId = null,
    ): TourBooking {
        return DB::transaction(function () use ($upstream, $idempotencyKey, $ownerId): TourBooking {
            $payload = $upstream->toArray();

            $booking = TourBooking::firstOrNew(['order_code' => $upstream->orderCode]);

            $booking->fill([
                'status' => $upstream->status !== 0 ? $upstream->status : TourBooking::PENDING_PAYMENT,

                'sub_total' => $upstream->subTotal,
                'discount' => $upstream->discount,
                'total' => $upstream->total,
                'cost' => $upstream->cost,
                'currency' => $upstream->currency !== '' ? $upstream->currency : 'USD',

                'input_sub_total' => $upstream->inputSubTotal,
                'input_discount' => $upstream->inputDiscount,
                'input_total' => $upstream->inputTotal,
                'input_cost' => $upstream->inputCost,
                'input_currency' => $upstream->inputCurrency !== '' ? $upstream->inputCurrency : 'USD',
                'input_currency_version' => $upstream->inputCurrencyVersion,
                'input_currency_exchange_rate' => $upstream->inputCurrencyExchangeRate,

                'promotion_code' => $upstream->promotionCode,
                'name' => $upstream->name,
                'email' => $upstream->email,
                'email2' => $upstream->email2,
                'dial_code' => $upstream->dialCode,
                'phone' => $upstream->phone,
                'paid_at' => $this->blankToNull($upstream->paidAt),
                'upstream_tour_booking_id' => $upstream->id,
            ]);

            // Set once. A replay must not push the hold window forward — the seat
            // upstream expires on its original clock, not ours.
            if (! $booking->exists) {
                $booking->idempotency_key = $idempotencyKey;
                $booking->user_id = $ownerId;
            }

            $booking->save();

            $this->syncApplicants($booking, $upstream);
            $this->syncDetail($booking, $upstream);
            // $this->syncRefund($booking, $upstream);

            // A host with richer local tables fills them from the payload here,
            // inside this transaction, so a booking and its detail rows commit
            // together or not at all.
            BookingMirrorExtension::apply($booking, $payload);

            return $booking;
        });
    }

    public function markStatus(TourBooking $booking, int $status): TourBooking
    {
        $booking->status = $status;
        $booking->save();

        return $booking;
    }

    private function syncApplicants(TourBooking $booking, PartnerBookingResource $upstream): void
    {
        if ($upstream->tourBookingApplicants === []) {
            return;
        }

        $booking->applicants()->delete();

        foreach ($upstream->tourBookingApplicants as $applicant) {
            $payload = is_array($applicant) ? $applicant : $applicant->toArray();

            $booking->applicants()->create([
                'type' => (int) ($payload['type'] ?? 0),
                'full_name' => (string) ($payload['full_name'] ?? ''),
                'gender' => $payload['gender'] ?? null,
                // Upstream sends '' for an absent date or photo, which a date
                // column will not take. Empty means absent, so store null.
                'date_of_birth' => $this->blankToNull($payload['date_of_birth'] ?? null),
                'nationality' => $this->blankToNull($payload['nationality'] ?? null),
                'passport_photo' => $this->blankToNull($payload['passport_photo'] ?? null),
                'upstream_applicant_id' => isset($payload['id']) ? (string) $payload['id'] : null,
            ]);
        }
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }

    private function syncDetail(TourBooking $booking, PartnerBookingResource $upstream): void
    {
        $detail = $upstream->tourBookingDetail;

        if (! $detail instanceof PartnerBookingDetailResource) {
            return;
        }

        $booking->detail()->updateOrCreate(
            ['tour_booking_id' => $booking->id],
            [
                'tour_id' => $detail->tourId,
                'tour_price_group_id' => $detail->tourPriceGroupId,
                'departure_date' => $detail->departureDate,
                'adult_quantity' => $detail->adultQuantity,
                'child_quantity' => $detail->childQuantity,
                'infant_quantity' => $detail->infantQuantity,
                'adult_price' => $detail->adultPrice,
                'child_price' => $detail->childPrice,
                'infant_price' => $detail->infantPrice,
                'discount_price' => $detail->discountPrice,
                'discount_type' => $detail->discountType,
                'discount_count' => $detail->discountCount,
                'special_request' => $detail->specialRequest,
                'currency' => $detail->currency,
                'input_adult_price' => $detail->inputAdultPrice,
                'input_child_price' => $detail->inputChildPrice,
                'input_infant_price' => $detail->inputInfantPrice,
                'input_discount_price' => $detail->inputDiscountPrice,
                'input_currency' => $detail->inputCurrency,
                'input_currency_version' => $detail->inputCurrencyVersion,
                'input_currency_exchange_rate' => $detail->inputCurrencyExchangeRate,
            ],
        );
    }

    private function syncRefund(TourBooking $booking, PartnerBookingResource $upstream): void
    {
        $refund = $upstream->tourBookingRefund;

        if (! $refund instanceof PartnerBookingRefundResource) {
            return;
        }

        // The partner contract carries only the amount and the decision. `reasons`
        // and the feedback columns exist for parity with travelo-api's own table
        // and stay untouched — writing null over them would erase what an admin
        // endpoint might later fill.
        $booking->refund()->updateOrCreate(
            ['tour_booking_id' => $booking->id],
            [
                'refund_total' => $refund->refundTotal,
                'status' => $refund->status,
            ],
        );
    }
}
