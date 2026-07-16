<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Laravel\Http\Controllers\CatalogProxyController;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Api\TourApi;
use TheOneDigi\TourSdk\WebhookVerifier;

/**
 * Auto-discovered. Installing the package is the whole integration: the client,
 * its API classes and the webhook verifier are wired from config, so a consumer
 * writes no boilerplate and every consumer wires them identically.
 *
 * What this deliberately does NOT do is register routes for booking writes.
 * `confirm` turns a held seat into a sold one; when that is allowed is the
 * consumer's payment policy, not the SDK's. Auto-exposing it would hand every
 * partner an endpoint that confirms bookings nobody paid for. Catalog reads are
 * opt-in via `Route::traveloCatalog()`.
 */
class TourSdkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/travelo.php', 'travelo');

        $this->app->singleton(PartnerClient::class, static fn (Application $app) => PartnerClient::fromConfig([
            'base_url' => $app['config']->get('travelo.base_url'),
            'client_id' => $app['config']->get('travelo.client_id'),
            'secret' => $app['config']->get('travelo.secret'),
            'currency' => $app['config']->get('travelo.currency'),
            'timeout' => $app['config']->get('travelo.timeout'),
            'integration_name' => $app['config']->get('travelo.integration.name'),
            'integration_version' => $app['config']->get('travelo.integration.version'),
        ]));

        // Bound so consumers can inject exactly the API surface they use, and double it
        // in tests, instead of hand-rolling a wrapper each time.
        $this->app->bind(BookingApi::class, static fn (Application $app) => $app->make(PartnerClient::class)->bookings());
        $this->app->bind(TourApi::class, static fn (Application $app) => $app->make(PartnerClient::class)->tours());

        $this->app->singleton(WebhookVerifier::class, static fn (Application $app) => new WebhookVerifier(
            (string) $app['config']->get('travelo.secret'),
            (int) $app['config']->get('travelo.webhook_max_skew_seconds', 300),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/travelo.php' => $this->app->configPath('travelo.php'),
            ], 'travelo-config');
        }

        $this->registerCatalogMacro();
    }

    /**
     * Opt-in read-only catalog pass-through:
     *
     *   Route::traveloCatalog();                       // GET /catalog/tours...
     *   Route::traveloCatalog('v2', ['auth:api']);     // your prefix, your guards
     *
     * Responses are forwarded verbatim, so callers keep travelo-api's shape. The
     * consumer chooses the URL and the middleware — the SDK does not squat on either.
     */
    private function registerCatalogMacro(): void
    {
        if (Route::hasMacro('traveloCatalog')) {
            return;
        }

        Route::macro('traveloCatalog', function (string $prefix = 'catalog', array $middleware = []) {
            // A group, not Route::prefix(): a prefix declared on a route inside an
            // existing group is merged with prependExistingPrefix = false, so
            // calling this from routes/api.php would yield "catalog/api/..." rather
            // than "api/catalog/...". Groups merge the other way round.
            return Route::group(['prefix' => $prefix, 'middleware' => $middleware], function () use ($prefix) {
                Route::get('/{path}', [CatalogProxyController::class, 'proxy'])
                    // Catalog only: this route must never reach a booking write.
                    ->where('path', 'tours(/.*)?')
                    // Named per prefix: consumers may mount the catalog more than
                    // once (a legacy path plus a new one), and a fixed name collides.
                    ->name('travelo.catalog.' . trim(str_replace('/', '.', $prefix), '.'));
            });
        });
    }
}
