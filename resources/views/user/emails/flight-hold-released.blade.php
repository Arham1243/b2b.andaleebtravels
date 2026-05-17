@php
    $brand = config('app.name', 'Travel');
    $forAdmin = $forAdmin ?? false;
    $detailUrl = $detailUrl ?? route('user.bookings.flights.detail', $booking->id);
    $lead = $booking->passengers_data['lead'] ?? [];
    $cancelMeta = is_array($booking->cancel_response ?? null) ? $booking->cancel_response : [];
    $cancelType = $cancelMeta['cancellation_type'] ?? null;
    $routeLabel = strtoupper(trim(($booking->from_airport ?? '') . ' → ' . ($booking->to_airport ?? '')));
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $forAdmin ? 'Admin: ' : '' }}Hold released - {{ $booking->booking_number }}</title>
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
                <h1>Flight hold released</h1>
            </td>
        </tr>
    </table>

    @if ($forAdmin)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
            <tr>
                <td class="banner-admin">
                    Internal notice: an unpaid Sabre hold was released / cleared on the portal.
                    @if($cancelType)
                        <br/><span style="font-size:12px;opacity:.9;">Type: {{ e($cancelType) }}</span>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:12px 0 8px;">
        <tr>
            <td align="center">
                <span class="status-badge-fail">Hold released</span>
            </td>
        </tr>
    </table>

    @if (!$forAdmin)
        <p class="muted" style="margin-bottom:12px;">Dear {{ trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? '')) ?: 'Customer' }},</p>
        <p class="muted">Your unpaid flight hold has been released. No ticketing occurred; any reserved space at the airline may have been cancelled.</p>
    @else
        <p class="muted">Hold released for the booking below.</p>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <h2>Itinerary</h2>
                <table width="100%" cellpadding="8" cellspacing="0">
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Booking reference</div>
                            <div class="data-text">{{ $booking->booking_number }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Route</div>
                            <div class="data-text">{{ $routeLabel ?: '—' }}</div>
                        </td>
                    </tr>
                    @if($booking->sabre_record_locator)
                    <tr>
                        <td valign="top" colspan="2" style="padding:8px 0;">
                            <div class="label">PNR</div>
                            <div class="data-text conf-ref">{{ $booking->sabre_record_locator }}</div>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td valign="top" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Released at</div>
                            <div class="data-text">{{ formatBookingCancelledAt($booking->cancelled_at) ?? '—' }}</div>
                        </td>
                        <td valign="top" class="mob-full" style="padding:8px 0;">
                            <div class="label">Recorded by</div>
                            <div class="data-text">{{ formatBookingCancelledByLabel($booking->cancelled_by) }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(!$forAdmin)
        <p class="muted" style="margin-top:14px;"><a href="{{ $detailUrl }}">View booking details</a> in your dashboard.</p>
    @else
        <p class="muted" style="margin-top:14px;"><a href="{{ $detailUrl }}">Open booking</a> (vendor view).</p>
    @endif

    @include('user.emails.partials.booking-email-footer')
</body>
</html>
