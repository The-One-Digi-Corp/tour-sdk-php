<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
    public function __construct(private readonly int $holdTtlMinutes = 30)
    {
    }

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
                'total' => $upstream->total,
                'currency' => $upstream->currency !== '' ? $upstream->currency : 'USD',
                'input_total' => $upstream->inputTotal,
                'input_currency' => $upstream->inputCurrency !== '' ? $upstream->inputCurrency : null,
                'upstream_payload' => $payload,
            ]);

            // Set once. A replay must not push the hold window forward — the seat
            // upstream expires on its original clock, not ours.
            if (! $booking->exists) {
                $booking->idempotency_key = $idempotencyKey;
                $booking->user_id = $ownerId;
                $booking->held_until = Carbon::now()->addMinutes($this->holdTtlMinutes);
            }

            $booking->save();

            $this->syncApplicants($booking, $upstream);

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
                'upstream_applicant_id' => isset($payload['id']) ? (string) $payload['id'] : null,
                'payload' => $payload,
            ]);
        }
    }
}
