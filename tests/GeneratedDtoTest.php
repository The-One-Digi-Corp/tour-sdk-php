<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests;

use PHPUnit\Framework\TestCase;
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutCreateBookingApplicantRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerCheckoutCreateBookingRequest;
use TheOneDigi\TourSdk\Generated\Request\PartnerToursSearchRequest;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingApplicantResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingDetailResource;
use TheOneDigi\TourSdk\Generated\Resource\PartnerBookingResource;

final class GeneratedDtoTest extends TestCase
{
    public function test_generated_dtos_are_in_sync_with_the_openapi_fixture(): void
    {
        $output = [];
        $exitCode = 0;

        $command = sprintf(
            '%s %s --check 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(dirname(__DIR__) . '/tools/generate-contract-dtos.php'),
        );

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
    }

    public function test_generated_request_objects_build_contract_payloads(): void
    {
        $create = new PartnerCheckoutCreateBookingRequest(
            name: 'Customer Name',
            phone: '0900000000',
            email: 'customer@example.com',
            orderDetails: [
                'tour_id' => 42,
                'departure_date' => '2026-08-01',
                'adult_quantity' => 1,
            ],
            applicants: [
                new PartnerCheckoutCreateBookingApplicantRequest(type: 1, fullName: 'Customer Name'),
            ],
            dialCode: '84',
        );

        self::assertSame([
            'name' => 'Customer Name',
            'phone' => '0900000000',
            'email' => 'customer@example.com',
            'order_details' => [
                'tour_id' => 42,
                'departure_date' => '2026-08-01',
                'adult_quantity' => 1,
            ],
            'applicants' => [
                ['type' => 1, 'full_name' => 'Customer Name'],
            ],
            'dial_code' => '84',
        ], $create->toArray());

        $search = PartnerToursSearchRequest::fromArray([
            'search' => 'vung tau',
            'minPrice' => '10',
            'per_page' => '12',
        ]);

        self::assertSame([
            'search' => 'vung tau',
            'minPrice' => 10.0,
            'per_page' => 12,
        ], $search->toArray());
    }

    public function test_generated_resources_expose_contract_fields_and_keep_raw_payload(): void
    {
        $booking = new PartnerBookingResource([
            'id' => '1',
            'order_code' => 'TLU7SCK',
            'status' => '3',
            'sub_total' => '33.57',
            'discount' => '0',
            'total' => '33.57',
            'cost' => '20.14',
            'currency' => 'USD',
            'base_currency' => 'USD',
            'input_currency' => 'USD',
            'sub_total_amounts' => [['currency' => 'USD', 'amount' => '33.57']],
            'total_amounts' => [['currency' => 'USD', 'amount' => '33.57']],
            'promotion_code' => '',
            'name' => 'Customer Name',
            'email' => 'customer@example.com',
            'email2' => '',
            'dial_code' => '84',
            'phone' => '0900000000',
            'paid_at' => '2026-07-15 11:49:31',
            'created_at' => '2026-07-15 11:48:18',
            'tour_booking_detail' => ['id' => 9, 'tour_id' => 1, 'departure_date' => '2026-08-01'],
            'tour_booking_applicants' => [[
                'id' => 7,
                'tour_booking_id' => 1,
                'type' => 1,
                'full_name' => 'Applicant Name',
                'gender' => null,
                'date_of_birth' => '1990-01-15',
                'nationality' => 'VN',
                'passport_photo' => 'upload/passports/a.jpg',
                'created_at' => '2026-07-15 11:48:18',
                'updated_at' => '2026-07-15 11:48:18',
            ]],
            'tour_booking_refund' => null,
            'commission_rate' => 15,
            'commission_amount' => '5.04',
            'future_field' => 'kept',
        ]);

        self::assertSame('TLU7SCK', $booking->orderCode);
        // int, not '3': the contract types status as an integer now that travelo-api
        // resolves it through the model. The payload still sends a string and the
        // resource coerces it — that is the point of coercing at all.
        self::assertSame(3, $booking->status);
        self::assertSame(15.0, $booking->commissionRate);
        self::assertInstanceOf(PartnerBookingDetailResource::class, $booking->tourBookingDetail);
        self::assertSame('2026-08-01', $booking->tourBookingDetail->departureDate);
        self::assertInstanceOf(PartnerBookingApplicantResource::class, $booking->tourBookingApplicants[0]);
        self::assertSame('Applicant Name', $booking->tourBookingApplicants[0]->fullName);
        self::assertNull($booking->tourBookingRefund);
        self::assertSame('kept', $booking->toArray()['future_field']);
    }
}
