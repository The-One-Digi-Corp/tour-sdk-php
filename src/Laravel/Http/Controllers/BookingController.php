<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingResource;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsApiErrors;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;
use TheOneDigi\TourSdk\Laravel\Services\BookingMirror;
use TheOneDigi\TourSdk\Laravel\Support\BookingOwner;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * Booking endpoints for controller mode.
 *
 * Reads come from the local mirror, NOT from upstream — and that is a security
 * boundary, not an optimisation. `GET api/partner/bookings` upstream returns every
 * booking belonging to the partner, scoped by the client_id in the HMAC rather
 * than by any customer. Forwarding it would hand each visitor the names, emails
 * and amounts of everyone else's bookings. The mirror knows who owns what; upstream
 * does not.
 *
 * There is deliberately no confirm() here. Confirming turns a held seat into a sold
 * one, and nothing on this side knows whether money arrived — that answer lives in
 * the consumer's payment flow. A route would let anyone confirm a booking nobody
 * paid for, which is exactly what an HTTP endpoint reachable from a browser is.
 */
class BookingController
{
    use ForwardsApiErrors;

    public function __construct(
        private readonly BookingApi $bookings,
        private readonly PartnerClient $client,
        private readonly BookingMirror $mirror,
    ) {
    }

    /** Authoritative price for a selection. Reserves nothing, so it stays public. */
    public function quote(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->post(BookingApi::BASE . '/quote', $request->all()),
            'bookings.quote',
        );
    }

    public function checkPromotion(Request $request): JsonResponse
    {
        return $this->forward(
            fn () => $this->client->post(BookingApi::BASE . '/check-promotion', $request->all()),
            'bookings.check-promotion',
        );
    }

    /**
     * Holds a seat upstream, then mirrors it.
     *
     * Public: a customer holds a seat before they have an account (travelo-api
     * creates one from their email), exactly as the storefront does. The mirror's
     * owner is null for a guest — the per-customer read routes are what require a
     * resolved owner. Payment, which confirms the hold, is the consumer's flow;
     * until then the hold expires on travelo-api's clock.
     */
    public function store(Request $request): JsonResponse
    {
        $ownerId = BookingOwner::id();

        return $this->forward(function () use ($request, $ownerId) {
            // Generated here, never accepted from the caller. A key the client picks
            // is a key the client can regenerate on retry, and a fresh key on a
            // timed-out retry books a second seat.
            $idempotencyKey = (string) Str::orderedUuid();

            // Passthrough: travelo-api now speaks the storefront shape the frontend
            // sends, so the body is forwarded verbatim and the response returned
            // verbatim (booking under `data.order`). The only local work is the
            // mirror, written from that booking.
            $body = $request->all();
            $body['idempotency_key'] = $idempotencyKey;

            $envelope = $this->client->post(BookingApi::BASE, $body, [
                'X-Partner-Idempotency-Key' => $idempotencyKey,
            ]);

            $order = $envelope['data']['order'] ?? null;

            if (is_array($order) && $order !== []) {
                $this->mirror->write(PartnerBookingResource::fromArray($order), $idempotencyKey, $ownerId);
            }

            return $envelope;
        }, 'bookings.store');
    }

    public function index(Request $request): JsonResponse
    {
        $ownerId = BookingOwner::id();

        if ($ownerId === null) {
            return $this->unauthenticated();
        }

        $perPage = min(max((int) $request->query('per_page', '20'), 1), 100);

        $page = TourBooking::query()
            ->where('user_id', $ownerId)
            ->latest('id')
            ->paginate($perPage);

        return response()->json($this->envelope(200, 'Success', [], [
            'current_page' => $page->currentPage(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'last_page' => $page->lastPage(),
            'bookings' => array_map(
                fn (TourBooking $booking) => $booking->upstream_payload ?? [],
                $page->items(),
            ),
        ]));
    }

    public function show(string $code): JsonResponse
    {
        $booking = $this->ownedBooking($code);

        if ($booking === null) {
            return $this->notFound();
        }

        return response()->json($this->envelope(200, 'Success', [], $booking->upstream_payload ?? []));
    }

    public function cancel(string $code): JsonResponse
    {
        $booking = $this->ownedBooking($code);

        if ($booking === null) {
            return $this->notFound();
        }

        return $this->forward(function () use ($booking, $code) {
            $upstream = $this->bookings->cancelResource($code);
            $this->mirror->write($upstream);
            $this->mirror->markStatus($booking->refresh(), TourBooking::CANCELED);

            return $this->envelope(200, 'Success', [], $upstream->toArray());
        }, 'bookings.cancel');
    }

    public function updateApplicant(Request $request, string $code, string $applicantId): JsonResponse
    {
        $booking = $this->ownedBooking($code);

        if ($booking === null) {
            return $this->notFound();
        }

        return $this->forward(function () use ($request, $code, $applicantId) {
            $data = $this->bookings->updateApplicant($code, (int) $applicantId, $request->all());

            return $this->envelope(200, 'Success', [], $data);
        }, 'bookings.update-applicant');
    }

    /**
     * Ownership check and existence check in one. A booking someone else owns is
     * reported as absent rather than forbidden — 403 would confirm the code exists,
     * and booking codes are guessable enough for that to matter.
     */
    private function ownedBooking(string $code): ?TourBooking
    {
        $ownerId = BookingOwner::id();

        if ($ownerId === null) {
            return null;
        }

        return TourBooking::query()
            ->where('order_code', $code)
            ->where('user_id', $ownerId)
            ->first();
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
