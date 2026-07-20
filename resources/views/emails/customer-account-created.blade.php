<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your account is ready</title>
</head>

<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:32px 32px 8px;">
                            <h1 style="margin:0 0 16px;font-size:20px;">Welcome, {{ $user->name }}</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
                                An account has been created for you so you can view and manage your booking.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#f4f5f7;border-radius:6px;margin:0 0 16px;">
                                <tr>
                                    <td style="padding:16px;font-size:14px;line-height:1.8;">
                                        <strong>Email:</strong> {{ $user->email }}<br>
                                        <strong>Password:</strong> <code style="font-size:14px;">{{ $plainPassword }}</code>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#52606d;">
                                For your security, please sign in and change this password as soon as you can.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>