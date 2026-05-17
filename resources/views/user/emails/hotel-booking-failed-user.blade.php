@php
    $brand = config('app.name', 'Travel');
    $footerExtra = 'Reference <strong>' . e($booking->booking_number) . '</strong>.';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hotel booking issue - {{ $booking->booking_number }}</title>
    @include('user.emails.partials.booking-email-styles')
</head>

<body>
    @include('user.emails.partials.booking-email-shell-open')
    @include('user.emails.partials.email-banner-logo')

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td align="center">
                <p class="muted" style="font-size:12px;font-weight:700;color:#999999;text-transform:uppercase;margin:0 0 8px;">
                    {{ $brand }} - B2B
                </p>
                <h1>Hotel booking could not be completed</h1>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:12px 0 8px;">
        <tr>
            <td align="center">
                <span class="status-badge-fail">Not confirmed</span>
            </td>
        </tr>
    </table>

    <p class="muted">Dear {{ $booking->lead_full_name }},</p>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
        <tr>
            <td class="banner-danger">
                {{ $reason !== '' ? e($reason) : 'We were unable to complete your hotel reservation with the supplier. Our team has been notified. If you believe payment was taken without confirmation, please contact support with your booking reference below.' }}
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <h2>Booking summary</h2>
                <table width="100%" cellpadding="8" cellspacing="0">
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Booking reference</div>
                            <div class="data-text">{{ $booking->booking_number }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Hotel</div>
                            <div class="data-text">{{ $booking->hotel_name }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Check-in</div>
                            <div class="data-text">{{ $booking->check_in_date->format('d M Y') }}</div>
                        </td>
                        <td valign="top" class="mob-full" style="padding:8px 0;">
                            <div class="label">Check-out</div>
                            <div class="data-text">{{ $booking->check_out_date->format('d M Y') }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="muted" style="margin-top:8px;">Please search again or contact your account manager if this keeps happening.</p>

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>

</html>
