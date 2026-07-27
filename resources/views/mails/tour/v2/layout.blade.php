@php
    $appName = config('app.name', 'Travelo');
    $clientUrl = rtrim((string) config('project.client_url', config('app.url', url('/'))), '/');
@endphp

<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap');

        * {
            font-size: 20px;
            color: #000;
            font-weight: 500;
            line-height: 1.35;
            letter-spacing: 0.012em;
            font-family: 'Manrope', Arial, sans-serif;
        }

        small,
        .fs-sm {
            font-size: 12px;
        }

        .fs-md {
            font-size: 14px;
        }

        h6 {
            margin-top: 0;
            margin-bottom: 0.5rem;
        }

        h5,
        .fs-h5 {
            font-size: 18px;
            line-height: 25px;
        }

        h4,
        .fs-h4 {
            font-size: 24px;
            line-height: 33px;
        }

        h3,
        .fs-h3 {
            font-size: 30px;
            line-height: 41px;
        }

        h2,
        .fs-h2 {
            font-size: 36px;
            line-height: 49px;
        }

        .fs-lg {
            font-size: 20px;
            line-height: 27px;
        }

        .fs-xl {
            font-size: 24px;
            line-height: 33px;
            letter-spacing: 0.015em;
        }

        .text-primary {
            color: #0A438B;
        }

        .text-secondary {
            color: #444;
        }

        .text-white {
            color: #ffffff;
        }

        .text-black {
            color: #000;
        }

        .text-uppercase {
            text-transform: uppercase
        }

        .text-nowrap {
            white-space: nowrap;
        }

        .text-underline {
            text-decoration: underline;
        }

        .text-center {
            text-align: center
        }

        .bg-white {
            background-color: #ffffff;
        }

        .bg-primary {
            background-color: #0A438B;
        }

        .bg-primary-50 {
            background-color: rgba(222, 239, 255, 0.5);
        }

        .bg-primary-60 {
            background-color: rgba(223, 239, 255, 0.6);
        }

        .bg-secondary {
            background-color: #F4F5F9;
        }

        .fw-bold {
            font-weight: 700;
        }

        .fw-semibold {
            font-weight: 600;
        }

        .fw-medium {
            font-weight: 500;
        }

        .w-100 {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table tr {
            vertical-align: middle;
        }

        table th,
        table td {
            padding: 6px;
        }

        .table-info th,
        .table-info td {
            padding: 8px;
            font-size: 14px;
            border-bottom: 1px solid #B0B0B0;
        }

        .bordered {
            border: 1px solid #D3D3D3;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #D3D3D3;
        }

        a {
            text-decoration: none;
        }

        .pb-4 {
            padding-bottom: 24px;
        }

        .p-4 {
            padding: 24px;
        }

        .p-0 {
            padding: 0 !important;
        }

        .my-0 {
            margin-top: 0;
            margin-bottom: 0;
        }

        .mt-1 {
            margin-top: 2px;
        }

        .mt-2 {
            margin-top: 4px;
        }

        .mb-4 {
            margin-bottom: 24px;
        }

        .mb-3 {
            margin-bottom: 16px;
        }

        .rounded-circle {
            border-radius: 50%;
        }

        .d-none {
            display: none;
        }

        .fs-h2 span,
        .fs-h3 span,
        .fs-h4 span,
        .fs-h5 span,
        .fs-xl span,
        .fs-lg span {
            font-size: inherit;
            line-height: inherit;
        }
    </style>
</head>

<body style="font-family: 'Manrope', Arial, sans-serif; background-color:#f8f8f8; margin: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" border="0" style="padding: 30px 6px;">
                <table class="bg-white" style="width:600px; border: 1px solid #ededed;" cellpadding="0"
                    cellspacing="0" border="0">
                    <tr>
                        <td class="bg-primary" align="center" style="padding: 24px;">
                            <img src="{{ url('images/mails/logo-white.png') }}" alt="Logo" width="103px"
                                style="vertical-align: middle;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background: #F4F5F9; padding: 24px;">
                            <div class="fw-bold text-black" style="font-size: 20px; margin-bottom: 16px;">
                                Travel smarter. Explore further. Experience more.</div>
                            <div style="margin-bottom: 16px;">
                                <a href="{{ $clientUrl }}/en" target="__blank" class="fw-bold text-black"
                                    style="color: #000 !important; font-size: 16px; margin: 0 8px;">Website</a>
                                <a href="{{ $clientUrl }}/en/blogs" target="__blank" class="fw-bold text-black"
                                    style="color: #000 !important; font-size: 16px; margin: 0 8px;">Blog</a>
                                <a href="{{ $clientUrl }}/en/faqs" target="__blank" class="fw-bold text-black"
                                    style="color: #000 !important; font-size: 16px; margin: 0 8px;">FAQs</a>
                                <a href="{{ $clientUrl }}/en/contact" target="__blank" class="fw-bold text-black"
                                    style="color: #000 !important; font-size: 16px; margin: 0 8px;">Contact Us</a>
                            </div>
                            <div style="color: #444444; font-size: 16px;">
                                Copyright © {{ date('Y') }} {{ $appName }}. All right reserved.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
