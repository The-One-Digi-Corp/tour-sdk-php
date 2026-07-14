<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

use TheOneDigi\TourSdk\PartnerClient;

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
class BookingResource
{
    private const BASE = 'api/partner/bookings';

    public function __construct(private readonly PartnerClient $client)
    {
    }

    /**
     * Authoritative price for a selection. Use to fail fast before holding a slot —
     * the amount actually charged must come from create()'s response.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function quote(array $payload): array
    {
        return $this->client->data($this->client->post(self::BASE . '/quote', $payload));
    }

    /**
     * Reserves a slot. Persist $idempotencyKey BEFORE calling: on a timeout the
     * outcome is unknown, and retrying with the same key returns the same booking
     * instead of double-booking.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload, string $idempotencyKey): array
    {
        return $this->client->data($this->client->post(self::BASE, $payload, [
            'X-Partner-Idempotency-Key' => $idempotencyKey,
            'X-Idempotency-Key' => $idempotencyKey,
            'Idempotency-Key' => $idempotencyKey,
        ]));
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

    /**
     * Releases the hold and its slots. Safe to call more than once.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $code): array
    {
        return $this->client->data($this->client->post(self::BASE . '/' . rawurlencode($code) . '/cancel'));
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $code): array
    {
        return $this->client->data($this->client->get(self::BASE . '/' . rawurlencode($code)));
    }

    /**
     * Paginated list. Pagination lives inside `data` alongside `bookings`
     * (current_page / total / per_page / last_page), so unwrapping keeps it.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->client->data($this->client->get(self::BASE, $filters));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function checkPromotion(array $payload): array
    {
        return $this->client->data($this->client->post(self::BASE . '/check-promotion', $payload));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateApplicant(string $code, int $applicantId, array $payload): array
    {
        return $this->client->data($this->client->post(
            self::BASE . '/' . rawurlencode($code) . '/applicant/' . $applicantId,
            $payload,
        ));
    }
}
