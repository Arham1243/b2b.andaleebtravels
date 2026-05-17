@php
    $brand = config('app.name', 'Travel');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hotel booking issue — {{ $booking->booking_number }}</title>
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f3f5fb; font-family: Arial, Helvetica, sans-serif; }
        h1 { font-size: 20px; font-weight: 700; color: #1a2540; margin: 0 0 12px 0; }
        .muted { font-size: 14px; color: #64748b; line-height: 1.55; margin: 0 0 12px 0; }
        .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #8492a6; }
        .value { font-size: 14px; color: #1a2540; font-weight: 600; padding-top: 4px; }
        .banner { font-size: 13px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 14px 16px; line-height: 1.45; }
        .footer { font-size: 12px; color: #64748b; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f3f5fb">
    <tr>
        <td align="center" style="padding: 32px 16px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
                <tr>
                    <td style="padding:22px 24px;background:linear-gradient(135deg,#1a2540 0%,#2d3b5f 100%);">
                        <p style="margin:0;font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.12em;">{{ $brand }} — B2B</p>
                        <p style="margin:8px 0 0;font-size:17px;font-weight:700;color:#ffffff;">Hotel booking could not be completed</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 24px;">
                        <p class="muted">Dear {{ $booking->lead_full_name }},</p>
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;"><tr><td class="banner">
                            {{ $reason !== '' ? e($reason) : 'We were unable to complete your hotel reservation with the supplier. Our team has been notified. If you believe payment was taken without confirmation, please contact support with your booking reference below.' }}
                        </td></tr></table>
                        <table width="100%" cellpadding="10" cellspacing="0">
                            <tr>
                                <td width="50%" valign="top" style="padding:8px 8px 8px 0;">
                                    <div class="label">Booking reference</div>
                                    <div class="value">{{ $booking->booking_number }}</div>
                                </td>
                                <td width="50%" valign="top" style="padding:8px 0;">
                                    <div class="label">Hotel</div>
                                    <div class="value">{{ $booking->hotel_name }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" style="padding:8px 8px 8px 0;">
                                    <div class="label">Check-in</div>
                                    <div class="value">{{ $booking->check_in_date->format('d M Y') }}</div>
                                </td>
                                <td valign="top" style="padding:8px 0;">
                                    <div class="label">Check-out</div>
                                    <div class="value">{{ $booking->check_out_date->format('d M Y') }}</div>
                                </td>
                            </tr>
                        </table>
                        <p class="muted" style="margin-top:20px;">Please search again or contact your account manager if this keeps happening.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 24px 28px;background-color:#fafbfc;border-top:1px solid #e2e8f0;">
                        <p class="footer">&copy; {{ date('Y') }} {{ $brand }}. Reference <strong>{{ $booking->booking_number }}</strong></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
