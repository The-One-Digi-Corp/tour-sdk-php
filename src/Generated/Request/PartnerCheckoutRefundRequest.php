<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Request;

use TheOneDigi\TourSdk\Common\BuildsPayload;
use TheOneDigi\TourSdk\Common\RequestPayload;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI operation partnerCheckoutRefund (POST /bookings/{code}/refund).
 *
 * Everything outside MANUAL BODY is rewritten by composer generate:contract.
 * MANUAL BODY survives regeneration: override normalizeManual() there to fold
 * travelo-api's backward-compatible query aliases onto one canonical key before
 * fromArray() maps them.
 */
class PartnerCheckoutRefundRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly float $refundTotal,
        public readonly ?string $reasons = null,
        public readonly ?string $feedbackStaff = null,
        public readonly ?string $feedbackManager = null,
        /**
         * Payload keys fromArray() actually saw, so an explicit null survives
         * toArray(). Empty when the request is built with named arguments.
         *
         * @var list<string>
         */
        protected readonly array $providedKeys = [],
    ) {
        $this->validateManual();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): static
    {
        $payload = static::normalizeManual($payload);

        return new static(
            refundTotal: (float) ($payload['refund_total'] ?? 0),
            reasons: (array_key_exists('reasons', $payload) && $payload['reasons'] !== null ? (string) $payload['reasons'] : null),
            feedbackStaff: (array_key_exists('feedback_staff', $payload) && $payload['feedback_staff'] !== null ? (string) $payload['feedback_staff'] : null),
            feedbackManager: (array_key_exists('feedback_manager', $payload) && $payload['feedback_manager'] !== null ? (string) $payload['feedback_manager'] : null),
            providedKeys: array_keys($payload),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'refund_total' => $this->refundTotal,
            'reasons' => $this->reasons,
            'feedback_staff' => $this->feedbackStaff,
            'feedback_manager' => $this->feedbackManager,
        ]);
    }

    /* BEGIN MANUAL BODY */
    /* END MANUAL BODY */
}
