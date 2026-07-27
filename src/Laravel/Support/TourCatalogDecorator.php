<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Support;

use Illuminate\Http\Request;

/**
 * The one seam where a host folds its own data into the catalog the package
 * serves.
 *
 * The package reads tours from travelo-api and knows nothing about the host's
 * users — so anything user-scoped (a wishlist flag, a "booked before" badge) has
 * to come from the host. It comes through here, as a callback, not through a
 * controller the host overrides: drop-in stays drop-in, and the host's only code
 * is the merge itself.
 *
 *   TourCatalogDecorator::extend(function (array $tours, Request $request): array {
 *       return WishlistMerger::apply($tours, Auth::id());
 *   });
 *
 * A decorator receives the list of tour arrays exactly as upstream returned them
 * and returns the list to send on. It must return one entry per input entry, in
 * order — the package does not reconcile a decorator that adds or drops rows.
 */
final class TourCatalogDecorator
{
    /** @var list<callable(list<array<string, mixed>>, Request): list<array<string, mixed>>> */
    private static array $decorators = [];

    /**
     * @param callable(list<array<string, mixed>>, Request): list<array<string, mixed>> $decorator
     */
    public static function extend(callable $decorator): void
    {
        self::$decorators[] = $decorator;
    }

    /** Registered state is process-global; tests must reset it between cases. */
    public static function flush(): void
    {
        self::$decorators = [];
    }

    public static function hasDecorators(): bool
    {
        return self::$decorators !== [];
    }

    /**
     * @param list<array<string, mixed>> $tours
     * @return list<array<string, mixed>>
     */
    public static function applyList(array $tours, Request $request): array
    {
        foreach (self::$decorators as $decorator) {
            $tours = array_values($decorator($tours, $request));
        }

        return $tours;
    }

    /**
     * @param array<string, mixed> $tour
     * @return array<string, mixed>
     */
    public static function applyOne(array $tour, Request $request): array
    {
        if (! self::hasDecorators()) {
            return $tour;
        }

        $decorated = self::applyList([$tour], $request);

        return $decorated[0] ?? $tour;
    }
}
