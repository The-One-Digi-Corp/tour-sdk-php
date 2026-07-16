<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;
use TheOneDigi\TourSdk\Laravel\Support\BookingMirrorExtension;
use TheOneDigi\TourSdk\Laravel\Support\BookingOwner;
use TheOneDigi\TourSdk\Laravel\Support\TourCatalogDecorator;
use TheOneDigi\TourSdk\PartnerClient;

class ControllerModeTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array<string, mixed>> */
    private array $transactions = [];

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('travelo.controller_mode.enabled', true);
        $app['config']->set('travelo.controller_mode.prefix', 'travelo');
        // auth:sanctum is not installed in the package test app; ownership is proved
        // through BookingOwner instead, which is what the controllers actually read.
        $app['config']->set('travelo.controller_mode.auth_middleware', []);
    }

    protected function tearDown(): void
    {
        BookingOwner::resolveUsing(null);
        TourCatalogDecorator::flush();
        BookingMirrorExtension::flush();

        parent::tearDown();
    }

    /**
     * @param list<Response|\Throwable> $responses
     */
    private function fakeUpstream(array $responses): void
    {
        $this->transactions = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->transactions));

        $this->app->instance(PartnerClient::class, new PartnerClient(
            baseUrl: 'http://travelo.test',
            clientId: 'test_client',
            secret: 'test_secret',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function envelope(array $data): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'status' => 200,
            'message' => 'Success',
            'errors' => [],
            'data' => $data,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function upstreamBooking(string $orderCode = 'TB-001'): array
    {
        return [
            'id' => 1,
            'order_code' => $orderCode,
            'status' => TourBooking::PENDING_PAYMENT,
            'total' => 120.5,
            'currency' => 'USD',
            'input_total' => 3000000.0,
            'input_currency' => 'VND',
        ];
    }

    private function asOwner(int $id): void
    {
        BookingOwner::resolveUsing(fn () => $id);
    }

    public function test_catalog_route_forwards_the_upstream_envelope_verbatim(): void
    {
        $this->fakeUpstream([$this->envelope(['tours' => [['code' => 'T-1']]])]);

        $response = $this->getJson('travelo/tours?per_page=5');

        // Verbatim means verbatim: `message` survives, not just `data`.
        $response->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Success',
                'data' => ['tours' => [['code' => 'T-1']]],
            ]);

        $this->assertSame(
            'http://travelo.test/api/partner/tours?per_page=5',
            (string) $this->transactions[0]['request']->getUri(),
        );
    }

    public function test_literal_catalog_segments_are_not_swallowed_by_the_code_route(): void
    {
        $this->fakeUpstream([$this->envelope(['categories' => []])]);

        $this->getJson('travelo/tours/references')->assertOk();

        // If '/{code}' had won, this would have been signed as tours/references-as-a-code.
        $this->assertSame(
            'http://travelo.test/api/partner/tours/references',
            (string) $this->transactions[0]['request']->getUri(),
        );
    }

    public function test_a_host_decorator_merges_local_data_into_the_tour_list(): void
    {
        $this->fakeUpstream([$this->envelope([
            'total' => 2,
            'tours' => [['code' => 'T-1'], ['code' => 'T-2']],
        ])]);

        // The host's only code: fold a wishlist flag the package cannot know.
        TourCatalogDecorator::extend(fn (array $tours) => array_map(
            fn (array $tour) => $tour + ['is_wishlisted' => $tour['code'] === 'T-1'],
            $tours,
        ));

        $response = $this->getJson('travelo/tours');

        $response->assertOk();
        $this->assertTrue($response->json('data.tours.0.is_wishlisted'));
        $this->assertFalse($response->json('data.tours.1.is_wishlisted'));
        // Pagination and envelope survive untouched.
        $this->assertSame(2, $response->json('data.total'));
        $this->assertSame('Success', $response->json('message'));
    }

    public function test_a_host_decorator_merges_into_a_single_tour_on_show(): void
    {
        $this->fakeUpstream([$this->envelope(['code' => 'T-9', 'name' => 'Halong'])]);

        TourCatalogDecorator::extend(fn (array $tours) => array_map(
            fn (array $tour) => $tour + ['is_wishlisted' => true],
            $tours,
        ));

        $response = $this->getJson('travelo/tours/T-9');

        $response->assertOk();
        $this->assertTrue($response->json('data.is_wishlisted'));
        $this->assertSame('T-9', $response->json('data.code'));
    }

    public function test_without_a_decorator_the_payload_is_forwarded_verbatim(): void
    {
        $this->fakeUpstream([$this->envelope(['tours' => [['code' => 'T-1']]])]);

        // No decorator registered — nothing added, nothing touched.
        $response = $this->getJson('travelo/tours');

        $response->assertOk();
        $this->assertSame(['code' => 'T-1'], $response->json('data.tours.0'));
    }

    public function test_confirm_is_not_routable(): void
    {
        // The one endpoint that must never be reachable over HTTP: it turns a held
        // seat into a sold one without anyone checking that money arrived.
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->all();

        foreach ($routes as $uri) {
            $this->assertStringNotContainsString('confirm', $uri);
        }
    }

    public function test_bookings_index_only_returns_the_current_owners_bookings(): void
    {
        TourBooking::create([
            'order_code' => 'MINE',
            'user_id' => 7,
            'currency' => 'USD',
            'upstream_payload' => ['order_code' => 'MINE'],
        ]);
        TourBooking::create([
            'order_code' => 'THEIRS',
            'user_id' => 99,
            'currency' => 'USD',
            'upstream_payload' => ['order_code' => 'THEIRS'],
        ]);

        $this->asOwner(7);

        $response = $this->getJson('travelo/bookings');

        $response->assertOk();
        $this->assertSame(1, $response->json('data.total'));
        $this->assertSame('MINE', $response->json('data.bookings.0.order_code'));
    }

    public function test_bookings_show_reports_someone_elses_booking_as_absent(): void
    {
        TourBooking::create([
            'order_code' => 'THEIRS',
            'user_id' => 99,
            'currency' => 'USD',
            'upstream_payload' => ['order_code' => 'THEIRS'],
        ]);

        $this->asOwner(7);

        // 404 and not 403: a 403 would confirm the code exists.
        $this->getJson('travelo/bookings/THEIRS')->assertNotFound();
    }

    public function test_booking_reads_refuse_when_no_owner_can_be_resolved(): void
    {
        TourBooking::create([
            'order_code' => 'MINE',
            'user_id' => 7,
            'currency' => 'USD',
            'upstream_payload' => ['order_code' => 'MINE'],
        ]);

        // Defence in depth: auth_middleware is empty here, so if the controller
        // trusted the route guard alone this would list the partner's whole book.
        $this->getJson('travelo/bookings')->assertUnauthorized();
    }

    public function test_store_holds_upstream_then_mirrors(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/bookings', ['tour_code' => 'T-1'])->assertOk();

        $booking = TourBooking::where('order_code', 'TB-001')->first();

        $this->assertNotNull($booking);
        $this->assertSame(7, (int) $booking->user_id);
        $this->assertSame(120.5, $booking->total);
        $this->assertSame('USD', $booking->currency);
        $this->assertSame('VND', $booking->input_currency);
        $this->assertNotNull($booking->held_until);
    }

    public function test_mirror_extension_receives_the_booking_and_upstream_payload(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $seen = [];
        // A host fills its own local tables (order detail, commission) here.
        BookingMirrorExtension::extend(function (TourBooking $booking, array $payload) use (&$seen): void {
            $seen = ['order_code' => $booking->order_code, 'payload_total' => $payload['total'] ?? null];
        });

        $this->postJson('travelo/bookings', ['order_details' => ['tour_id' => 1]])->assertOk();

        $this->assertSame('TB-001', $seen['order_code']);
        $this->assertSame(120.5, $seen['payload_total']);
    }

    public function test_store_allows_a_guest_and_mirrors_with_no_owner(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        // No asOwner(): a guest holds a seat before they have an account, like the
        // storefront. travelo-api creates the account from their email.

        $this->postJson('travelo/bookings', ['order_details' => ['tour_id' => 1]])->assertOk();

        $booking = TourBooking::where('order_code', 'TB-001')->first();

        $this->assertNotNull($booking);
        $this->assertNull($booking->user_id);
    }

    public function test_store_generates_its_own_idempotency_key_and_ignores_the_callers(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/bookings', [
            'tour_code' => 'T-1',
            'idempotency_key' => 'attacker-supplied',
        ])->assertOk();

        $sent = json_decode((string) $this->transactions[0]['request']->getBody(), true);

        // A key the caller picks is a key the caller regenerates on retry — and a
        // fresh key on a timed-out retry holds a second seat.
        $this->assertNotSame('attacker-supplied', $sent['idempotency_key']);
        $this->assertSame(
            $sent['idempotency_key'],
            TourBooking::where('order_code', 'TB-001')->value('idempotency_key'),
        );
    }

    public function test_store_does_not_mirror_when_upstream_refuses(): void
    {
        $this->fakeUpstream([
            new Response(422, ['Content-Type' => 'application/json'], (string) json_encode([
                'status' => 422,
                'message' => 'Tour is sold out.',
                'errors' => [],
                'data' => null,
            ])),
        ]);
        $this->asOwner(7);

        $this->postJson('travelo/bookings', ['tour_code' => 'T-1'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Tour is sold out.']);

        $this->assertSame(0, TourBooking::count());
    }

    public function test_a_replayed_booking_keeps_its_original_hold_window(): void
    {
        $this->fakeUpstream([
            $this->envelope(['order' => $this->upstreamBooking()]),
            $this->envelope(['order' => $this->upstreamBooking()]),
        ]);
        $this->asOwner(7);

        $this->postJson('travelo/bookings', ['tour_code' => 'T-1'])->assertOk();
        $first = TourBooking::where('order_code', 'TB-001')->firstOrFail();
        $heldUntil = $first->held_until;

        $this->postJson('travelo/bookings', ['tour_code' => 'T-1'])->assertOk();

        // The seat expires on travelo-api's clock, not on ours — a second write must
        // not push the window forward, and must not create a twin row.
        $this->assertSame(1, TourBooking::count());
        $this->assertEquals(
            $heldUntil->toDateTimeString(),
            TourBooking::where('order_code', 'TB-001')->firstOrFail()->held_until->toDateTimeString(),
        );
    }

    public function test_an_unreachable_travelo_is_503_not_500(): void
    {
        $this->fakeUpstream([
            new ConnectException('Connection refused', new PsrRequest('GET', 'http://travelo.test')),
        ]);

        $this->getJson('travelo/tours')
            ->assertStatus(503)
            ->assertJson(['errors' => ['reason' => 'travelo_unreachable']]);
    }
}
