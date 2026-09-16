<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Generated\Resource;

use TheOneDigi\TourSdk\Common\ArrayBackedResource;
/* BEGIN MANUAL IMPORTS */
/* END MANUAL IMPORTS */

/**
 * Generated from OpenAPI schema PartnerBookingResource.
 *
 * AUTO FIELDS and AUTO HYDRATION are rewritten by composer generate:contract.
 * MANUAL FIELDS and MANUAL HYDRATION survive regeneration — put hand-written
 * fields there. A manual property replaces the auto field of the same name.
 */
class PartnerBookingResource extends ArrayBackedResource
{
    /* BEGIN AUTO FIELDS */
    public readonly int $id;
    public readonly string $orderCode;
    public readonly int $status;
    public readonly float $subTotal;
    public readonly float $discount;
    public readonly float $total;
    public readonly float $cost;
    public readonly string $currency;
    public readonly string $inputCurrency;
    /** @var list<MoneyAmountResource> */
    public readonly array $subTotalAmounts;
    /** @var list<MoneyAmountResource> */
    public readonly array $totalAmounts;
    public readonly ?string $promotionCode;
    public readonly ?string $name;
    public readonly ?string $email;
    public readonly ?string $email2;
    public readonly ?int $dialCode;
    public readonly ?string $phone;
    public readonly int $userId;
    public readonly ?string $paidAt;
    public readonly ?string $createdAt;
    public readonly ?PartnerBookingDetailResource $tourBookingDetail;
    /** @var list<PartnerBookingApplicantResource> */
    public readonly array $tourBookingApplicants;
    public readonly ?PartnerBookingRefundResource $tourBookingRefund;
    public readonly float $commissionRate;
    public readonly float $commissionAmount;
    /** @var array<string, mixed>|list<mixed> */
    public readonly array $commission;
    public readonly float $inputSubTotal;
    public readonly float $inputDiscount;
    public readonly float $inputTotal;
    public readonly float $inputCost;
    public readonly int $inputCurrencyVersion;
    public readonly float $inputCurrencyExchangeRate;
    /* END AUTO FIELDS */

    /* BEGIN MANUAL FIELDS */
    /* END MANUAL FIELDS */

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        /* BEGIN AUTO HYDRATION */
        $this->id = $this->int('id');
        $this->orderCode = $this->string('order_code');
        $this->status = $this->int('status');
        $this->subTotal = $this->float('sub_total');
        $this->discount = $this->float('discount');
        $this->total = $this->float('total');
        $this->cost = $this->float('cost');
        $this->currency = $this->string('currency');
        $this->inputCurrency = $this->string('input_currency');
        $this->subTotalAmounts = self::resourceList($this->array('sub_total_amounts'), MoneyAmountResource::class);
        $this->totalAmounts = self::resourceList($this->array('total_amounts'), MoneyAmountResource::class);
        $this->promotionCode = $this->nullableString('promotion_code');
        $this->name = $this->nullableString('name');
        $this->email = $this->nullableString('email');
        $this->email2 = $this->nullableString('email2');
        $this->dialCode = $this->nullableInt('dial_code');
        $this->phone = $this->nullableString('phone');
        $this->userId = $this->int('user_id');
        $this->paidAt = $this->nullableString('paid_at');
        $this->createdAt = $this->nullableString('created_at');
        $this->tourBookingDetail = is_array($this->get('tour_booking_detail')) ? PartnerBookingDetailResource::fromArray($this->get('tour_booking_detail')) : null;
        $this->tourBookingApplicants = self::resourceList($this->array('tour_booking_applicants'), PartnerBookingApplicantResource::class);
        $this->tourBookingRefund = is_array($this->get('tour_booking_refund')) ? PartnerBookingRefundResource::fromArray($this->get('tour_booking_refund')) : null;
        $this->commissionRate = $this->float('commission_rate');
        $this->commissionAmount = $this->float('commission_amount');
        $this->commission = $this->array('commission');
        $this->inputSubTotal = $this->float('input_sub_total');
        $this->inputDiscount = $this->float('input_discount');
        $this->inputTotal = $this->float('input_total');
        $this->inputCost = $this->float('input_cost');
        $this->inputCurrencyVersion = $this->int('input_currency_version');
        $this->inputCurrencyExchangeRate = $this->float('input_currency_exchange_rate');
        /* END AUTO HYDRATION */

        $this->hydrateManual();
    }

    /* BEGIN MANUAL HYDRATION */
    /**
     * The amount to charge: the frozen order total, never the display value.
     *
     * `total` above is presentation — travelo-api converted it to whatever
     * X-Currency asked for, at today's rate. `input_total` is what the order froze
     * at. Charging the display value bills the customer a number the order never
     * agreed to. No schema can express which of the two you are supposed to use,
     * so this stays hand-written however good the contract gets.
     */
    public function payableAmount(): float
    {
        return $this->inputTotal;
    }

    public function payableCurrency(): string
    {
        return $this->inputCurrency;
    }
    /* END MANUAL HYDRATION */
}
