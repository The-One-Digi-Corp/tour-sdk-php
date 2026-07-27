<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Who a mirrored booking belongs to, in the consuming app's terms.
 *
 * The SDK has no idea what a user is here — travelo-api authenticates the partner,
 * not the partner's customers. So ownership is the consumer's answer to give, and
 * every booking read filters on it.
 *
 * A resolver lives here rather than in config because config may be cached, and
 * `config:cache` cannot serialise a closure — it would fail at deploy time, on the
 * one machine nobody tests on.
 *
 *   TheOneDigi\TourSdk\Laravel\Support\BookingOwner::resolveUsing(fn () => Auth::guard('web')->id());
 */
final class BookingOwner
{
    /** @var (callable(): int|string|null)|null */
    private static $resolver = null;

    /**
     * @param (callable(): int|string|null)|null $resolver
     */
    public static function resolveUsing(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function id(): int|string|null
    {
        if (self::$resolver !== null) {
            return (self::$resolver)();
        }

        return Auth::id();
    }
}
