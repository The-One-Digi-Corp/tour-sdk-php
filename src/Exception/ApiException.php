<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Exception;

/**
 * travelo-api answered with a 4xx/5xx envelope.
 */
class ApiException extends TourSdkException
{
    /**
     * @param array<string, mixed> $payload Decoded response body, when JSON.
     */
    public function __construct(
        string $message,
        private readonly int $status,
        private readonly array $payload = [],
        private readonly ?string $correlationId = null,
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    /** Signature rejected, timestamp skewed, or credential revoked. */
    public function isAuthFailure(): bool
    {
        return $this->status === 401;
    }

    /** Credential is missing the scope the route requires (tour:read / tour:book). */
    public function isScopeFailure(): bool
    {
        return $this->status === 403;
    }

    /** Slot sold out, or the booking is no longer in a state that allows this call. */
    public function isConflict(): bool
    {
        return $this->status === 409;
    }
}
