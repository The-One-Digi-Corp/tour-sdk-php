<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Support;

use Illuminate\Http\Request;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;

/**
 * The seam a host uses to shape the customer's own booking reads.
 *
 * The package owns the account read endpoints (AccountBookingController): the
 * route, the customer-ownership filter and the refund contract all live in the
 * SDK, and every read comes straight from the local mirror — travelo-api is never
 * called. What the SDK deliberately does NOT own is presentation: grouping the
 * list, joining catalog rows the package never models (tour name, thumbnail,
 * destination) and converting money into the shopper's display currency are all
 * host concerns that depend on tables and a locale only the host has.
 *
 * A host registers that presentation once and the SDK calls back into it:
 *
 *   AccountBookingPresenter::listUsing(fn ($ownerId, Request $r) => [...]);
 *   AccountBookingPresenter::detailUsing(fn (TourBooking $booking, Request $r) => [...]);
 *
 * The list callback is handed the resolved owner id (not a collection): the host
 * typically has a richer join query of its own and re-runs it, so the SDK does not
 * waste a throwaway query building a collection the host would ignore.
 *
 * With nothing registered the endpoints still work — they fall back to the
 * package's own `toContractArray()` shape, so the SDK is useful drop-in and the
 * host layers richness on top only where it wants to.
 */
final class AccountBookingPresenter
{
    /** @var (callable(int|string, Request): array<string, mixed>)|null */
    private static $listPresenter = null;

    /** @var (callable(TourBooking, Request): array<string, mixed>)|null */
    private static $detailPresenter = null;

    /**
     * @param (callable(int|string, Request): array<string, mixed>)|null $presenter
     */
    public static function listUsing(?callable $presenter): void
    {
        self::$listPresenter = $presenter;
    }

    /**
     * @param (callable(TourBooking, Request): array<string, mixed>)|null $presenter
     */
    public static function detailUsing(?callable $presenter): void
    {
        self::$detailPresenter = $presenter;
    }

    public static function hasList(): bool
    {
        return self::$listPresenter !== null;
    }

    /** Registered state is process-global; tests must reset it between cases. */
    public static function flush(): void
    {
        self::$listPresenter = null;
        self::$detailPresenter = null;
    }

    /**
     * Shape the owner's bookings into the list response. Only call when hasList().
     *
     * @return array<string, mixed>
     */
    public static function list(int|string $ownerId, Request $request): array
    {
        return (self::$listPresenter)($ownerId, $request);
    }

    /**
     * Shape one booking into the detail response. Falls back to the package's
     * contract array when the host registered nothing.
     *
     * @return array<string, mixed>
     */
    public static function detail(TourBooking $booking, Request $request): array
    {
        if (self::$detailPresenter !== null) {
            return (self::$detailPresenter)($booking, $request);
        }

        return $booking->toContractArray();
    }
}
