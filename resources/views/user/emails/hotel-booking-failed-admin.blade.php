@php
    $brand = config('app.name', 'Travel');
    $vendorEmail = optional($booking->vendor)->email ?? $booking->lead_email;
    $footerExtra = '<strong>' . e($brand) . '</strong> - internal notification.';
    $companyCurrency = $companyCurrency ?? companyCurrency();
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>[Admin] Hotel booking failed - {{ $booking->booking_number }}</title>
    @include('user.emails.partials.booking-email-styles')
</head>

<body>
    @include('user.emails.partials.booking-email-shell-open')
    @include('user.emails.partials.email-banner-logo')

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <p class="muted" style="font-size:12px;font-weight:700;color:#999999;text-transform:uppercase;margin:0 0 8px;">
                    Admin
                </p>
                <h1>Hotel booking failed</h1>
                <p class="data-text" style="margin:8px 0 0;">
                    {{ $booking->booking_number }}
                    <span class="muted" style="font-size:13px;"> - supplier {{ strtoupper((string) ($booking->supplier ?? '')) }}</span>
                </p>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
        <tr>
            <td class="banner-warn">
                {{ $reason !== '' ? e($reason) : 'Failure reason not specified.' }}
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <table width="100%" cellpadding="8" cellspacing="0">
                    <tr>
                        <td style="padding:6px 0;">
                            <div class="label">Hotel</div>
                            <div class="data-text">{{ $booking->hotel_name }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;">
                            <div class="label">Vendor / lead email</div>
                            <div class="data-text" style="word-break:break-word;">{{ $vendorEmail }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;">
                            <div class="label">Lead guest</div>
                            <div class="data-text">{{ $booking->lead_full_name }} - {{ $booking->lead_phone }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;">
                            <div class="label">Stay</div>
                            <div class="data-text">{{ $booking->check_in_date->format('Y-m-d') }} to
                                {{ $booking->check_out_date->format('Y-m-d') }} ({{ $booking->nights }} nights)</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;">
                            <div class="label">Payment</div>
                            <div class="data-text">{{ $booking->payment_method }} - {{ $booking->payment_status }} -
                                {{ $companyCurrency }} {{ number_format((float) $booking->total_amount, 2) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;">
                            <div class="label">Booking status</div>
                            <div class="data-text">{{ $booking->booking_status }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (!empty($booking->booking_response))
        <table width="100%" cellpadding="0" cellspacing="0" class="section-padding">
            <tr>
                <td>
                    <h2 style="font-size:12px;text-transform:uppercase;color:#999999;margin:0 0 10px;">Last supplier response (booking)</h2>
                    <pre class="json-block">@json($booking->booking_response)</pre>
                </td>
            </tr>
        </table>
    @endif

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>

</html>
