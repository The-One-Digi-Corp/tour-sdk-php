<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Common\ApiPaths;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingResource;
use TheOneDigi\TourSdk\Laravel\Mail\BookingCreated;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsApiErrors;
use TheOneDigi\TourSdk\Laravel\Http\Concerns\ForwardsRequestContext;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;
use TheOneDigi\TourSdk\Laravel\Services\BookingMirror;
use TheOneDigi\TourSdk\Laravel\Services\CustomerProvisioner;
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
    use ForwardsRequestContext;

    public function __construct(
        private readonly BookingApi $bookings,
        private readonly PartnerClient $client,
        private readonly BookingMirror $mirror,
        private readonly CustomerProvisioner $provisioner,
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
            fn () => $this->client->post(BookingApi::BASE . ApiPaths::QUOTE, $request->all(), $this->contextHeaders($request)),
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
            fn () => $this->client->post(BookingApi::BASE . ApiPaths::CHECK_PROMOTION, $request->all(), $this->contextHeaders($request)),
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
            'applicants.*.nationality' => 'nullable|string|max:50',
            'applicants.*.date_of_birth' => 'nullable|date',
            'applicants.*.passport_photo' => 'nullable|string',
        ]);

        /** @var array{id: int|string|null, welcome_email: ?string, welcome_mail: ?\TheOneDigi\TourSdk\Laravel\Mail\CustomerAccountCreated}|null $provisioned */
        $provisioned = null;
        $mirrored = null;

        $response = $this->forward(function () use ($request, &$provisioned, &$mirrored) {
            $idempotencyKey = (string) Str::orderedUuid();

            $body = $request->all();
            $body['idempotency_key'] = $idempotencyKey;

            // Forward the shopper's own currency: travelo-api freezes it as the
            // order's input_currency, which is what the payment screen shows the
            // customer. The headline amounts come back canonical (USD) from the
            // unified partner contract regardless, so the mirror stores USD while
            // the input_* snapshot keeps the shopper's figures — no forcing needed.
            $headers = ['X-Partner-Idempotency-Key' => $idempotencyKey] + $this->contextHeaders($request);

            $envelope = $this->client->post(BookingApi::BASE, $body, $headers);

            // travelo-api's partner endpoint returns the booking directly under
            // `data`; the storefront shape nests it under `data.order`. Accept
            // both — exactly as BookingApi::create() does — then always re-expose
            // it under `data.order` so the consumer contract is stable regardless
            // of which shape upstream used.
            $data = is_array($envelope['data'] ?? null) ? $envelope['data'] : [];
            $order = (isset($data['order']) && is_array($data['order'])) ? $data['order'] : $data;

            if (is_array($order) && $order !== []) {
                $resolved = $this->provisioner->resolve([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                ]);

                $mirrored = $this->mirror->write(PartnerBookingResource::fromArray($order), $idempotencyKey, $resolved['id']);

                // Set only after the mirror commits: a confirmation email for a
                // booking that failed to persist would be a lie.
                $provisioned = $resolved;

                // travelo-api already returns the unified currency shape (`currency`
                // is the canonical base, `input_*` carries the shopper's figures), so
                // there is nothing to remap — only the envelope wrapper is normalised.
                $envelope['data'] = ['order' => $order];
            }

            return $envelope;
        }, 'bookings.store');

        $this->sendWelcomeEmail($provisioned, $response);
        $this->sendBookingConfirmation($mirrored, $response);

        return $response;
    }

    /**
     * Email the customer that their booking was created.
     *
     * Fires for every successful booking (unlike the welcome mail, which is only
     * for a brand-new account). Sent after the response and outside the booking
     * transaction — a held seat must not roll back because SMTP was down, so a mail
     * failure is reported and swallowed.
     */
    private function sendBookingConfirmation(?TourBooking $booking, JsonResponse $response): void
    {
        if ($booking === null || $response->getStatusCode() >= 300) {
            return;
        }

        if (! (bool) config('travelo.booking.send_confirmation_email', true)) {
            return;
        }

        $email = $booking->email;

        if (! is_string($email) || $email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new BookingCreated($booking->loadMissing('detail')));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Send the generated-password email for an account this booking just created.
     *
     * After the response, never inside the booking transaction: a booking that
     * holds a seat must not roll back because an SMTP server was down, so a mail
     * failure is reported and swallowed. Skipped unless the booking succeeded.
     *
     * @param array{id: int|string|null, welcome_email: ?string, welcome_mail: ?\TheOneDigi\TourSdk\Laravel\Mail\CustomerAccountCreated}|null $provisioned
     */
    private function sendWelcomeEmail(?array $provisioned, JsonResponse $response): void
    {
        if ($provisioned === null || $provisioned['welcome_mail'] === null || $provisioned['welcome_email'] === null) {
            return;
        }

        if ($response->getStatusCode() >= 300) {
            return;
        }

        try {
            Mail::to($provisioned['welcome_email'])->send($provisioned['welcome_mail']);
        } catch (\Throwable $e) {
            report($e);
        }
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
            ->with(['detail', 'applicants', 'refund'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json($this->envelope(200, 'Success', [], [
            'current_page' => $page->currentPage(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'last_page' => $page->lastPage(),
            'bookings' => array_map(
                fn (TourBooking $booking) => $booking->toContractArray(),
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

        $booking->load(['detail', 'applicants', 'refund']);

        return response()->json($this->envelope(200, 'Success', [], $booking->toContractArray()));
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
     *
     * The URL receives the local mirror's applicant id (returned by /_booking/{code}).
     * The method resolves it to the upstream (travelo-api) applicant id before
     * forwarding the update, so callers never need to know travelo-api's primary key.
     */
    public function updateApplicant(Request $request, string $code, string $applicantId): JsonResponse
    {
        $request->validate([
            'full_name' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|in:1,2',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'passport_photo' => 'nullable|string',
        ]);

        $booking = TourBooking::where('order_code', $code)->first();

        if (! $booking) {
            return response()->json($this->envelope(404, 'Booking not found.'), 404);
        }

        $localApplicant = $booking->applicants()->whereKey($applicantId)->first();

        if (! $localApplicant) {
            return response()->json($this->envelope(404, 'Passenger not found.'), 404);
        }

        $upstreamApplicantId = $localApplicant->upstream_applicant_id ?? $applicantId;

        return $this->forward(function () use ($request, $code, $upstreamApplicantId) {
            $data = $this->bookings->updateApplicant($code, (int) $upstreamApplicantId, $request->all());

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
