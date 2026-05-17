@php
    $brand = config('app.name', 'Travel');
    $forAdmin = $forAdmin ?? false;
    $detailUrl = $detailUrl ?? route('user.bookings.hotels.detail', $booking->id);
    $payMethod = strtolower((string) ($booking->payment_method ?? ''));
    $payLabel = match ($payMethod) {
        'payby' => 'Card (PayBy)',
        'tabby' => 'Tabby',
        'tamara' => 'Tamara',
        'wallet' => 'Wallet',
        default => $payMethod !== '' ? ucfirst($payMethod) : '-',
    };
    $confRef = $booking->yalago_booking_reference
        ?: data_get($booking->booking_response, 'BookingReferenceId')
        ?: data_get($booking->booking_response, 'BookingRef')
        ?: data_get($booking->booking_response, 'ConfirmationNumber')
        ?: data_get($booking->booking_response, 'BookingId');
    $supplierLabel = strtoupper((string) ($booking->supplier ?? 'YALAGO'));
    $footerExtra = 'Booking <strong>' . e($booking->booking_number) . '</strong>.';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $forAdmin ? 'Admin: ' : '' }}Hotel confirmed - {{ $booking->booking_number }}</title>
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
                <h1>Hotel booking confirmed</h1>
            </td>
        </tr>
    </table>

    @if ($forAdmin)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
            <tr>
                <td class="banner-admin">
                    Internal notice: hotel reservation confirmed on the portal (supplier {{ $supplierLabel }}). Summary below.
                </td>
            </tr>
        </table>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:12px 0 8px;">
        <tr>
            <td align="center">
                <span class="status-badge">Confirmed</span>
            </td>
        </tr>
    </table>

    <p class="muted" style="margin-bottom:12px;">{{ $intro }}</p>
    @if (!$forAdmin)
        <p class="muted" style="margin-bottom:0;">Dear {{ $booking->lead_full_name }},</p>
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
                            @if ($confRef)
                                <div class="label">Supplier confirmation</div>
                                <div class="data-text conf-ref">{{ $confRef }}</div>
                            @else
                                <div class="label">Supplier confirmation</div>
                                <div class="data-text" style="color:#888888;font-weight:500;">Pending / not in response</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding:8px 0;">
                            <div class="label">Hotel</div>
                            <div class="data-text">{{ $booking->hotel_name }}</div>
                        </td>
                    </tr>
                    @if ($booking->hotel_address)
                        <tr>
                            <td colspan="2" style="padding:8px 0;">
                                <div class="label">Address</div>
                                <div class="data-text" style="font-weight:500;">{{ $booking->hotel_address }}</div>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Check-in</div>
                            <div class="data-text">{{ $booking->check_in_date->format('l, d M Y') }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Check-out</div>
                            <div class="data-text">{{ $booking->check_out_date->format('l, d M Y') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Nights</div>
                            <div class="data-text">{{ $booking->nights }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Supplier</div>
                            <div class="data-text">{{ $supplierLabel }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Lead guest</div>
                            <div class="data-text">{{ $booking->lead_full_name }}</div>
                        </td>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                            <div class="label">Payment</div>
                            <div class="data-text">{{ $payLabel }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                            <div class="label">Total</div>
                            <div class="data-text grand-total-text">{{ $booking->currency }}
                                {{ number_format((float) $booking->total_amount, 2) }}</div>
                        </td>
                        @if ((float) ($booking->wallet_amount ?? 0) > 0.001)
                            <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                <div class="label">Wallet applied</div>
                                <div class="data-text">{{ $booking->currency }}
                                    {{ number_format((float) $booking->wallet_amount, 2) }}</div>
                            </td>
                        @endif
                    </tr>
                    @if ($forAdmin && optional($booking->vendor)->email)
                        <tr>
                            <td colspan="2" style="padding:8px 0;">
                                <div class="label">Agent account</div>
                                <div class="data-text">{{ $booking->vendor->email }}</div>
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if (!empty($booking->selected_rooms) && is_iterable($booking->selected_rooms))
        <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
            <tr>
                <td>
                    <h2>Room details</h2>
                    @foreach ($booking->selected_rooms as $room)
                        <table width="100%" cellpadding="8" cellspacing="0" class="booking-card" style="margin-bottom:10px;">
                            <tr>
                                <td>
                                    <div class="label">Room type</div>
                                    <div class="data-text">{{ $room['room_name'] ?? ($room['name'] ?? 'N/A') }}</div>
                                </td>
                            </tr>
                            @if (!empty($room['board_title']) || !empty($room['board']))
                                <tr>
                                    <td>
                                        <div class="label">Board type</div>
                                        <div class="data-text">{{ $room['board_title'] ?? ($room['board'] ?? 'N/A') }}</div>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    @if (!$forAdmin)
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center" style="padding:8px 0 24px;">
                    <a href="{{ $detailUrl }}" class="btn-view">View booking</a>
                    <p class="muted" style="margin:14px 0 0;font-size:12px;text-align:center;">Manage this reservation from your B2B portal.</p>
                </td>
            </tr>
        </table>
    @endif

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>

</html>
