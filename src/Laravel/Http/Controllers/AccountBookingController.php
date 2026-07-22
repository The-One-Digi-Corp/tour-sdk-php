<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsApiErrors;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;
use TheOneDigi\TourSdk\Laravel\Support\AccountBookingPresenter;
use TheOneDigi\TourSdk\Laravel\Support\BookingOwner;

/**
 * The signed-in customer's own tour bookings, read straight from the local
 * mirror (travelo_partner_db). This controller NEVER calls travelo-api.
 *
 * It is deliberately split from BookingController, which is the upstream side —
 * quoting, holding, cancelling and refunding all forward to travelo-api. Reading
 * "my bookings" needs none of that: the mirror already holds every booking this
 * partner made. Keeping the two apart makes the boundary obvious — anything here
 * is local and instant; anything on BookingController crosses the network.
 *
 * Presentation (list grouping, catalog names, display currency) is the host's
 * through AccountBookingPresenter; the SDK owns the route, the ownership filter
 * and the refund contract that ships in the mirror.
 */
class AccountBookingController
{
    use ForwardsApiErrors;

    /**
     * List the signed-in customer's tour bookings from the mirror.
     *
     * The host presenter (if registered) owns the shape — grouping, catalog
     * enrichment and display currency. Without one, a flat contract-array list is
     * returned so the endpoint is useful drop-in.
     */
    public function list(Request $request): JsonResponse
    {
        $ownerId = BookingOwner::id();

        if ($ownerId === null) {
            return $this->unauthenticated();
        }

        if (AccountBookingPresenter::hasList()) {
            return response()->json(
                $this->envelope(200, 'Success', [], AccountBookingPresenter::list($ownerId, $request)),
            );
        }

        $bookings = TourBooking::query()
            ->where('user_id', $ownerId)
            ->with(['detail', 'applicants', 'refund'])
            ->latest('id')
            ->get();

        return response()->json($this->envelope(200, 'Success', [], [
            'bookings' => $bookings->map(fn (TourBooking $booking) => $booking->toContractArray())->values()->all(),
        ]));
    }

    /**
     * One booking of the signed-in customer, by order code.
     *
     * A booking someone else owns is reported as absent rather than forbidden — a
     * 403 would confirm the code exists, and order codes are guessable enough for
     * that to matter.
     */
    public function show(Request $request, string $code): JsonResponse
    {
        $ownerId = BookingOwner::id();

        if ($ownerId === null) {
            return $this->unauthenticated();
        }

        $booking = TourBooking::query()
            ->where('order_code', $code)
            ->where('user_id', $ownerId)
            ->with(['detail', 'applicants', 'refund'])
            ->first();

        if ($booking === null) {
            return $this->notFound();
        }

        return response()->json(
            $this->envelope(200, 'Success', [], AccountBookingPresenter::detail($booking, $request)),
        );
    }

    private function notFound(): JsonResponse
    {
        return response()->json($this->envelope(404, 'Booking not found.'), 404);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json($this->envelope(401, 'Unauthenticated.'), 401);
    }
}
