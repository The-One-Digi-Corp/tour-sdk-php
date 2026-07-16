<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk;

/**
 * Verifies webhooks travelo-api sends to the partner (SendPartnerWebhookJob).
 *
 * Signature = hash_hmac('sha256', <raw JSON body>, <partner secret>), delivered
 * in X-Partner-Signature with X-Partner-Timestamp alongside. The same secret
 * signs outbound API calls — rotating it rotates both directions.
 *
 * Always pass the RAW request body. Re-encoding a decoded array can reorder keys
 * or change escaping and will fail verification.
 *
 * Note: the payload carries no unique event id, so callers must de-duplicate on
 * a content hash (order_code + event + status + timestamp).
 */
final class WebhookVerifier
{
    public const HEADER_SIGNATURE = 'X-Partner-Signature';
    public const HEADER_TIMESTAMP = 'X-Partner-Timestamp';

    public function __construct(
        private readonly string $secret,
        private readonly int $maxSkewSeconds = PartnerSigner::MAX_SKEW_SECONDS,
    ) {
    }

    public function isValid(string $rawBody, string $signature, int|string|null $timestamp = null): bool
    {
        if ($signature === '' || $this->secret === '') {
            return false;
        }

        if ($timestamp !== null && !$this->timestampWithinSkew($timestamp)) {
            return false;
        }

        return hash_equals($this->expectedSignature($rawBody), $signature);
    }

    public function expectedSignature(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $this->secret);
    }

    public function timestampWithinSkew(int|string $timestamp): bool
    {
        if (!ctype_digit((string) $timestamp)) {
            return false;
        }

        return abs(time() - (int) $timestamp) <= $this->maxSkewSeconds;
    }

    /**
     * Stable de-duplication key for the webhook inbox.
     *
     * @param array<string, mixed> $payload
     */
    public static function eventHash(array $payload): string
    {
        return hash('sha256', implode('|', [
            (string) ($payload['order_code'] ?? ''),
            (string) ($payload['event'] ?? ''),
            (string) ($payload['status'] ?? ''),
            (string) ($payload['timestamp'] ?? ''),
        ]));
    }
}
