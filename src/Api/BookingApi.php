<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Api;

use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Common\RequestPayload;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingApplicantResource;
use TheOneDigi\TourSdk\Generated\Resource\BookingListResource;
use TheOneDigi\TourSdk\Generated\Resource\BookingQuoteResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingResource;

/**
 * Partner booking endpoints.
 *
 * Two-step booking: create() reserves the slot (PENDING_PAYMENT) and starts the
 * hold TTL; the partner collects payment on their side, then calls confirm().
 * Holds never confirmed are expired server-side and their seats released.
 *
 * Read calls need the `tour:read` scope, writes need `tour:book`.
 *
 * Not final: consumers inject and double this directly.
 */
class BookingApi
{
    /** Public so controller mode can build the same paths without restating them. */
    public const BASE = 'api/partner/bookings';

    public function __construct(private readonly PartnerClient $client)
    {
    }

    /**
     * Authoritative price for a selection. Use to fail fast before holding a slot —
     * the amount actually charged must come from create()'s response.
     *
     * @return array<string, mixed>
     */
    public function quote(array|RequestPayload $payload): array
    {
        return $this->client->data($this->client->post(self::BASE . '/quote', $this->payload($payload)));
    }

    /**
     * Same endpoint as quote(), returned as a typed SDK resource.
     */
    public function quoteResource(array|RequestPayload $payload): BookingQuoteResource
    {
        return BookingQuoteResource::fromArray($this->quote($payload));
    }

    /**
     * Reserves a slot. Persist $idempotencyKey BEFORE calling: on a timeout the
     * outcome is unknown, and retrying with the same key returns the same booking
     * instead of double-booking.
     *
     * @return array<string, mixed>
     */
    public function create(array|RequestPayload $payload, string $idempotencyKey): array
    {
        // The key travels in the body, because that is the channel the contract
        // documents. travelo-api also accepts it as X-Partner-Idempotency-Key and
        // two older header spellings, and this SDK used to send only those — so the
        // one thing standing between a timed-out retry and a double booking was a
        // header no schema mentioned. Anyone tidying up an undocumented header
        // would have taken idempotency with it, silently.
        //
        // The header goes too, as the documented alias, for gateways that strip or
        // rewrite bodies on retry.
        $body = $this->payload($payload);
        $body['idempotency_key'] = $idempotencyKey;

        $data = $this->client->data($this->client->post(self::BASE, $body, [
            'X-Partner-Idempotency-Key' => $idempotencyKey,
        ]));

        // Create returns the booking under `order` (matching the storefront shape),
        // unlike the other booking reads which return the resource directly. Unwrap
        // it so createResource() and callers get the resource, not the wrapper.
        return isset($data['order']) && is_array($data['order']) ? $data['order'] : $data;
    }

    /**
     * Same endpoint as create(), returned as a typed SDK resource.
     */
    public function createResource(array|RequestPayload $payload, string $idempotencyKey): PartnerBookingResource
    {
        return PartnerBookingResource::fromArray($this->create($payload, $idempotencyKey));
    }

    /**
     * Marks payment received: PENDING_PAYMENT → IN_PROGRESS. Idempotent.
     * Call only after the money is actually settled.
     *
     * @return array<string, mixed>
     */
    public function confirm(string $code): array
    {
        return $this->client->data($this->client->post(self::BASE . '/' . rawurlencode($code) . '/confirm'));
    }

    public function confirmResource(string $code): PartnerBookingResource
    {
        return PartnerBookingResource::fromArray($this->confirm($code));
    }

    /**
     * Releases the hold and its slots. Safe to call more than once.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $code): array
    {
        return $this->client->data($this->client->post(self::BASE . '/' . rawurlencode($code) . '/cancel'));
    }

    public function cancelResource(string $code): PartnerBookingResource
    {
        return PartnerBookingResource::fromArray($this->cancel($code));
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $code): array
    {
        return $this->client->data($this->client->get(self::BASE . '/' . rawurlencode($code)));
    }

    public function showResource(string $code): PartnerBookingResource
    {
        return PartnerBookingResource::fromArray($this->show($code));
    }

    /**
     * Paginated list. Pagination lives inside `data` alongside `bookings`
     * (current_page / total / per_page / last_page), so unwrapping keeps it.
     *
     * @return array<string, mixed>
     */
    public function list(array|RequestPayload $filters = []): array
    {
        return $this->client->data($this->client->get(self::BASE, $this->payload($filters)));
    }

    public function listResources(array|RequestPayload $filters = []): BookingListResource
    {
        return BookingListResource::fromArray($this->list($filters));
    }

    /**
     * @return array<string, mixed>
     */
    public function checkPromotion(array|RequestPayload $payload): array
    {
        return $this->client->data($this->client->post(self::BASE . '/check-promotion', $this->payload($payload)));
    }

    /**
     * @return array<string, mixed>
     */
    public function updateApplicant(string $code, int $applicantId, array|RequestPayload $payload): array
    {
        return $this->client->data($this->client->post(
            self::BASE . '/' . rawurlencode($code) . '/applicant/' . $applicantId,
            $this->payload($payload),
        ));
    }

    public function updateApplicantResource(string $code, int $applicantId, array|RequestPayload $payload): PartnerBookingApplicantResource
    {
        $data = $this->updateApplicant($code, $applicantId, $payload);
        $applicant = $data['applicant'] ?? $data;

        return PartnerBookingApplicantResource::fromArray(is_array($applicant) ? $applicant : []);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array|RequestPayload $payload): array
    {
        return $payload instanceof RequestPayload ? $payload->toArray() : $payload;
    }
}
