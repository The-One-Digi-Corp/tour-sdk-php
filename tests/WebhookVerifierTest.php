<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use PHPUnit\Framework\TestCase;
use TheOneDigi\TourSdk\WebhookVerifier;

final class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'travelo_test_secret';

    /**
     * Mirrors SendPartnerWebhookJob: signature = hash_hmac over json_encode($payload).
     *
     * @param array<string, mixed> $payload
     */
    private static function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), self::SECRET);
    }

    /**
     * @return array<string, mixed>
     */
    private static function payload(string $event = 'booking.confirmed', int $status = 2): array
    {
        return [
            'event' => $event,
            'product_type' => 'tour',
            'order_code' => 'TB-1',
            'status' => $status,
            'total' => 100.0,
            'currency' => 'USD',
            'timestamp' => time(),
        ];
    }

    public function test_accepts_a_signature_produced_the_way_travelo_api_produces_it(): void
    {
        $payload = self::payload();
        $rawBody = json_encode($payload);

        $verifier = new WebhookVerifier(self::SECRET);

        self::assertTrue($verifier->isValid($rawBody, self::sign($payload), $payload['timestamp']));
    }

    public function test_rejects_a_tampered_body(): void
    {
        $payload = self::payload();
        $signature = self::sign($payload);

        $tampered = json_encode(['...' => 'tampered'] + $payload);

        self::assertFalse((new WebhookVerifier(self::SECRET))->isValid($tampered, $signature));
    }

    public function test_rejects_a_wrong_secret(): void
    {
        $payload = self::payload();

        $verifier = new WebhookVerifier('another_secret');

        self::assertFalse($verifier->isValid(json_encode($payload), self::sign($payload)));
    }

    public function test_rejects_an_empty_signature(): void
    {
        self::assertFalse((new WebhookVerifier(self::SECRET))->isValid('{}', ''));
    }

    public function test_rejects_a_stale_timestamp(): void
    {
        $payload = self::payload();
        $rawBody = json_encode($payload);

        $verifier = new WebhookVerifier(self::SECRET, maxSkewSeconds: 300);

        self::assertFalse($verifier->isValid($rawBody, self::sign($payload), time() - 301));
        self::assertTrue($verifier->isValid($rawBody, self::sign($payload), time() - 299));
    }

    public function test_rejects_a_non_numeric_timestamp(): void
    {
        self::assertFalse((new WebhookVerifier(self::SECRET))->timestampWithinSkew('not-a-number'));
    }

    public function test_event_hash_is_stable_and_distinguishes_status_changes(): void
    {
        $confirmed = self::payload('booking.confirmed', 2);
        $expired = ['event' => 'booking.expired', 'status' => 6] + $confirmed;

        self::assertSame(WebhookVerifier::eventHash($confirmed), WebhookVerifier::eventHash($confirmed));
        self::assertNotSame(WebhookVerifier::eventHash($confirmed), WebhookVerifier::eventHash($expired));
    }
}
