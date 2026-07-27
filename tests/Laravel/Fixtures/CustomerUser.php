<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests\Laravel\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Stand-in for the host app's auth model in package tests.
 *
 * Backed by the SDK's own `users` migration, so CustomerProvisioner has a real
 * table to create accounts in. A consuming app points travelo.account.user_model
 * (or config('auth.providers.users.model')) at its own model instead.
 */
class CustomerUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password'];
}
