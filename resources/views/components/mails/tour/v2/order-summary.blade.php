@php
    $detail = $booking->detail;
    $currency = strtoupper((string) $booking->input_currency);
    $money = static fn ($value) => number_format((float) $value, $currency === 'VND' ? 0 : 2) . ' ' . $currency;
    $statusLabels = [1 => 'Pending Payment', 2 => 'In Progress', 3 => 'Completed', 4 => 'Canceled', 5 => 'Refunded', 6 => 'Expired'];
    $statusColors = [1 => '#FF7827', 2 => '#0056D2', 3 => '#39A36B', 4 => '#FF060A', 5 => '#39A36B', 6 => '#777777'];
    $statusColor = $statusColors[(int) $booking->status] ?? '#FF7827';
    $genderLabels = [1 => 'Male', 2 => 'Female'];
@endphp

<div style="margin-top: 24px;">
    <table>
        <tr>
            <td valign="top" style="padding: 0; border: 0;">
                <div class="fs-h3 fw-semibold text-black">Booking Details</div>
                <div class="fs-xl" style="color: #F3C024; margin-top: 4px;">Order: #{{ $booking->order_code }}</div>
            </td>
            <td align="right" valign="top" style="padding: 0; border: 0;">
                <span class="fs-lg" style="display:inline-block;padding:6px 12px;border-radius:8px;color:{{ $statusColor }};background:{{ $statusColor }}33;">
                    {{ $statusLabels[(int) $booking->status] ?? 'Pending Payment' }}
                </span>
            </td>
        </tr>
    </table>

    <table style="margin-top: 16px;">
        <tr>
            <td class="fs-lg fw-bold text-black" style="padding: 5px 0; border: 0;">Payment Amount</td>
            <td align="right" class="fs-lg text-black" style="padding: 5px 0; border: 0;">{{ $money($booking->input_total) }}</td>
        </tr>
        <tr>
            <td class="fs-lg fw-bold text-black" style="padding: 5px 0; border: 0;">Booking Date</td>
            <td align="right" class="fs-lg text-black" style="padding: 5px 0; border: 0;">{{ $booking->created_at?->format('d/m/Y') }}</td>
        </tr>
        @if ($detail?->departure_date)
            <tr>
                <td class="fs-lg fw-bold text-black" style="padding: 5px 0; border: 0;">Departure Date</td>
                <td align="right" class="fs-lg text-black" style="padding: 5px 0; border: 0;">{{ \Illuminate\Support\Carbon::parse($detail->departure_date)->format('d/m/Y') }}</td>
            </tr>
        @endif
    </table>
</div>

<hr style="border: none; border-top: 1px solid #D1D1D1; margin: 24px 0;">

<div>
    <div class="fs-h3 fw-semibold text-black" style="margin-bottom: 16px;">Price Details</div>
    @if ($detail)
        <table>
            <tr>
                <td valign="bottom" style="padding: 0 0 16px; border: 0;">
                    <div class="fs-lg fw-bold text-black">Adult</div>
                    <div class="fs-lg">{{ $money($detail->input_adult_price) }} (x{{ $detail->adult_quantity }})</div>
                </td>
                <td align="right" valign="bottom" class="fs-lg fw-bold text-primary" style="padding: 0 0 16px; border: 0;">{{ $money($detail->input_adult_price * $detail->adult_quantity) }}</td>
            </tr>
            @if ($detail->child_quantity > 0)
                <tr>
                    <td valign="bottom" style="padding: 0 0 16px; border: 0;">
                        <div class="fs-lg fw-bold text-black">Children</div>
                        <div class="fs-lg">{{ $money($detail->input_child_price) }} (x{{ $detail->child_quantity }})</div>
                    </td>
                    <td align="right" valign="bottom" class="fs-lg fw-bold text-primary" style="padding: 0 0 16px; border: 0;">{{ $money($detail->input_child_price * $detail->child_quantity) }}</td>
                </tr>
            @endif
            @if ($detail->infant_quantity > 0)
                <tr>
                    <td valign="bottom" style="padding: 0 0 16px; border: 0;">
                        <div class="fs-lg fw-bold text-black">Infants</div>
                        <div class="fs-lg">{{ $detail->input_infant_price > 0 ? $money($detail->input_infant_price) : 'Free' }} (x{{ $detail->infant_quantity }})</div>
                    </td>
                    <td align="right" valign="bottom" class="fs-lg fw-bold text-primary" style="padding: 0 0 16px; border: 0;">{{ $detail->input_infant_price > 0 ? $money($detail->input_infant_price * $detail->infant_quantity) : 'Free' }}</td>
                </tr>
            @endif
        </table>
    @endif

    <hr style="border: none; border-top: 1px solid #D1D1D1; margin: 0 0 12px;">
    <table>
        <tr>
            <td class="fs-lg fw-bold text-black" style="padding: 4px 0; border: 0;">Sub total</td>
            <td align="right" class="fs-lg text-primary" style="padding: 4px 0; border: 0;">{{ $money($booking->input_sub_total) }}</td>
        </tr>
        <tr>
            <td class="fs-lg fw-bold text-black" style="padding: 4px 0; border: 0;">Discount</td>
            <td align="right" class="fs-lg text-primary" style="padding: 4px 0; border: 0;">{{ $money($booking->input_discount) }}</td>
        </tr>
        <tr>
            <td class="fs-xl fw-bold text-black" style="padding: 4px 0; border: 0;">Total</td>
            <td align="right" class="fs-xl fw-semibold text-primary" style="padding: 4px 0; border: 0;">{{ $money($booking->input_total) }}</td>
        </tr>
    </table>
</div>

@if ($booking->applicants->isNotEmpty())
    <hr style="border: none; border-top: 1px solid #D1D1D1; margin: 24px 0;">
    <div>
        <div class="fs-h3 fw-semibold text-black" style="margin-bottom: 16px;">Participant Details</div>
        @foreach ($booking->applicants as $applicant)
            <div style="margin-bottom: 20px;">
                <span class="fs-h5 fw-bold text-primary" style="display:inline-block;padding:8px 16px;border:1px solid #0A438B;border-radius:16px;">
                    {{ [1 => 'Adult', 2 => 'Children', 3 => 'Infant'][$applicant->type] ?? 'Traveller' }}
                </span>
                <table style="margin-top: 12px; width: 100%; table-layout: fixed;">
                    <tr>
                        <td width="50%" valign="top" style="padding: 0 12px 0 0; border: 0;">
                            <div class="fs-lg fw-bold text-black">Full Name</div>
                            <div class="fs-xl text-black">{{ $applicant->full_name ?: 'Not provided' }}</div>
                        </td>
                        <td width="50%" valign="top" style="padding: 0; border: 0;">
                            <div class="fs-lg fw-bold text-black">Gender</div>
                            <div class="fs-xl text-black">{{ $genderLabels[$applicant->gender] ?? 'Not provided' }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
@endif
