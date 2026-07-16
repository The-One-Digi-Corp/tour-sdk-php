<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Exception;

use Throwable;

/**
 * The request never produced an HTTP response (DNS, connect, timeout).
 *
 * Callers must treat this as an UNKNOWN outcome, not a failure: a write may
 * still have been applied server-side. Retry with the same idempotency key.
 */
class TransportException extends TourSdkException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?string $method = null,
        public readonly ?string $path = null,
        public readonly ?string $url = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function isNetworkFailure(): bool
    {
        return true;
    }
}
