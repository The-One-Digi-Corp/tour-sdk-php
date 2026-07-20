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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use TheOneDigi\TourSdk\Laravel\Mail\BookingCreated;
use TheOneDigi\TourSdk\Laravel\Mail\CustomerAccountCreated;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;
use TheOneDigi\TourSdk\Laravel\Models\TourBookingDetail;
use TheOneDigi\TourSdk\Laravel\Support\BookingMirrorExtension;
use TheOneDigi\TourSdk\Laravel\Support\BookingOwner;
use TheOneDigi\TourSdk\Laravel\Support\TourCatalogDecorator;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Tests\Laravel\Fixtures\CustomerUser;

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
        // The account CustomerProvisioner creates a customer in belongs to the host
        // app; here the fixture model backed by the SDK's own users migration stands
        // in for it.
        $app['config']->set('auth.providers.users.model', CustomerUser::class);
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
     * The shopper's currency and language have to reach travelo-api, which prices
     * and localises off exactly these two headers.
     *
     * PartnerClient builds them from its own config — right for a queue job with no
     * request, wrong for controller mode. Before this forwarding existed, a visitor
     * switching to VND kept seeing USD, and every price still rendered, so nothing
     * looked broken.
     */
    public function test_the_callers_currency_and_language_reach_upstream(): void
    {
        $this->fakeUpstream([$this->envelope(['scheduleTour' => []])]);

        $this->withHeaders(['X-Currency' => 'vnd', 'Accept-Language' => 'vi'])
            ->getJson('/travelo/tours/7/get-schedule-tour?date=2026-08-01')
            ->assertStatus(200);

        $sent = $this->transactions[0]['request'];

        self::assertSame('VND', $sent->getHeaderLine('X-Currency'), 'currency was not forwarded upstream');
        self::assertSame('vi', $sent->getHeaderLine('Accept-Language'), 'language was not forwarded upstream');
    }

    /**
     * A caller that sends no X-Currency leaves PartnerClient's configured default
     * standing, so forwarding adds a currency rather than removing one.
     *
     * Only currency is asserted: an HTTP client practically always sends some
     * Accept-Language, so its absence is not a case this can reach. travelo-api
     * normalises whatever arrives ("en-US,en;q=0.9" -> "en"), so forwarding a
     * browser's full header verbatim is safe.
     */
    public function test_upstream_keeps_the_configured_currency_when_the_caller_sends_none(): void
    {
        $this->transactions = [];
        $stack = HandlerStack::create(new MockHandler([$this->envelope(['scheduleTour' => []])]));
        $stack->push(Middleware::history($this->transactions));

        $this->app->instance(PartnerClient::class, new PartnerClient(
            baseUrl: 'http://travelo.test',
            clientId: 'test_client',
            secret: 'test_secret',
            defaultCurrency: 'USD',
            locale: 'en',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        ));

        $this->getJson('/travelo/tours/7/get-schedule-tour?date=2026-08-01')->assertStatus(200);

        self::assertSame('USD', $this->transactions[0]['request']->getHeaderLine('X-Currency'));
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
            'sub_total' => 130.5,
            'discount' => 10.0,
            'total' => 120.5,
            'cost' => 90.0,
            'currency' => 'USD',
            'input_sub_total' => 3250000.0,
            'input_discount' => 250000.0,
            'input_total' => 3000000.0,
            'input_cost' => 2200000.0,
            'input_currency' => 'VND',
            'input_currency_version' => 2,
            'input_currency_exchange_rate' => 24900.0,
            'promotion_code' => 'SUMMER',
            'name' => 'Nguyen Van A',
            'email' => 'a@x.com',
            'phone' => '0900000000',
            'dial_code' => 84,
            'tour_booking_detail' => [
                'tour_id' => 42,
                'tour_price_group_id' => 7,
                'departure_date' => '2026-08-15',
                'adult_quantity' => 2,
                'adult_price' => 60.25,
                'currency' => 'USD',
                'input_adult_price' => 1500000.0,
                'input_currency' => 'VND',
                'input_currency_version' => 2,
                'input_currency_exchange_rate' => 24900.0,
            ],
            'tour_booking_applicants' => [
                [
                    'id' => 501,
                    'type' => 1,
                    'full_name' => 'Nguyen Van A',
                    'gender' => 1,
                    'nationality' => 'Vietnam',
                ],
            ],
            'tour_booking_refund' => [
                'id' => 900,
                'refund_total' => 30.0,
                'status' => 2,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function minBookingPayload(): array
    {
        return [
            'order_details' => [
                'tour_id' => 1,
                'departure_date' => '2026-08-15',
                'adult_quantity' => 2,
            ],
            'name' => 'Nguyen Van A',
            'phone' => '0900000000',
            'email' => 'a@x.com',
            'applicants' => [
                ['type' => 1, 'full_name' => 'Nguyen Van A'],
            ],
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
        ]);
        TourBooking::create([
            'order_code' => 'THEIRS',
            'user_id' => 99,
            'currency' => 'USD',
        ]);

        $this->asOwner(7);

        $response = $this->getJson('travelo/tours/bookings');

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
        ]);

        $this->asOwner(7);

        // 404 and not 403: a 403 would confirm the code exists.
        $this->getJson('travelo/tours/bookings/THEIRS')->assertNotFound();
    }

    public function test_booking_reads_refuse_when_no_owner_can_be_resolved(): void
    {
        TourBooking::create([
            'order_code' => 'MINE',
            'user_id' => 7,
            'currency' => 'USD',
        ]);

        // Defence in depth: auth_middleware is empty here, so if the controller
        // trusted the route guard alone this would list the partner's whole book.
        $this->getJson('travelo/tours/bookings')->assertUnauthorized();
    }

    public function test_store_holds_upstream_then_mirrors(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        $booking = TourBooking::where('order_code', 'TB-001')->first();

        $this->assertNotNull($booking);
        $this->assertSame(7, (int) $booking->user_id);
        $this->assertSame(120.5, $booking->total);
        $this->assertSame('USD', $booking->currency);
        $this->assertSame('VND', $booking->input_currency);

        // Every amount the contract carries is mirrored, not just the total: a read
        // rebuilds the booking from these columns now that no blob backs it up.
        $this->assertSame(130.5, $booking->sub_total);
        $this->assertSame(10.0, $booking->discount);
        $this->assertSame(90.0, $booking->cost);
        $this->assertSame(3250000.0, $booking->input_sub_total);
        $this->assertSame(3000000.0, $booking->input_total);
        $this->assertSame(2, (int) $booking->input_currency_version);
        $this->assertSame('SUMMER', $booking->promotion_code);
        $this->assertSame('Nguyen Van A', $booking->name);
        $this->assertSame('a@x.com', $booking->email);
        $this->assertSame('0900000000', $booking->phone);

        $detail = TourBookingDetail::where('tour_booking_id', $booking->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame(42, (int) $detail->tour_id);
        $this->assertSame(2, (int) $detail->adult_quantity);
        $this->assertSame(60.25, (float) $detail->adult_price);
        $this->assertSame('USD', $detail->currency);
        $this->assertSame('2026-08-15', $detail->departure_date->format('Y-m-d'));

        // Applicants carry their real columns, and the upstream id is kept so an
        // update can address travelo-api's row.
        $applicant = $booking->applicants()->first();
        $this->assertNotNull($applicant);
        $this->assertSame(1, (int) $applicant->type);
        $this->assertSame('Nguyen Van A', $applicant->full_name);
        $this->assertSame('501', $applicant->upstream_applicant_id);

        // Refund sync is currently disabled (BookingMirror::syncRefund commented
        // out), so a new booking carries no mirrored refund row.
        $this->assertNull($booking->refund()->first());
    }

    public function test_store_accepts_travelo_apis_flat_data_shape_and_exposes_order(): void
    {
        // travelo-api's partner endpoint returns the booking directly under `data`,
        // with no `order` wrapper. Before the fix, store() read `data.order`, got
        // null, skipped the mirror, and handed the frontend a response whose
        // `data.order.order_code` was undefined — the /payment/undefined bug.
        $this->fakeUpstream([$this->envelope($this->upstreamBooking())]);
        $this->asOwner(7);

        $response = $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        // The mirror runs off the flat shape...
        $this->assertNotNull(TourBooking::where('order_code', 'TB-001')->first());

        // ...and the response is normalised so the consumer's data.order holds.
        $this->assertSame('TB-001', $response->json('data.order.order_code'));
    }

    public function test_store_keeps_exposing_order_for_the_nested_upstream_shape(): void
    {
        // The storefront shape already nests the booking under `data.order`; the
        // contract must stay `data.order` for it too.
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $response = $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        $this->assertSame('TB-001', $response->json('data.order.order_code'));
    }

    public function test_show_rebuilds_the_booking_from_columns_not_a_blob(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        // Hold + mirror, then read it back through the normalised columns.
        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        $response = $this->getJson('travelo/tours/bookings/TB-001')->assertOk();

        $this->assertSame('TB-001', $response->json('data.order_code'));
        $this->assertSame(130.5, $response->json('data.sub_total'));
        $this->assertSame(120.5, $response->json('data.total'));
        $this->assertSame('SUMMER', $response->json('data.promotion_code'));

        $this->assertSame(42, $response->json('data.tour_booking_detail.tour_id'));
        $this->assertSame('2026-08-15', $response->json('data.tour_booking_detail.departure_date'));

        $this->assertSame('Nguyen Van A', $response->json('data.tour_booking_applicants.0.full_name'));
        // The read exposes the mirror's own applicant id, the handle the update
        // route takes — never travelo-api's.
        $applicantId = $response->json('data.tour_booking_applicants.0.id');
        $this->assertNotSame(501, $applicantId);

        // Refund sync is disabled, so the serialised booking exposes no refund.
        $this->assertNull($response->json('data.tour_booking_refund'));
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

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        $this->assertSame('TB-001', $seen['order_code']);
        $this->assertSame(120.5, $seen['payload_total']);
    }

    public function test_store_leaves_a_guest_owner_less_when_account_creation_is_off(): void
    {
        config()->set('travelo.account.create_customer', false);

        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        // No asOwner() and provisioning disabled: the guest holds a seat and the
        // mirror stays owner-less, the pre-account-move behaviour.

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        $booking = TourBooking::where('order_code', 'TB-001')->first();

        $this->assertNotNull($booking);
        $this->assertNull($booking->user_id);
        $this->assertSame(0, CustomerUser::query()->count());
    }

    public function test_store_creates_a_customer_account_for_a_guest_and_emails_the_password(): void
    {
        Mail::fake();
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        // No asOwner(): the customer-facing account is now the SDK's to create.

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        $user = CustomerUser::where('email', 'a@x.com')->first();
        $this->assertNotNull($user, 'a guest checkout should leave an account behind');
        $this->assertSame('Nguyen Van A', $user->name);

        $booking = TourBooking::where('order_code', 'TB-001')->first();
        $this->assertSame((int) $user->getKey(), (int) $booking->user_id);

        // A generated-password account is emailed exactly once, to that address.
        Mail::assertSent(CustomerAccountCreated::class, fn (CustomerAccountCreated $mail) => $mail->hasTo('a@x.com'));
    }

    public function test_store_emails_a_booking_confirmation(): void
    {
        Mail::fake();
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        // Every successful booking gets a confirmation to the booking's email.
        Mail::assertSent(BookingCreated::class, fn (BookingCreated $mail) => $mail->hasTo('a@x.com'));
    }

    public function test_store_does_not_email_a_booking_confirmation_when_disabled(): void
    {
        config()->set('travelo.booking.send_confirmation_email', false);
        Mail::fake();
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        Mail::assertNotSent(BookingCreated::class);
    }

    public function test_store_uses_the_logged_in_owner_and_creates_no_account(): void
    {
        Mail::fake();
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        // A logged-in customer already owns the booking — no lookup, no new row.
        $this->assertSame(0, CustomerUser::query()->count());
        $this->assertSame(7, (int) TourBooking::where('order_code', 'TB-001')->value('user_id'));
        Mail::assertNotSent(CustomerAccountCreated::class);
    }

    public function test_store_reuses_an_existing_customer_without_a_second_welcome(): void
    {
        Mail::fake();
        $existing = CustomerUser::create([
            'name' => 'Returning Customer',
            'email' => 'a@x.com',
            'password' => bcrypt('secret'),
        ]);

        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        // Matched by email: no duplicate account, and a returning customer is not
        // emailed a password they never asked to reset.
        $this->assertSame(1, CustomerUser::where('email', 'a@x.com')->count());
        $this->assertSame((int) $existing->getKey(), (int) TourBooking::where('order_code', 'TB-001')->value('user_id'));
        Mail::assertNotSent(CustomerAccountCreated::class);
    }

    public function test_store_forwards_the_shoppers_currency_and_stores_the_canonical(): void
    {
        config()->set('travelo.currency', 'USD');
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        // The shopper's currency reaches travelo-api so it freezes as the order's
        // input_currency (what the payment screen shows). The headline amounts come
        // back canonical from the unified contract, so the mirror still stores USD.
        $this->withHeaders(['X-Currency' => 'vnd'])
            ->postJson('travelo/tours/bookings', $this->minBookingPayload())
            ->assertOk();

        $this->assertSame('VND', $this->transactions[0]['request']->getHeaderLine('X-Currency'));

        $booking = TourBooking::where('order_code', 'TB-001')->firstOrFail();
        $this->assertSame('USD', $booking->currency);
        $this->assertSame('VND', $booking->input_currency);
    }

    public function test_store_generates_its_own_idempotency_key_and_ignores_the_callers(): void
    {
        $this->fakeUpstream([$this->envelope(['order' => $this->upstreamBooking()])]);
        $this->asOwner(7);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload() + [
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

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())
            ->assertStatus(422)
            ->assertJson(['message' => 'Tour is sold out.']);

        $this->assertSame(0, TourBooking::count());
    }

    public function test_a_replayed_booking_updates_the_same_row_not_a_twin(): void
    {
        $this->fakeUpstream([
            $this->envelope(['order' => $this->upstreamBooking()]),
            $this->envelope(['order' => $this->upstreamBooking()]),
        ]);
        $this->asOwner(7);

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();
        $firstKey = TourBooking::where('order_code', 'TB-001')->firstOrFail()->idempotency_key;

        $this->postJson('travelo/tours/bookings', $this->minBookingPayload())->assertOk();

        // Keyed on order_code: a replay updates the same row instead of creating a
        // twin, and the original idempotency key is preserved.
        $this->assertSame(1, TourBooking::count());
        $this->assertSame($firstKey, TourBooking::where('order_code', 'TB-001')->firstOrFail()->idempotency_key);
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
