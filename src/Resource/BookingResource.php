<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Resource;

class BookingResource extends ArrayBackedResource
{
    public readonly int|string|null $id;
    public readonly string $orderCode;
    public readonly int|string|null $status;
    public readonly float $subTotal;
    public readonly float $discount;
    public readonly float $total;
    public readonly float $cost;
    public readonly string $currency;
    public readonly string $baseCurrency;
    public readonly float $inputSubTotal;
    public readonly float $inputDiscount;
    public readonly float $inputTotal;
    public readonly float $inputCost;
    public readonly string $inputCurrency;
    public readonly int $inputCurrencyVersion;
    public readonly float $inputCurrencyExchangeRate;
    public readonly float $commissionRate;
    public readonly float $commissionAmount;
    public readonly ?string $promotionCode;
    public readonly ?string $name;
    public readonly ?string $email;
    public readonly ?string $email2;
    public readonly ?string $dialCode;
    public readonly ?string $phone;
    public readonly ?string $paidAt;
    public readonly ?string $createdAt;
    public readonly ?BookingDetailResource $detail;

    /**
     * @var list<BookingApplicantResource>
     */
    public readonly array $applicants;

    /**
     * @var array<string, mixed>|null
     */
    public readonly ?array $refund;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->id = $this->scalar('id');
        $this->orderCode = $this->string('order_code');
        $this->status = $this->scalar('status');
        $this->subTotal = $this->float('sub_total');
        $this->discount = $this->float('discount');
        $this->total = $this->float('total');
        $this->cost = $this->float('cost');
        $this->currency = $this->string('currency');
        $this->baseCurrency = $this->string('base_currency', $this->currency);
        $this->inputSubTotal = $this->float('input_sub_total', $this->subTotal);
        $this->inputDiscount = $this->float('input_discount', $this->discount);
        $this->inputTotal = $this->float('input_total', $this->total);
        $this->inputCost = $this->float('input_cost', $this->cost);
        $this->inputCurrency = $this->string('input_currency', $this->currency);
        $this->inputCurrencyVersion = $this->int('input_currency_version', 1);
        $this->inputCurrencyExchangeRate = $this->float('input_currency_exchange_rate', 1.0);
        $this->commissionRate = $this->float('commission_rate');
        $this->commissionAmount = $this->float('commission_amount');
        $this->promotionCode = $this->nullableString('promotion_code');
        $this->name = $this->nullableString('name');
        $this->email = $this->nullableString('email');
        $this->email2 = $this->nullableString('email2');
        $this->dialCode = $this->nullableString('dial_code');
        $this->phone = $this->nullableString('phone');
        $this->paidAt = $this->nullableString('paid_at');
        $this->createdAt = $this->nullableString('created_at');

        $detail = $attributes['tour_booking_detail'] ?? null;
        $this->detail = is_array($detail) ? BookingDetailResource::fromArray($detail) : null;

        $this->applicants = self::resourceList(
            $this->array('tour_booking_applicants'),
            BookingApplicantResource::class,
        );

        $refund = $attributes['tour_booking_refund'] ?? null;
        $this->refund = is_array($refund) ? $refund : null;
    }

    public function payableAmount(): float
    {
        return $this->inputTotal;
    }

    public function payableCurrency(): string
    {
        return $this->inputCurrency;
    }
}
