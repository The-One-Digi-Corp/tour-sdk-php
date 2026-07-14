<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Exception;

/**
 * The request never produced an HTTP response (DNS, connect, timeout).
 *
 * Callers must treat this as an UNKNOWN outcome, not a failure: a write may
 * still have been applied server-side. Retry with the same idempotency key.
 */
class TransportException extends TourSdkException
{
}
