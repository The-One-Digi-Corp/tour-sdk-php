<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TheOneDigi\TourSdk\Generated\Resource\PartnerTourSyncResource;
use TheOneDigi\TourSdk\PartnerClient;

final class TourSyncTest extends TestCase
{
    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $transactions = [];

    public function test_sync_resources_reads_one_page_of_tours_with_calendars_and_overrides(): void
    {
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], (string) json_encode([
            'status' => 200,
            'message' => 'OK',
            'data' => [
                'current_page' => 2,
                'total' => 21,
                'per_page' => 20,
                'last_page' => 2,
                'tours' => [[
                    'id' => 10,
                    'code' => 'SGCCFD',
                    'currency' => 'USD',
                    'base_price' => 49,
                    'day' => 1,
                    'night' => 0,
                    'thumbnail' => 'https://media.example/t.png',
                    'translations' => [['language' => 'vi', 'name' => 'Địa đạo Củ Chi', 'slug' => 'dia-dao']],
                    'prices' => [['range_id' => 2, 'min_pax' => 1, 'max_pax' => null, 'currency' => 'USD', 'adult_price' => 49, 'child_price' => 37, 'infant_price' => 0]],
                    'calendars' => [[
                        'id' => 24,
                        'start_date' => '2026-07-14',
                        'end_date' => '2026-12-31',
                        'max_slots_per_day' => null,
                        'prices' => [],
                        'overrides' => [['id' => 3, 'start_date' => '2026-12-24', 'end_date' => '2026-12-25', 'type' => 2, 'prices' => []]],
                    ]],
                ]],
            ],
        ]))]));
        $stack->push(Middleware::history($this->transactions));
        $client = new PartnerClient(
            baseUrl: 'http://localhost:8000',
            clientId: 'cid',
            secret: 'sec',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );

        $tours = $client->tours()->syncResources(['page' => 2]);

        self::assertSame('/api/partner/tours/sync', $this->transactions[0]['request']->getUri()->getPath());
        self::assertSame('page=2', $this->transactions[0]['request']->getUri()->getQuery());
        self::assertCount(1, $tours);
        self::assertInstanceOf(PartnerTourSyncResource::class, $tours[0]);
        self::assertSame('SGCCFD', $tours[0]->code);
        self::assertSame(49.0, $tours[0]->prices[0]->adultPrice);
        self::assertSame(24, $tours[0]->calendars[0]->id);
        self::assertSame(2, $tours[0]->calendars[0]->overrides[0]['type']);
    }

    public function test_id_list_sends_the_search_filters_and_reads_the_ids(): void
    {
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], (string) json_encode([
            'status' => 200,
            'message' => 'OK',
            'data' => ['total' => 2, 'ids' => [3, 8]],
        ]))]));
        $stack->push(Middleware::history($this->transactions));
        $client = new PartnerClient(
            baseUrl: 'http://localhost:8000',
            clientId: 'cid',
            secret: 'sec',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );

        $ids = $client->tours()->idList(['tour_direction' => 'outbound', 'type' => '2']);

        self::assertSame('/api/partner/tours/ids', $this->transactions[0]['request']->getUri()->getPath());
        self::assertSame('tour_direction=outbound&type=2', $this->transactions[0]['request']->getUri()->getQuery());
        self::assertSame([3, 8], $ids);
    }
}
