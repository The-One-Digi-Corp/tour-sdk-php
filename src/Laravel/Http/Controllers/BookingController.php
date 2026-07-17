<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Common\ApiPaths;
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

    /**
     * Authoritative price for a selection.
     *
     * Use to fail fast before holding a slot. Reserves nothing, so it stays public.
     */
    public function quote(Request $request): JsonResponse
    {
        $request->validate([
            'tour_code' => 'required|string|max:80',
            'departure_date' => 'required|date',
            'adult_quantity' => 'required|integer|min:1',
            'child_quantity' => 'nullable|integer|min:0',
            'infant_quantity' => 'nullable|integer|min:0',
            'promotion_code' => 'nullable|string|max:50',
        ]);

        return $this->forward(
            fn () => $this->client->post(BookingApi::BASE . ApiPaths::QUOTE, $request->all()),
            'bookings.quote',
        );
    }

    /**
     * Validate a promotion code.
     */
    public function checkPromotion(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        return $this->forward(
            fn () => $this->client->post(BookingApi::BASE . ApiPaths::CHECK_PROMOTION, $request->all()),
            'bookings.check-promotion',
        );
    }

    /**
     * Create a booking: holds a seat upstream, then mirrors it locally.
     *
     * Public: a customer holds a seat before they have an account (travelo-api
     * creates one from their email), exactly as the storefront does. The mirror's
     * owner is null for a guest — the per-customer read routes are what require a
     * resolved owner. Payment, which confirms the hold, is the consumer's flow;
     * until then the hold expires on travelo-api's clock.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_details' => 'required|array',
            'order_details.tour_id' => 'required|integer|min:1',
            'order_details.departure_date' => 'required|date',
            'order_details.adult_quantity' => 'required|integer|min:1',
            'order_details.child_quantity' => 'nullable|integer|min:0',
            'order_details.infant_quantity' => 'nullable|integer|min:0',
            'order_details.special_request' => 'nullable|string',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'dial_code' => 'nullable|string|max:10',
            'email2' => 'nullable|email|max:255',
            'promotion_code' => 'nullable|string|max:50',
            'applicants' => 'required|array|min:1',
            'applicants.*.type' => 'required|integer|in:1,2,3',
            'applicants.*.full_name' => 'nullable|string|max:255',
            'applicants.*.gender' => 'nullable|integer|in:1,2',
            'applicants.*.nationality' => 'nullable|string|max:5',
            'applicants.*.date_of_birth' => 'nullable|date',
            'applicants.*.passport_photo' => 'nullable|string',
        ]);

        $ownerId = BookingOwner::id();

        return $this->forward(function () use ($request, $ownerId) {
            $idempotencyKey = (string) Str::orderedUuid();

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

    /**
     * List the authenticated user's bookings from the local mirror.
     *
     * Reads from the local mirror, NOT from upstream — upstream returns every
     * booking belonging to the partner (scoped by HMAC client_id, not by customer).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'time' => 'nullable|string|in:all,upcoming,past',
        ]);

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

    /**
     * Get one booking of the authenticated user.
     */
    public function show(string $code): JsonResponse
    {
        $booking = $this->ownedBooking($code);

        if ($booking === null) {
            return $this->notFound();
        }

        return response()->json($this->envelope(200, 'Success', [], $booking->upstream_payload ?? []));
    }

    /**
     * Cancel one booking and release its slots.
     */
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

    /**
     * Update one applicant on a booking.
     *
     * Partial update — only the fields present in the body are changed.
     */
    public function updateApplicant(Request $request, string $code, string $applicantId): JsonResponse
    {
        $request->validate([
            'full_name' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|in:1,2',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:5',
            'passport_photo' => 'nullable|string',
        ]);

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
