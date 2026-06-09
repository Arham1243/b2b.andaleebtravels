@php
    $lead = $booking->passengers_data['lead'] ?? [];
    $paxList = $booking->passengers_data['passengers'] ?? [];
    $fullName = trim(
        ($lead['title'] ?? '') . ' ' . ($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? '')
    );
    $payMethod = strtolower((string) ($booking->payment_method ?? ''));
    $payLabel = match ($payMethod) {
        'payby' => 'Card (PayBy)',
        'tabby' => 'Tabby',
        'tamara' => 'Tamara',
        'wallet' => 'Wallet',
        'hold' => 'Hold',
        default => $payMethod !== '' ? ucfirst($payMethod) : '-',
    };
    $routeStr = strtoupper((string) ($booking->from_airport ?? ''))
        . (($booking->return_date ?? null) ? ' - ' : ' to ')
        . strtoupper((string) ($booking->to_airport ?? ''));
    $detailUrl = $detailUrl ?? route('user.bookings.flights.detail', $booking->id);
    $brand = config('app.name', 'Travel');
    $forAdmin = $forAdmin ?? false;
    $footerExtra =
        'This message was generated automatically regarding booking <strong>' .
        e($booking->booking_number) .
        '</strong>.';
    $companyCurrency = $companyCurrency ?? companyCurrency();
    $ticketNumbers = $booking->resolvedTicketNumbers();
@endphp
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $forAdmin ? 'Admin: ' : '' }}Flight confirmed - {{ $booking->booking_number }}</title>
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
                <h1>Flight booking confirmed</h1>
            </td>
        </tr>
    </table>

    @if ($forAdmin)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
            <tr>
                <td class="banner-admin">
                    Internal notice: confirmed flight booking issued on the portal. Summary below for your records.
                </td>
            </tr>
        </table>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:12px 0 8px;">
        <tr>
            <td align="center">
                <span class="status-badge">Ticket issued</span>
            </td>
        </tr>
    </table>

    <p class="muted" style="margin-bottom:12px;">{{ $intro }}</p>
    @if (!$forAdmin && $fullName !== '')
        <p class="muted" style="margin-bottom:0;">Dear {{ trim($fullName) }},</p>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <h2>Reservation details</h2>
                <table width="100%" cellpadding="8" cellspacing="0">
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Booking reference</div>
                            <div class="data-text">{{ $booking->booking_number }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            @if ($booking->sabre_record_locator)
                                <div class="label">PNR ({{ $booking->providerLabel() }})</div>
                                <div class="data-text conf-ref">{{ $booking->sabre_record_locator }}</div>
                            @else
                                <div class="label">PNR</div>
                                <div class="data-text" style="color:#888888;font-weight:500;">Pending / not available</div>
                            @endif
                        </td>
                    </tr>
                    @if (count($ticketNumbers) > 0)
                    <tr>
                        <td colspan="2" style="padding:8px 0;">
                            <div class="label">Ticket number{{ count($ticketNumbers) > 1 ? 's' : '' }}</div>
                            <div class="data-text conf-ref">{{ implode(', ', $ticketNumbers) }}</div>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2" style="padding:8px 0;">
                            <div class="label">Route</div>
                            <div class="data-text">{{ $routeStr }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Departure</div>
                            <div class="data-text">{{ $booking->departure_date?->format('l, d M Y') ?? '-' }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Return</div>
                            <div class="data-text">{{ $booking->return_date?->format('l, d M Y') ?? '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Travellers</div>
                            <div class="data-text">
                                {{ (int) $booking->adults }} Adult{{ (int) $booking->adults !== 1 ? 's' : '' }}
                                @if ((int) $booking->children > 0)
                                    , {{ (int) $booking->children }}
                                    Child{{ (int) $booking->children !== 1 ? 'ren' : '' }}
                                @endif
                                @if ((int) $booking->infants > 0)
                                    , {{ (int) $booking->infants }}
                                    Infant{{ (int) $booking->infants !== 1 ? 's' : '' }}
                                @endif
                            </div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Payment</div>
                            <div class="data-text">{{ $payLabel }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Total paid</div>
                            <div class="data-text grand-total-text">{{ $companyCurrency }}
                                {{ number_format((float) $booking->total_amount, 2) }}</div>
                        </td>
                        @if ((float) ($booking->wallet_amount ?? 0) > 0.001)
                            <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                <div class="label">Wallet applied</div>
                                <div class="data-text">{{ $companyCurrency }}
                                    {{ number_format((float) $booking->wallet_amount, 2) }}</div>
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <h2>Contact on record</h2>
                <table width="100%" cellpadding="8" cellspacing="0">
                    @if (!empty($lead['phone']))
                        <tr>
                            <td style="padding:6px 0;">
                                <div class="label">Phone</div>
                                <div class="data-text" style="font-weight:500;">{{ $lead['phone'] }}</div>
                            </td>
                        </tr>
                    @endif
                    @if (!empty($leadEmail))
                        <tr>
                            <td style="padding:6px 0;">
                                <div class="label">Email</div>
                                <div class="data-text" style="font-weight:500;">{{ $leadEmail }}</div>
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if (count($paxList) > 0)
        <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
            <tr>
                <td>
                    <h2>Passenger names</h2>
                    <table width="100%" cellpadding="0" cellspacing="0" class="booking-card">
                        @foreach ($paxList as $p)
                            <tr>
                                <td style="padding:12px 14px;@if (!$loop->last) border-bottom:1px solid #eeeeee; @endif">
                                    <span style="font-size:11px;color:#999999;text-transform:uppercase;font-weight:700;">
                                        @if (($p['type'] ?? 'ADT') === 'C06')
                                            Child
                                        @elseif (($p['type'] ?? 'ADT') === 'INF')
                                            Infant
                                        @else
                                            Adult
                                        @endif
                                    </span>
                                    <div class="data-text" style="margin-top:4px;">
                                        {{ strtoupper(trim(($p['title'] ?? '') . ' ' . ($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:8px 0 24px;">
                <a href="{{ $detailUrl }}" class="btn-view">View booking</a>
                <p class="muted" style="margin:14px 0 0;font-size:12px;text-align:center;">
                    Use your B2B login to manage this booking, download documents where available, or contact support from the portal.
                </p>
            </td>
        </tr>
    </table>

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>

</html>
