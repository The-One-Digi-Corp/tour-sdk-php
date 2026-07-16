<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Laravel\Http\Controllers\BookingController;
use TheOneDigi\TourSdk\Laravel\Http\Controllers\CatalogProxyController;
use TheOneDigi\TourSdk\Laravel\Http\Controllers\TourCatalogController;
use TheOneDigi\TourSdk\Laravel\Services\BookingMirror;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Api\TourApi;
use TheOneDigi\TourSdk\WebhookVerifier;

/**
 * Auto-discovered. Installing the package is the whole integration: the client,
 * its API classes and the webhook verifier are wired from config, so a consumer
 * writes no boilerplate and every consumer wires them identically.
 *
 * Two modes, side by side:
 *
 *  - SDK mode (always on): inject TourApi / BookingApi and write your own controllers.
 *  - Controller mode (`travelo.controller_mode.enabled`): the SDK registers the
 *    endpoints itself and the consumer writes none.
 *
 * Controller mode is off by default. Installing a package should never open HTTP
 * routes on someone's app without them saying so.
 *
 * `confirm` has no route in either mode. Confirming turns a held seat into a sold
 * one, and only the consumer's payment flow knows whether money arrived — a route
 * would let anyone confirm a booking nobody paid for.
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

        $this->app->bind(BookingMirror::class, static fn (Application $app) => new BookingMirror(
            (int) $app['config']->get('travelo.hold_ttl_minutes', 30),
        ));
    }

    public function boot(): void
    {
        // Loaded unconditionally: the mirror tables must exist before controller
        // mode is switched on, and a consumer flipping a config flag should not
        // have to discover a second step to make it work.
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/travelo.php' => $this->app->configPath('travelo.php'),
            ], 'travelo-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'travelo-migrations');
        }

        $this->registerCatalogMacro();
        $this->registerControllerModeRoutes();
    }

    /**
     * Controller mode: every endpoint a consumer would otherwise hand-write.
     *
     * Routes are declared one by one rather than as a `{path}` catch-all. A regex
     * can whitelist a GET path; it can say nothing about a POST body, and booking
     * writes live in this group.
     */
    private function registerControllerModeRoutes(): void
    {
        $config = $this->app['config'];

        if (! $config->get('travelo.controller_mode.enabled', false)) {
            return;
        }

        $auth = (array) $config->get('travelo.controller_mode.auth_middleware', []);

        Route::group([
            'prefix' => (string) $config->get('travelo.controller_mode.prefix', 'travelo'),
            'middleware' => (array) $config->get('travelo.controller_mode.middleware', ['api']),
        ], function () use ($auth): void {
            Route::prefix('tours')->name('travelo.tours.')->group(function (): void {
                // Literal segments first: '/{code}' would otherwise swallow
                // 'references' and every get-* below it.
                Route::get('/', [TourCatalogController::class, 'index'])->name('index');
                Route::get('/references', [TourCatalogController::class, 'references'])->name('references');
                Route::get('/get-seasonal', [TourCatalogController::class, 'seasonal'])->name('seasonal');
                Route::get('/get-featured', [TourCatalogController::class, 'featured'])->name('featured');
                Route::get('/get-similar', [TourCatalogController::class, 'similar'])->name('similar');
                Route::get('/{code}', [TourCatalogController::class, 'show'])->name('show');
                Route::get('/{code}/calendars', [TourCatalogController::class, 'calendars'])->name('calendars');
                Route::get('/{code}/calendar-by-date', [TourCatalogController::class, 'calendarByDate'])
                    ->name('calendar-by-date');
                Route::get('/{id}/get-list-reviews', [TourCatalogController::class, 'reviews'])->name('reviews');
                Route::get('/{id}/get-all-image-reviews', [TourCatalogController::class, 'reviewImages'])
                    ->name('review-images');
            });

            Route::prefix('bookings')->name('travelo.bookings.')->group(function () use ($auth): void {
                // Public, like the storefront: a customer holds a seat before they
                // have an account (travelo-api creates one from their email). Quote
                // and promotion checks reserve nothing either.
                Route::post('/quote', [BookingController::class, 'quote'])->name('quote');
                Route::post('/check-promotion', [BookingController::class, 'checkPromotion'])->name('check-promotion');
                Route::post('/', [BookingController::class, 'store'])->name('store');

                // Reads and mutations of an existing booking are per-customer.
                // Without a guard, `index` would list every booking the partner
                // has ever taken.
                Route::middleware($auth)->group(function (): void {
                    Route::get('/', [BookingController::class, 'index'])->name('index');
                    Route::get('/{code}', [BookingController::class, 'show'])->name('show');
                    Route::post('/{code}/cancel', [BookingController::class, 'cancel'])->name('cancel');
                    Route::post('/{code}/applicant/{id}', [BookingController::class, 'updateApplicant'])
                        ->name('update-applicant');
                });
            });
        });
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
