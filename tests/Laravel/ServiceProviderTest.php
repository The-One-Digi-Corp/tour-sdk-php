<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use TheOneDigi\TourSdk\Laravel\Facades\Travelo;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Api\TourApi;
use TheOneDigi\TourSdk\WebhookVerifier;

class ServiceProviderTest extends TestCase
{
    public function test_installing_the_package_is_the_whole_wiring(): void
    {
        // No wrapper class, no manual binding: this is what a consumer gets for free.
        $this->assertInstanceOf(PartnerClient::class, $this->app->make(PartnerClient::class));
        $this->assertInstanceOf(BookingApi::class, $this->app->make(BookingApi::class));
        $this->assertInstanceOf(TourApi::class, $this->app->make(TourApi::class));
        $this->assertInstanceOf(WebhookVerifier::class, $this->app->make(WebhookVerifier::class));
    }

    public function test_the_client_is_a_singleton(): void
    {
        $this->assertSame($this->app->make(PartnerClient::class), $this->app->make(PartnerClient::class));
    }

    public function test_the_facade_reaches_the_api_classes(): void
    {
        $this->assertInstanceOf(BookingApi::class, Travelo::bookings());
        $this->assertInstanceOf(TourApi::class, Travelo::tours());
    }

    public function test_api_classes_can_be_swapped_for_a_double(): void
    {
        // The affordance that removes the need for a hand-rolled wrapper: consumers
        // bind a double for the surface they use.
        $double = $this->createMock(BookingApi::class);
        $this->app->instance(BookingApi::class, $double);

        $this->assertSame($double, $this->app->make(BookingApi::class));
    }

    public function test_config_is_merged_so_consumers_need_not_publish_it(): void
    {
        $this->assertSame('http://travelo.test', config('travelo.base_url'));
        $this->assertSame(30, config('travelo.hold_ttl_minutes'));
        $this->assertSame(300, config('travelo.webhook_max_skew_seconds'));
    }

    public function test_email_views_and_reusable_v2_parts_are_registered(): void
    {
        $hints = View::getFinder()->getHints()['travelo'];

        $this->assertContains(
            realpath(__DIR__ . '/../../resources/views'),
            array_map(static fn (string $path) => realpath($path), $hints),
        );
        $this->assertSame(
            realpath(__DIR__ . '/../../resources/views/emails/booking-created.blade.php'),
            realpath(View::getFinder()->find('travelo::emails.booking-created')),
        );
        $this->assertTrue(View::exists('travelo::mails.tour.v2.layout'));
        $this->assertTrue(View::exists('travelo::mails.tour.v2.create-order'));
        $this->assertTrue(View::exists('travelo::mails.tour.v2.create-account'));
        $this->assertTrue(View::exists('travelo::components.mails.tour.v2.order-summary'));
        $this->assertTrue(View::exists('travelo::components.mails.tour.v2.contact-card'));
    }

    public function test_booking_email_entry_point_renders_the_v2_template(): void
    {
        $booking = (object) [
            'order_code' => 'SDK123',
            'name' => 'Test Customer',
            'status' => 1,
            'input_currency' => 'VND',
            'input_total' => 805680,
            'input_sub_total' => 805680,
            'input_discount' => 0,
            'created_at' => \Illuminate\Support\Carbon::parse('2026-07-20'),
            'detail' => (object) [
                'departure_date' => '2026-07-25',
                'input_adult_price' => 805680,
                'adult_quantity' => 1,
                'input_child_price' => 0,
                'child_quantity' => 0,
                'input_infant_price' => 0,
                'infant_quantity' => 0,
            ],
            'applicants' => collect(),
        ];

        $html = View::make('travelo::emails.booking-created', ['booking' => $booking])->render();

        $this->assertStringContainsString('<html lang="en-US">', $html);
        $this->assertStringContainsString('Booking ID: <span class="text-primary">#SDK123</span>', $html);
        $this->assertStringContainsString('Payment Amount', $html);
        $this->assertStringContainsString('805,680 VND', $html);
    }

    /**
     * Routes this package put on the app — the host framework has its own.
     *
     * @return list<\Illuminate\Routing\Route>
     */
    private function packageRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_contains((string) $route->getActionName(), 'TheOneDigi\\TourSdk\\Laravel'))
            ->values()
            ->all();
    }

    public function test_the_package_registers_no_routes_by_default(): void
    {
        // The whole point: installing the SDK must not put any endpoint on a
        // consumer's app. Especially not a booking write.
        $this->assertSame([], $this->packageRoutes());
    }

    public function test_no_write_route_can_be_opted_into(): void
    {
        Route::traveloCatalog();

        foreach ($this->packageRoutes() as $route) {
            $signature = strtoupper(implode('|', $route->methods())) . ' ' . $route->uri();

            // Confirming a booking is a payment decision. The SDK never exposes it.
            $this->assertStringNotContainsString('confirm', $signature);
            $this->assertStringNotContainsString('cancel', $signature);
            $this->assertStringNotContainsString('POST', $signature);
        }
    }

    public function test_catalog_routes_are_opt_in_and_take_the_consumers_prefix(): void
    {
        Route::traveloCatalog('v2/content');

        $routes = $this->packageRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('v2/content/{path}', $routes[0]->uri());
        $this->assertSame(['GET', 'HEAD'], $routes[0]->methods());
    }

    public function test_the_consumer_chooses_the_catalog_middleware(): void
    {
        Route::traveloCatalog('catalog', ['throttle:10,1']);

        $this->assertContains('throttle:10,1', $this->packageRoutes()[0]->gatherMiddleware());
    }

    public function test_the_prefix_nests_under_an_enclosing_group_in_the_right_order(): void
    {
        // Consumers call this from routes/api.php, which Laravel already wraps in an
        // "api" group. Registering the route with Route::prefix() instead of a group
        // silently produces "catalog/api/{path}" — the segments the wrong way round.
        Route::group(['prefix' => 'api'], function () {
            Route::traveloCatalog('catalog');
        });

        $this->assertSame('api/catalog/{path}', $this->packageRoutes()[0]->uri());
    }

    public function test_enclosing_middleware_still_applies(): void
    {
        Route::group(['prefix' => 'api', 'middleware' => ['throttle:60,1']], function () {
            Route::traveloCatalog('catalog', ['throttle:10,1']);
        });

        $middleware = $this->packageRoutes()[0]->gatherMiddleware();

        $this->assertContains('throttle:60,1', $middleware);
        $this->assertContains('throttle:10,1', $middleware);
    }

    public function test_the_catalog_macro_is_registered_once(): void
    {
        $this->assertTrue(Route::hasMacro('traveloCatalog'));
    }

    public function test_the_catalog_can_be_mounted_at_several_prefixes(): void
    {
        // A consumer keeping a legacy path alongside a new one must not hit a
        // duplicate route name.
        Route::traveloCatalog('partner');
        Route::traveloCatalog('catalog');

        $names = array_map(fn ($route) => $route->getName(), $this->packageRoutes());

        $this->assertSame(['travelo.catalog.partner', 'travelo.catalog.catalog'], $names);
        $this->assertCount(2, array_unique($names));
    }
}
