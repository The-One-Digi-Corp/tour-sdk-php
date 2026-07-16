<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Support;

use TheOneDigi\TourSdk\Laravel\Models\TourBooking;

/**
 * The seam a host uses to fill in booking data the package does not model.
 *
 * The mirror stores only what the package itself reads — order_code, amounts,
 * status — plus the whole upstream response as `upstream_payload`. A host that
 * carries richer local tables (order detail rows, commission snapshots, payment
 * columns) unpacks them from that payload here, inside the same transaction as
 * the core write, so a booking and its detail never exist half-written.
 *
 *   BookingMirrorExtension::extend(function (TourBooking $booking, array $payload) {
 *       TourBookingDetailWriter::fromUpstream($booking, $payload);
 *   });
 *
 * The package knows nothing about those tables — the host does. Registering a
 * callback keeps drop-in drop-in: no controller, no model override, just the
 * mapping the host alone can write.
 */
final class BookingMirrorExtension
{
    /** @var list<callable(TourBooking, array<string, mixed>): void> */
    private static array $extensions = [];

    /**
     * @param callable(TourBooking, array<string, mixed>): void $extension
     */
    public static function extend(callable $extension): void
    {
        self::$extensions[] = $extension;
    }

    /** Registered state is process-global; tests must reset it between cases. */
    public static function flush(): void
    {
        self::$extensions = [];
    }

    public static function hasExtensions(): bool
    {
        return self::$extensions !== [];
    }

    /**
     * @param array<string, mixed> $upstreamPayload
     */
    public static function apply(TourBooking $booking, array $upstreamPayload): void
    {
        foreach (self::$extensions as $extension) {
            $extension($booking, $upstreamPayload);
        }
    }
}
