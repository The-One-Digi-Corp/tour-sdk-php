<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Api;

use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Request\RequestPayload;
use TheOneDigi\TourSdk\Resource\BookingApplicantResource;
use TheOneDigi\TourSdk\Resource\BookingListResource;
use TheOneDigi\TourSdk\Resource\BookingQuoteResource;
use TheOneDigi\TourSdk\Resource\BookingResource;

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
    private const BASE = 'api/partner/bookings';

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
        return $this->client->data($this->client->post(self::BASE, $this->payload($payload), [
            'X-Partner-Idempotency-Key' => $idempotencyKey,
            'X-Idempotency-Key' => $idempotencyKey,
            'Idempotency-Key' => $idempotencyKey,
        ]));
    }

    /**
     * Same endpoint as create(), returned as a typed SDK resource.
     */
    public function createResource(array|RequestPayload $payload, string $idempotencyKey): BookingResource
    {
        return BookingResource::fromArray($this->create($payload, $idempotencyKey));
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

    public function confirmResource(string $code): BookingResource
    {
        return BookingResource::fromArray($this->confirm($code));
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

    public function cancelResource(string $code): BookingResource
    {
        return BookingResource::fromArray($this->cancel($code));
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $code): array
    {
        return $this->client->data($this->client->get(self::BASE . '/' . rawurlencode($code)));
    }

    public function showResource(string $code): BookingResource
    {
        return BookingResource::fromArray($this->show($code));
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

    public function updateApplicantResource(string $code, int $applicantId, array|RequestPayload $payload): BookingApplicantResource
    {
        $data = $this->updateApplicant($code, $applicantId, $payload);
        $applicant = $data['applicant'] ?? $data;

        return BookingApplicantResource::fromArray(is_array($applicant) ? $applicant : []);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array|RequestPayload $payload): array
    {
        return $payload instanceof RequestPayload ? $payload->toArray() : $payload;
    }
}
