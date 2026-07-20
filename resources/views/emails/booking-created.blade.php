<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your booking is confirmed</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:32px 32px 8px;">
                            <h1 style="margin:0 0 16px;font-size:20px;">Booking confirmed, {{ $booking->name }}</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
                                Thank you for your booking. Here are the details:
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#f4f5f7;border-radius:6px;margin:0 0 16px;">
                                <tr>
                                    <td style="padding:16px;font-size:14px;line-height:1.8;">
                                        <strong>Booking code:</strong> {{ $booking->order_code }}<br>
                                        @if ($booking->detail)
                                            <strong>Departure:</strong> {{ $booking->detail->departure_date?->format('Y-m-d') }}<br>
                                            <strong>Travellers:</strong>
                                            {{ $booking->detail->adult_quantity }} adult(s){{ $booking->detail->child_quantity ? ', ' . $booking->detail->child_quantity . ' child(ren)' : '' }}{{ $booking->detail->infant_quantity ? ', ' . $booking->detail->infant_quantity . ' infant(s)' : '' }}<br>
                                        @endif
                                        <strong>Total:</strong> {{ number_format((float) $booking->input_total, 2) }} {{ $booking->input_currency }}
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#52606d;">
                                We will contact you at {{ $booking->email }} with any updates.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
