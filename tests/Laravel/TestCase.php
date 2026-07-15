<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests\Laravel;

use Orchestra\Testbench\TestCase as Orchestra;
use TheOneDigi\TourSdk\Laravel\TourSdkServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [TourSdkServiceProvider::class];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['Travelo' => \TheOneDigi\TourSdk\Laravel\Facades\Travelo::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('travelo.base_url', 'http://travelo.test');
        $app['config']->set('travelo.client_id', 'test_client');
        $app['config']->set('travelo.secret', 'test_secret');
        $app['config']->set('travelo.currency', 'USD');
    }
}
