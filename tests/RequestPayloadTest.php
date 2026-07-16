<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutCreateBookingApplicantRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutCreateBookingRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerAccountListBookingsRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutQuoteRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerAccountUpdateApplicantRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutCheckPromotionRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerToursGetSimilarRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerToursGetAvailabilityRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerToursSearchRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerToursGetFeaturedRequest;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingApplicantResource;

final class RequestPayloadTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $transactions = [];

    /**
     * @param list<array<string, mixed>> $payloads
     */
    private function clientWith(array $payloads): PartnerClient
    {
        $this->transactions = [];
        $responses = array_map(
            static fn (array $data): Response => new Response(200, [], (string) json_encode([
                'status' => 200,
                'message' => 'OK',
                'data' => $data,
            ])),
            $payloads,
        );

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->transactions));

        return new PartnerClient(
            baseUrl: 'http://localhost:8000',
            clientId: 'cid',
            secret: 'sec',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );
    }

    private function request(int $index = 0): RequestInterface
    {
        return $this->transactions[$index]['request'];
    }

    /**
     * @return array<string, mixed>
     */
    private function sentJson(int $index = 0): array
    {
        $decoded = json_decode((string) $this->request($index)->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    public function test_booking_create_request_builds_the_documented_payload(): void
    {
        $client = $this->clientWith([['order_code' => 'TB123ABC']]);

        $client->bookings()->create(
            new PartnerCheckoutCreateBookingRequest(
                tourCode: 'IBTCARSGN3181',
                departureDate: '2026-08-01',
                name: 'Customer Name',
                phone: '0900000000',
                email: 'customer@example.com',
                applicants: [
                    new PartnerCheckoutCreateBookingApplicantRequest(type: 1, fullName: 'Customer Name', nationality: 'VN'),
                ],
                adultQuantity: 1,
                dialCode: '84',
                specialRequest: 'Window seat if possible',
            ),
            'idem-123',
        );

        $body = $this->sentJson();

        self::assertSame('IBTCARSGN3181', $body['tour_code']);
        self::assertSame('2026-08-01', $body['departure_date']);
        self::assertSame('Customer Name', $body['name']);
        self::assertSame('84', $body['dial_code']);
        self::assertSame('Window seat if possible', $body['special_request']);
        self::assertSame('Customer Name', $body['applicants'][0]['full_name']);
        self::assertSame('idem-123', $this->request()->getHeaderLine('X-Partner-Idempotency-Key'));
    }

    public function test_booking_quote_and_promotion_requests_are_accepted(): void
    {
        $client = $this->clientWith([
            ['total' => 33.57],
            ['promotion' => ['code' => 'PROMO10']],
        ]);

        $client->bookings()->quote(new PartnerCheckoutQuoteRequest(
            tourCode: 'IBTCARSGN3181',
            departureDate: '2026-08-01',
            adultQuantity: 2,
            promotionCode: 'PROMO10',
        ));
        $client->bookings()->checkPromotion(new PartnerCheckoutCheckPromotionRequest('PROMO10'));

        self::assertSame('PROMO10', $this->sentJson(0)['promotion_code']);
        self::assertSame('PROMO10', $this->sentJson(1)['code']);
    }

    public function test_booking_list_and_update_applicant_requests_are_accepted(): void
    {
        $client = $this->clientWith([
            ['current_page' => 1, 'total' => 0, 'per_page' => 20, 'last_page' => 1, 'bookings' => []],
            ['applicant' => ['id' => 7, 'full_name' => 'Updated Name']],
            ['applicant' => ['id' => 7, 'date_of_birth' => null]],
        ]);

        $client->bookings()->list(new PartnerAccountListBookingsRequest(status: 1, q: 'TB', page: 2, perPage: 10));
        $applicant = $client->bookings()->updateApplicantResource(
            'TB123ABC',
            7,
            new PartnerAccountUpdateApplicantRequest(fullName: 'Updated Name', gender: 1),
        );
        $client->bookings()->updateApplicant(
            'TB123ABC',
            7,
            PartnerAccountUpdateApplicantRequest::fromArray(['date_of_birth' => null]),
        );

        parse_str($this->request(0)->getUri()->getQuery(), $query);

        self::assertSame('1', $query['status']);
        self::assertSame('TB', $query['q']);
        self::assertInstanceOf(PartnerBookingApplicantResource::class, $applicant);
        self::assertSame('Updated Name', $applicant->fullName);
        self::assertSame(1, $this->sentJson(1)['gender']);
        self::assertArrayHasKey('date_of_birth', $this->sentJson(2));
        self::assertNull($this->sentJson(2)['date_of_birth']);
    }

    public function test_tour_query_requests_are_accepted(): void
    {
        $client = $this->clientWith([
            ['current_page' => 1, 'total' => 0, 'per_page' => 12, 'last_page' => 1, 'tours' => []],
            ['current_page' => 2, 'total' => 9, 'per_page' => 6, 'last_page' => 2, 'tours' => []],
            ['current_page' => 1, 'total' => 4, 'per_page' => 4, 'last_page' => 1, 'tours' => []],
            ['is_available' => true, 'remaining_slots' => 5],
        ]);

        $client->tours()->list(new PartnerToursSearchRequest(search: 'hcm', page: 1, perPage: 12));
        $client->tours()->featured(new PartnerToursGetFeaturedRequest(6, page: 2, perPage: 6));
        $client->tours()->similar(new PartnerToursGetSimilarRequest(tourCode: 'IBTCARSGN3181', take: 4, page: 1, perPage: 4));
        $client->tours()->calendarByDate('IBTCARSGN3181', new PartnerToursGetAvailabilityRequest('2026-08-01', pax: 2));

        parse_str($this->request(0)->getUri()->getQuery(), $listQuery);
        parse_str($this->request(1)->getUri()->getQuery(), $featuredQuery);
        parse_str($this->request(2)->getUri()->getQuery(), $similarQuery);
        parse_str($this->request(3)->getUri()->getQuery(), $dateQuery);

        self::assertSame('hcm', $listQuery['search']);
        self::assertSame('12', $listQuery['per_page']);
        self::assertSame('6', $featuredQuery['take']);
        self::assertSame('2', $featuredQuery['page']);
        self::assertSame('6', $featuredQuery['per_page']);
        self::assertSame('IBTCARSGN3181', $similarQuery['tour_code']);
        self::assertSame('4', $similarQuery['take']);
        self::assertSame('1', $similarQuery['page']);
        self::assertSame('4', $similarQuery['per_page']);
        self::assertSame('2026-08-01', $dateQuery['date']);
        self::assertSame('2', $dateQuery['pax']);
    }

    public function test_request_objects_fail_fast_for_obvious_invalid_payloads(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PartnerCheckoutQuoteRequest(
            tourCode: 'IBTCARSGN3181',
            departureDate: '2026-08-01',
            adultQuantity: 1,
            infantQuantity: 2,
        );
    }
}
