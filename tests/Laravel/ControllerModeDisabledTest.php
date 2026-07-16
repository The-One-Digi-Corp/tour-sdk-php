<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests\Laravel;

use Illuminate\Support\Facades\Route;

/**
 * Controller mode is opt-in. Installing a package must never open HTTP routes on
 * someone's app without them saying so — this is the test that keeps that true.
 */
class ControllerModeDisabledTest extends TestCase
{
    public function test_no_routes_are_registered_by_default(): void
    {
        $this->assertFalse(Route::has('travelo.tours.index'));
        $this->assertFalse(Route::has('travelo.bookings.index'));
        $this->assertFalse(Route::has('travelo.bookings.store'));

        $this->getJson('travelo/tours')->assertNotFound();
    }

    public function test_the_sdk_still_works_as_a_plain_sdk(): void
    {
        // Turning controller mode off must not cost a consumer the SDK itself.
        $this->assertInstanceOf(
            \TheOneDigi\TourSdk\Api\TourApi::class,
            $this->app->make(\TheOneDigi\TourSdk\Api\TourApi::class),
        );
    }
}
