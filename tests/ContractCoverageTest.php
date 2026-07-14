<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * Guards the SDK against the Partner API contract.
 *
 * This exists because the first cut of this SDK shipped with every booking
 * endpoint and none of the catalog ones, and nothing caught it. Now travelo-api
 * cannot grow an endpoint without this test failing until the SDK covers it.
 *
 * tests/fixtures/partner-api.openapi.json is a copy of the spec travelo-api
 * serves at /docs/partner/api.json. Refresh it with:
 *
 *   curl -s http://localhost:8000/docs/partner/api.json \
 *     -o tests/fixtures/partner-api.openapi.json
 */
final class ContractCoverageTest extends TestCase
{
    /** Spec paths are relative to servers[0].url, which ends in /api/partner. */
    private const PREFIX = '/api/partner';

    /** @var list<array<string, mixed>> */
    private array $transactions = [];

    private function client(): PartnerClient
    {
        $this->transactions = [];

        $stack = HandlerStack::create(new MockHandler(array_fill(
            0,
            50,
            new Response(200, [], (string) json_encode(['status' => 200, 'data' => []])),
        )));
        $stack->push(Middleware::history($this->transactions));

        return new PartnerClient(
            baseUrl: 'http://localhost:8000',
            clientId: 'cid',
            secret: 'sec',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );
    }

    /**
     * Every operation the contract declares, mapped to the SDK call that serves it.
     *
     * @return array<string, callable():mixed>
     */
    private function operations(PartnerClient $client): array
    {
        return [
            'GET /tours' => fn () => $client->tours()->list(['take' => 2]),
            'GET /tours/references' => fn () => $client->tours()->references(),
            'GET /tours/get-seasonal' => fn () => $client->tours()->seasonal(['take' => 2]),
            'GET /tours/get-featured' => fn () => $client->tours()->featured(['take' => 2]),
            'GET /tours/get-similar' => fn () => $client->tours()->similar(['tour_code' => 'T-1']),
            'GET /tours/{code}' => fn () => $client->tours()->show('T-1'),
            'GET /tours/{code}/calendars' => fn () => $client->tours()->calendars('T-1'),
            'GET /tours/{code}/calendar-by-date' => fn () => $client->tours()->calendarByDate('T-1', ['date' => '2026-08-01']),
            'GET /tours/{id}/get-list-reviews' => fn () => $client->tours()->reviews(7),
            'GET /tours/{id}/get-all-image-reviews' => fn () => $client->tours()->reviewImages(7),

            'GET /bookings' => fn () => $client->bookings()->list(),
            'POST /bookings' => fn () => $client->bookings()->create(['tour_code' => 'T-1'], 'idem-1'),
            'POST /bookings/quote' => fn () => $client->bookings()->quote(['tour_code' => 'T-1']),
            'POST /bookings/check-promotion' => fn () => $client->bookings()->checkPromotion(['code' => 'X']),
            'GET /bookings/{code}' => fn () => $client->bookings()->show('TB-1'),
            'POST /bookings/{code}/confirm' => fn () => $client->bookings()->confirm('TB-1'),
            'POST /bookings/{code}/cancel' => fn () => $client->bookings()->cancel('TB-1'),
            'POST /bookings/{code}/applicant/{id}' => fn () => $client->bookings()->updateApplicant('TB-1', 7, []),
        ];
    }

    /**
     * @return list<string> e.g. "GET /tours/{code}"
     */
    private function specOperations(): array
    {
        $spec = json_decode(
            (string) file_get_contents(__DIR__ . '/fixtures/partner-api.openapi.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $operations = [];

        foreach ($spec['paths'] ?? [] as $path => $item) {
            foreach ($item as $method => $operation) {
                if (in_array(strtolower($method), ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $operations[] = strtoupper($method) . ' ' . $path;
                }
            }
        }

        sort($operations);

        return $operations;
    }

    public function test_the_contract_prefix_is_still_api_partner(): void
    {
        $spec = json_decode(
            (string) file_get_contents(__DIR__ . '/fixtures/partner-api.openapi.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        // Everything below assumes spec paths hang off this prefix.
        self::assertStringEndsWith(self::PREFIX, (string) $spec['servers'][0]['url']);
    }

    public function test_every_endpoint_in_the_contract_has_an_sdk_method(): void
    {
        $missing = array_values(array_diff($this->specOperations(), array_keys($this->operations($this->client()))));

        self::assertSame([], $missing, "The Partner API has endpoints this SDK cannot reach:\n  " . implode("\n  ", $missing));
    }

    public function test_the_sdk_does_not_target_endpoints_the_contract_does_not_have(): void
    {
        $unknown = array_values(array_diff(array_keys($this->operations($this->client())), $this->specOperations()));

        self::assertSame([], $unknown, "This SDK calls endpoints the contract does not declare:\n  " . implode("\n  ", $unknown));
    }

    public function test_each_sdk_method_hits_the_url_the_contract_declares(): void
    {
        foreach ($this->operations($client = $this->client()) as $operation => $call) {
            $this->transactions = [];
            [$expectedMethod, $specPath] = explode(' ', $operation, 2);

            $call();

            self::assertCount(1, $this->transactions, "{$operation} did not issue exactly one request.");

            $request = $this->transactions[0]['request'];
            // {code} is a tour code under /tours and a booking code under /bookings.
            $code = str_starts_with($specPath, '/bookings') ? 'TB-1' : 'T-1';
            $expectedPath = self::PREFIX . strtr($specPath, ['{code}' => $code, '{id}' => '7']);

            self::assertSame($expectedMethod, $request->getMethod(), "{$operation} used the wrong HTTP method.");
            self::assertSame($expectedPath, $request->getUri()->getPath(), "{$operation} hit the wrong path.");
        }
    }

    public function test_the_booking_code_is_url_encoded_into_the_path(): void
    {
        $client = $this->client();

        $client->bookings()->confirm('TB/../evil');

        self::assertSame(
            '/api/partner/bookings/TB%2F..%2Fevil/confirm',
            $this->transactions[0]['request']->getUri()->getPath(),
        );
    }
}
