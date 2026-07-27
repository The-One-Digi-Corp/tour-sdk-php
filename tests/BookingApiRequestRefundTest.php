<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingRefundResource;
use TheOneDigi\TourSdk\PartnerClient;

/**
 * Guards the refund-request call the SDK adds for the partner refund loop:
 * it must POST to /{code}/request-refund with the reason, and unwrap the `refund`
 * object travelo-api nests under `data`.
 */
final class BookingApiRequestRefundTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $transactions = [];

    /**
     * @param array<string, mixed> $responseData
     */
    private function client(array $responseData): PartnerClient
    {
        $this->transactions = [];

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode(['status' => 200, 'data' => $responseData])),
        ]));
        $stack->push(Middleware::history($this->transactions));

        return new PartnerClient(
            baseUrl: 'http://localhost:8000',
            clientId: 'cid',
            secret: 'sec',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );
    }

    public function test_request_refund_posts_to_request_refund_path_with_reasons(): void
    {
        $client = $this->client([
            'refund' => [
                'id' => 5,
                'refund_total' => 200.0,
                'status' => 2,
                'status_text' => 'Pending',
                'currency' => 'USD',
            ],
        ]);

        $refund = $client->bookings()->requestRefund('ORDER123', ['reasons' => 'Change of plans']);

        // Envelope unwrapped down to the refund object.
        $this->assertSame(5, $refund['id']);
        $this->assertSame(2, $refund['status']);

        $request = $this->transactions[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/partner/bookings/ORDER123/request-refund', $request->getUri()->getPath());
        $this->assertStringContainsString('Change of plans', (string) $request->getBody());
    }

    public function test_request_refund_resource_hydrates_typed_refund(): void
    {
        $client = $this->client([
            'refund' => [
                'id' => 7,
                'refund_total' => 150.5,
                'status' => 3,
                'status_text' => 'Approved',
                'currency' => 'USD',
            ],
        ]);

        $refund = $client->bookings()->requestRefundResource('ORDER9');

        $this->assertInstanceOf(PartnerBookingRefundResource::class, $refund);
        $this->assertSame(7, $refund->id);
        $this->assertSame('Approved', $refund->statusText);
    }

    public function test_refunds_by_booking_ids_posts_upstream_booking_ids(): void
    {
        $client = $this->client([
            'refunds' => [[
                'tour_booking_id' => 782,
                'order_code' => '2QDZFAN',
                'booking_status' => 5,
                'refund' => [
                    'id' => 76,
                    'refund_total' => 33.57,
                    'status' => 3,
                    'status_text' => 'Approved',
                    'currency' => 'USD',
                ],
            ]],
        ]);

        $refunds = $client->bookings()->refundsByBookingIds(['782']);

        $this->assertSame(782, $refunds[0]['tour_booking_id']);
        $this->assertSame(76, $refunds[0]['refund']['id']);

        $request = $this->transactions[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/partner/bookings/refunds', $request->getUri()->getPath());
        $this->assertStringContainsString('782', (string) $request->getBody());
    }
}
