<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use TheOneDigi\TourSdk\Exception\ApiException;
use TheOneDigi\TourSdk\Exception\TransportException;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\PartnerSigner;

final class PartnerClientTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $transactions = [];

    /**
     * @param list<Response|\Throwable> $responses
     */
    private function clientWith(array $responses): PartnerClient
    {
        $this->transactions = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->transactions));

        return new PartnerClient(
            baseUrl: 'http://localhost:8000/api',
            clientId: 'travelo_test_client',
            secret: 'travelo_test_secret',
            defaultCurrency: 'USD',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );
    }

    private function lastRequest(): RequestInterface
    {
        return $this->transactions[0]['request'];
    }

    private static function envelope(mixed $data): Response
    {
        return new Response(200, [], json_encode(['status' => 200, 'message' => 'OK', 'data' => $data]));
    }

    public function test_strips_trailing_api_from_the_base_url(): void
    {
        self::assertSame('http://localhost:8000', PartnerClient::normalizeBaseUrl('http://localhost:8000/api/'));
        self::assertSame('http://localhost:8000', PartnerClient::normalizeBaseUrl('http://localhost:8000'));
        self::assertSame('https://x.dev/v1', PartnerClient::normalizeBaseUrl('https://x.dev/v1/'));
    }

    public function test_builds_the_url_so_the_signed_path_matches_the_server_path(): void
    {
        $client = $this->clientWith([self::envelope(['ok' => true])]);

        $client->get('api/partner/tours');

        self::assertSame(
            'http://localhost:8000/api/partner/tours',
            (string) $this->lastRequest()->getUri(),
        );
    }

    public function test_signature_covers_the_exact_bytes_that_are_sent(): void
    {
        $client = $this->clientWith([self::envelope(['total' => 100])]);

        $client->post('api/partner/bookings/quote', ['tour_code' => 'T-1', 'adult_quantity' => 2]);

        $request = $this->lastRequest();
        $sentBody = (string) $request->getBody();
        $signer = new PartnerSigner('travelo_test_client', 'travelo_test_secret');

        self::assertSame(
            $signer->sign('POST', 'api/partner/bookings/quote', $sentBody, $request->getHeaderLine(PartnerSigner::HEADER_TIMESTAMP)),
            $request->getHeaderLine(PartnerSigner::HEADER_SIGNATURE),
            'Signature must be computed over the body actually transmitted.',
        );
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function test_query_string_is_sent_but_excluded_from_the_signature(): void
    {
        $client = $this->clientWith([self::envelope([])]);

        $client->get('api/partner/bookings', ['page' => 2, 'status' => 'pending_payment']);

        $request = $this->lastRequest();
        $signer = new PartnerSigner('travelo_test_client', 'travelo_test_secret');

        self::assertStringContainsString('page=2', $request->getUri()->getQuery());
        self::assertSame(
            $signer->sign('GET', 'api/partner/bookings', '', $request->getHeaderLine(PartnerSigner::HEADER_TIMESTAMP)),
            $request->getHeaderLine(PartnerSigner::HEADER_SIGNATURE),
        );
    }

    public function test_sends_identity_and_currency_headers(): void
    {
        $client = $this->clientWith([self::envelope([])]);

        $client->get('api/partner/tours');

        $request = $this->lastRequest();

        self::assertSame('tour-sdk-php', $request->getHeaderLine('X-Travelo-SDK-Name'));
        self::assertSame('USD', $request->getHeaderLine('X-Currency'));
        self::assertNotSame('', $request->getHeaderLine('X-Request-Id'));
    }

    public function test_get_sends_no_body_and_signs_the_empty_hash(): void
    {
        $client = $this->clientWith([self::envelope([])]);

        $client->get('api/partner/tours');

        $request = $this->lastRequest();
        $signer = new PartnerSigner('travelo_test_client', 'travelo_test_secret');

        self::assertSame('', (string) $request->getBody());
        self::assertSame(
            $signer->sign('GET', 'api/partner/tours', '', $request->getHeaderLine(PartnerSigner::HEADER_TIMESTAMP)),
            $request->getHeaderLine(PartnerSigner::HEADER_SIGNATURE),
        );
    }

    public function test_booking_create_forwards_the_idempotency_key(): void
    {
        $client = $this->clientWith([self::envelope(['order_code' => 'TB-1'])]);

        $result = $client->bookings()->create(['tour_code' => 'T-1'], 'idem-123');

        self::assertSame(['order_code' => 'TB-1'], $result);
        self::assertSame('idem-123', $this->lastRequest()->getHeaderLine('X-Partner-Idempotency-Key'));
    }

    public function test_unwraps_the_data_envelope(): void
    {
        $client = $this->clientWith([self::envelope(['order_code' => 'TB-9', 'status' => 1])]);

        self::assertSame(['order_code' => 'TB-9', 'status' => 1], $client->bookings()->show('TB-9'));
    }

    public function test_list_keeps_pagination_which_lives_inside_data(): void
    {
        $client = $this->clientWith([
            self::envelope(['current_page' => 1, 'total' => 7, 'per_page' => 2, 'last_page' => 4, 'bookings' => []]),
        ]);

        $result = $client->bookings()->list(['per_page' => 2]);

        self::assertSame(7, $result['total']);
        self::assertSame(4, $result['last_page']);
        self::assertSame([], $result['bookings']);
    }

    public function test_error_response_raises_an_api_exception_with_status_and_payload(): void
    {
        $client = $this->clientWith([
            new Response(403, ['X-Correlation-Id' => 'corr-1'], json_encode([
                'status' => 403,
                'message' => 'Forbidden. Credential is missing the required scope: tour:book.',
            ])),
        ]);

        try {
            $client->bookings()->create(['tour_code' => 'T-1'], 'idem-1');
            self::fail('Expected ApiException.');
        } catch (ApiException $e) {
            self::assertSame(403, $e->status());
            self::assertTrue($e->isScopeFailure());
            self::assertFalse($e->isAuthFailure());
            self::assertSame('corr-1', $e->correlationId());
            self::assertStringContainsString('tour:book', $e->getMessage());
        }
    }

    public function test_sold_out_surfaces_as_a_conflict(): void
    {
        $client = $this->clientWith([new Response(409, [], json_encode(['message' => 'Sold out']))]);

        try {
            $client->bookings()->create(['tour_code' => 'T-1'], 'idem-1');
            self::fail('Expected ApiException.');
        } catch (ApiException $e) {
            self::assertTrue($e->isConflict());
        }
    }

    public function test_network_failure_raises_a_transport_exception(): void
    {
        $client = $this->clientWith([
            new \GuzzleHttp\Exception\ConnectException('timeout', new \GuzzleHttp\Psr7\Request('POST', 'x')),
        ]);

        try {
            $client->bookings()->confirm('TB-1');
            self::fail('Expected TransportException.');
        } catch (TransportException $e) {
            self::assertTrue($e->isNetworkFailure());
            self::assertSame('POST', $e->method);
            self::assertSame('api/partner/bookings/TB-1/confirm', $e->path);
            self::assertSame('http://localhost:8000/api/partner/bookings/TB-1/confirm', $e->url);
            self::assertStringContainsString('did not return a response', $e->getMessage());
        }
    }
}
