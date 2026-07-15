<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * @method static \TheOneDigi\TourSdk\Api\BookingApi bookings()
 * @method static \TheOneDigi\TourSdk\Api\TourApi tours()
 * @method static array get(string $path, array $query = [], array $headers = [])
 * @method static array post(string $path, ?array $body = null, array $headers = [])
 * @method static array request(string $method, string $path, ?array $body = null, array $query = [], array $headers = [])
 * @method static array data(array $envelope)
 *
 * @see PartnerClient
 */
class Travelo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PartnerClient::class;
    }
}
