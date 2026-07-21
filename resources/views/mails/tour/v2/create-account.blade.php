@extends('travelo::mails.tour.v2.layout')

@section('content')
    <div class="text-center">
        <div class="fs-h2 fw-medium text-primary" style="margin-bottom:8px;">Account Created!</div>
        <div class="fs-h4 fw-medium text-black" style="margin-bottom:24px;">
            Your {{ config('app.name', 'Travelo') }} account is ready.
        </div>
    </div>

    <div class="text-black" style="margin-top:24px;">
        <div class="fs-lg" style="margin-bottom:12px;">Dear {{ $user->name }},</div>
        <div class="fs-lg" style="margin-bottom:24px;">
            An account has been created so you can view and manage your booking.
        </div>
    </div>

    <hr style="border:none;border-top:1px solid #D1D1D1;margin:24px 0;">

    <div class="fs-h3 fw-semibold" style="margin-bottom:16px;">Login Details</div>
    <table style="background:#F4F5F9;border-radius:8px;">
        <tr>
            <td class="fs-lg fw-bold" style="padding:12px 16px;">Email</td>
            <td align="right" class="fs-lg" style="padding:12px 16px;">{{ $user->email }}</td>
        </tr>
        <tr>
            <td class="fs-lg fw-bold" style="padding:12px 16px;">Temporary Password</td>
            <td align="right" class="fs-lg text-primary" style="padding:12px 16px;font-family:monospace;font-weight:700;">{{ $plainPassword }}</td>
        </tr>
    </table>

    <div class="fs-lg" style="margin-top:16px;color:#444;">
        For your security, please sign in and change this password as soon as possible.
    </div>

    @include('travelo::components.mails.tour.v2.contact-card')
@endsection
