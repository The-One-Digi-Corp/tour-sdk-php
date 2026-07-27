@extends('travelo::mails.tour.v2.layout')

@section('content')
    <div class="text-center">
        <div class="fs-h2 fw-medium text-primary" style="margin-bottom: 8px;">Booking Created!</div>
        <div class="fs-h4 fw-medium text-black" style="margin-bottom: 24px;">
            Booking ID: <span class="text-primary">#{{ $booking->order_code }}</span> - Your reservation details are ready!
        </div>
    </div>

    <div class="text-black" style="margin-top: 24px; font-family: 'Manrope', Arial, sans-serif;">
        <div class="fs-lg" style="margin-bottom: 12px;">Dear {{ $booking->name }},</div>
        <div class="fs-lg" style="margin-bottom: 24px;">
            Thank you for booking with us! Below are the details of your reservation:
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid #D1D1D1; margin: 24px 0 0;">

    @include('travelo::components.mails.tour.v2.order-summary', ['booking' => $booking])

    @include('travelo::components.mails.tour.v2.contact-card', [
        'subtext' => 'Thank you for your prompt attention to this matter. We look forward to confirming your booking and providing you with an exceptional travel experience!'
    ])
@endsection
