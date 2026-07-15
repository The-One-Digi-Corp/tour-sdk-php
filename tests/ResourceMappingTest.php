<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TheOneDigi\TourSdk\PartnerClient;
use TheOneDigi\TourSdk\Resource\BookingListResource;
use TheOneDigi\TourSdk\Resource\BookingQuoteResource;
use TheOneDigi\TourSdk\Resource\BookingResource;
use TheOneDigi\TourSdk\Resource\TourCalendarDateResource;
use TheOneDigi\TourSdk\Resource\TourListResource;
use TheOneDigi\TourSdk\Resource\TourResource;

final class ResourceMappingTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $payloads
     */
    private function clientWith(array $payloads): PartnerClient
    {
        $responses = array_map(
            static fn (array $data): Response => new Response(200, [], (string) json_encode([
                'status' => 200,
                'message' => 'OK',
                'data' => $data,
            ])),
            $payloads,
        );

        $stack = HandlerStack::create(new MockHandler($responses));

        return new PartnerClient(
            baseUrl: 'http://localhost:8000',
            clientId: 'cid',
            secret: 'sec',
            http: new Client(['handler' => $stack, 'http_errors' => false]),
        );
    }

    public function test_booking_create_resource_exposes_typed_fields_and_keeps_raw_payload(): void
    {
        $client = $this->clientWith([$this->bookingPayload()]);

        $booking = $client->bookings()->createResource(['tour_code' => 'T-1'], 'idem-1');

        self::assertInstanceOf(BookingResource::class, $booking);
        self::assertSame('TB123ABC', $booking->orderCode);
        self::assertSame(33.57, $booking->payableAmount());
        self::assertSame('USD', $booking->payableCurrency());
        self::assertSame(15.0, $booking->commissionRate);
        self::assertSame(1, $booking->detail?->tourPriceGroupId);
        self::assertSame(33.57, $booking->detail?->inputAdultPrice);
        self::assertSame('Customer Name', $booking->applicants[0]->fullName);
        self::assertSame('kept-for-forward-compat', $booking->get('new_future_field'));
        self::assertSame('kept-for-forward-compat', $booking->toArray()['new_future_field']);
    }

    public function test_booking_list_and_quote_resources(): void
    {
        $client = $this->clientWith([
            [
                'current_page' => 1,
                'total' => 1,
                'per_page' => 20,
                'last_page' => 1,
                'bookings' => [$this->bookingPayload()],
            ],
            [
                'sub_total' => 33.57,
                'discount' => 0,
                'total' => 33.57,
                'cost' => 0,
                'currency' => 'USD',
                'prices' => ['adult_price' => 33.57],
                'is_available' => true,
                'remaining_slots' => 9,
            ],
        ]);

        $list = $client->bookings()->listResources();
        $quote = $client->bookings()->quoteResource(['tour_code' => 'T-1']);

        self::assertInstanceOf(BookingListResource::class, $list);
        self::assertSame(1, $list->total);
        self::assertSame('TB123ABC', $list->bookings[0]->orderCode);
        self::assertInstanceOf(BookingQuoteResource::class, $quote);
        self::assertTrue($quote->isAvailable);
        self::assertSame(9, $quote->remainingSlots);
        self::assertSame(33.57, $quote->prices['adult_price']);
    }

    public function test_tour_resources(): void
    {
        $client = $this->clientWith([
            $this->tourPayload(),
            ['current_page' => 1, 'total' => 1, 'per_page' => 10, 'last_page' => 1, 'tours' => [$this->tourPayload()]],
            [
                'is_available' => true,
                'remaining_slots' => 8,
                'tour_price_group_id' => 1,
                'price' => ['adult_price' => 33.57],
                'all_prices' => [['id' => 1, 'adult_price' => 33.57, 'child_price' => 23.5, 'infant_price' => 0]],
            ],
        ]);

        $tour = $client->tours()->showResource('T-1');
        $list = $client->tours()->listResources();
        $date = $client->tours()->calendarByDateResource('T-1', ['date' => '2026-08-01']);

        self::assertInstanceOf(TourResource::class, $tour);
        self::assertSame('T-1', $tour->code);
        self::assertSame(33.57, $tour->prices[0]->adultPrice);
        self::assertInstanceOf(TourListResource::class, $list);
        self::assertSame('T-1', $list->tours[0]->code);
        self::assertInstanceOf(TourCalendarDateResource::class, $date);
        self::assertSame(8, $date->remainingSlots);
        self::assertSame(33.57, $date->allPrices[0]->adultPrice);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(): array
    {
        return [
            'id' => 7,
            'order_code' => 'TB123ABC',
            'status' => 1,
            'sub_total' => 33.57,
            'discount' => 0,
            'total' => 33.57,
            'cost' => 0,
            'currency' => 'USD',
            'base_currency' => 'USD',
            'input_sub_total' => 33.57,
            'input_discount' => 0,
            'input_total' => 33.57,
            'input_cost' => 0,
            'input_currency' => 'USD',
            'input_currency_version' => 1,
            'input_currency_exchange_rate' => 1,
            'commission_rate' => 15,
            'commission_amount' => 5.04,
            'name' => 'Customer Name',
            'email' => 'customer@example.com',
            'tour_booking_detail' => [
                'id' => 9,
                'tour_id' => 1,
                'tour_price_group_id' => 1,
                'departure_date' => '2026-08-01',
                'adult_quantity' => 1,
                'child_quantity' => 0,
                'infant_quantity' => 0,
                'adult_price' => 33.57,
                'child_price' => 23.5,
                'infant_price' => 0,
                'input_adult_price' => 33.57,
                'input_child_price' => 23.5,
                'input_infant_price' => 0,
                'input_currency' => 'USD',
            ],
            'tour_booking_applicants' => [
                ['id' => 11, 'type' => 1, 'full_name' => 'Customer Name'],
            ],
            'new_future_field' => 'kept-for-forward-compat',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tourPayload(): array
    {
        return [
            'id' => 1,
            'code' => 'T-1',
            'name' => 'Sample Tour',
            'currency' => 'USD',
            'slug' => 'sample-tour',
            'duration_text' => '1 day',
            'thumbnail' => '/tour.jpg',
            'base_price' => 33.57,
            'translations' => ['name' => 'Sample Tour'],
            'prices' => [
                ['id' => 1, 'adult_price' => 33.57, 'child_price' => 23.5, 'infant_price' => 0],
            ],
        ];
    }
}
