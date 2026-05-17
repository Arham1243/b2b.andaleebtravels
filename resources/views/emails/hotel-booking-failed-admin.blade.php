@php
    $brand = config('app.name', 'Travel');
    $vendorEmail = optional($booking->vendor)->email ?? $booking->lead_email;
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>[Admin] Hotel booking failed — {{ $booking->booking_number }}</title>
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f3f5fb; font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #1a2540; }
        .banner { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 14px 16px; border-radius: 6px; margin-bottom: 16px; line-height: 1.45; }
        .kv { margin: 6px 0; }
        .k { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .v { font-weight: 600; margin-top: 2px; word-break: break-word; }
        pre { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 11px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f3f5fb">
    <tr>
        <td align="center" style="padding: 28px 12px;">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="max-width:640px;width:100%;border:1px solid #e2e8f0;border-radius:10px;">
                <tr>
                    <td style="padding:20px 22px;border-bottom:1px solid #e2e8f0;">
                        <strong style="font-size:16px;">{{ $brand }} — Hotel booking failed</strong>
                        <div style="color:#64748b;font-size:13px;margin-top:6px;">{{ $booking->booking_number }} · supplier {{ strtoupper((string) ($booking->supplier ?? '')) }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px;">
                        <div class="banner">{{ $reason !== '' ? e($reason) : 'Failure reason not specified.' }}</div>

                        <div class="kv"><div class="k">Hotel</div><div class="v">{{ $booking->hotel_name }}</div></div>
                        <div class="kv"><div class="k">Vendor / lead email</div><div class="v">{{ $vendorEmail }}</div></div>
                        <div class="kv"><div class="k">Lead guest</div><div class="v">{{ $booking->lead_full_name }} · {{ $booking->lead_phone }}</div></div>
                        <div class="kv"><div class="k">Stay</div><div class="v">{{ $booking->check_in_date->format('Y-m-d') }} → {{ $booking->check_out_date->format('Y-m-d') }} ({{ $booking->nights }} nights)</div></div>
                        <div class="kv"><div class="k">Payment</div><div class="v">{{ $booking->payment_method }} · {{ $booking->payment_status }} · {{ $booking->currency }} {{ number_format((float) $booking->total_amount, 2) }}</div></div>
                        <div class="kv"><div class="k">Booking status</div><div class="v">{{ $booking->booking_status }}</div></div>

                        @if (!empty($booking->booking_response))
                            <p style="margin:18px 0 8px;font-weight:700;font-size:12px;color:#64748b;text-transform:uppercase;">Last supplier response (booking)</p>
                            <pre>@json($booking->booking_response)</pre>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
