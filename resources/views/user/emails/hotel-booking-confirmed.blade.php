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
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $forAdmin ? 'Admin: ' : '' }}Hotel confirmed - {{ $booking->booking_number }}</title>
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f3f5fb; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased; }
        table { border-collapse: collapse; }
        h2 { font-size: 15px; font-weight: 700; color: #1a2540; margin: 0 0 14px 0; letter-spacing: 0.02em; }
        .muted { font-size: 14px; color: #64748b; line-height: 1.55; margin: 0; }
        .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #8492a6; }
        .value { font-size: 14px; color: #1a2540; font-weight: 600; padding-top: 4px; }
        .conf-ref { font-family: ui-monospace, Consolas, monospace; font-weight: 700; color: #cd1b4f; font-size: 15px; }
        .badge { display: inline-block; background-color: #e8f9f1; border: 1px solid #0f9d58; color: #0f9d58; padding: 5px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; border-radius: 4px; }
        .banner { font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 12px 14px; line-height: 1.45; }
        .btn { display: inline-block; background: linear-gradient(180deg, #cd1b4f 0%, #a8173f 100%); color: #ffffff !important; text-decoration: none; padding: 12px 24px; font-size: 14px; font-weight: 700; border-radius: 8px; }
        .divider { border-top: 1px solid #e2e8f0; }
        .footer { font-size: 12px; color: #64748b; text-align: center; line-height: 1.5; }
        @media only screen and (max-width: 600px) {
            .mob-full { width: 100% !important; display: block !important; }
        }
    </style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f3f5fb" style="background-color:#f3f5fb;">
    <tr>
        <td align="center" style="padding: 32px 16px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:22px 24px 18px;background:linear-gradient(135deg,#1a2540 0%,#2d3b5f 100%);">
                        <p style="margin:0;font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.12em;">{{ $brand }} - B2B</p>
                        <p style="margin:6px 0 0;font-size:17px;font-weight:700;color:#ffffff;">Hotel booking confirmed</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 24px 24px 8px;">
                        @if ($forAdmin)
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;"><tr><td class="banner">
                                Internal notice: hotel reservation confirmed on the portal (supplier {{ $supplierLabel }}). Summary below.
                            </td></tr></table>
                        @endif
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;"><tr><td>
                            <span class="badge">Confirmed</span>
                        </td></tr></table>
                        <p class="muted" style="margin-bottom:14px;">{{ $intro }}</p>
                        @if (!$forAdmin)
                            <p class="muted" style="margin-bottom:0;">Dear {{ $booking->lead_full_name }},</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 24px 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0" class="divider" style="padding-top:4px;"></table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 24px 22px;">
                        <h2>Reservation details</h2>
                        <table width="100%" cellpadding="10" cellspacing="0">
                            <tr>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                                    <div class="label">Booking reference</div>
                                    <div class="value">{{ $booking->booking_number }}</div>
                                </td>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                    @if ($confRef)
                                        <div class="label">Supplier confirmation</div>
                                        <div class="value conf-ref">{{ $confRef }}</div>
                                    @else
                                        <div class="label">Supplier confirmation</div>
                                        <div class="value" style="color:#64748b;font-weight:500;">Pending / not in response</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding:8px 0;">
                                    <div class="label">Hotel</div>
                                    <div class="value">{{ $booking->hotel_name }}</div>
                                </td>
                            </tr>
                            @if ($booking->hotel_address)
                                <tr>
                                    <td colspan="2" style="padding:8px 0;">
                                        <div class="label">Address</div>
                                        <div class="value" style="font-weight:500;">{{ $booking->hotel_address }}</div>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                                    <div class="label">Check-in</div>
                                    <div class="value">{{ $booking->check_in_date->format('l, d M Y') }}</div>
                                </td>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                    <div class="label">Check-out</div>
                                    <div class="value">{{ $booking->check_out_date->format('l, d M Y') }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                                    <div class="label">Nights</div>
                                    <div class="value">{{ $booking->nights }}</div>
                                </td>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                    <div class="label">Supplier</div>
                                    <div class="value">{{ $supplierLabel }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                                    <div class="label">Lead guest</div>
                                    <div class="value">{{ $booking->lead_full_name }}</div>
                                </td>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                    <div class="label">Payment</div>
                                    <div class="value">{{ $payLabel }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" width="50%" class="mob-full" style="padding:8px 8px 8px 0;">
                                    <div class="label">Total</div>
                                    <div class="value">{{ $booking->currency }} {{ number_format((float) $booking->total_amount, 2) }}</div>
                                </td>
                                @if ((float) ($booking->wallet_amount ?? 0) > 0.001)
                                    <td valign="top" width="50%" class="mob-full" style="padding:8px 0;">
                                        <div class="label">Wallet applied</div>
                                        <div class="value">{{ $booking->currency }} {{ number_format((float) $booking->wallet_amount, 2) }}</div>
                                    </td>
                                @endif
                            </tr>
                            @if ($forAdmin && optional($booking->vendor)->email)
                                <tr>
                                    <td colspan="2" style="padding:8px 0;">
                                        <div class="label">Agent account</div>
                                        <div class="value">{{ $booking->vendor->email }}</div>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
                @if (!$forAdmin)
                <tr>
                    <td style="padding: 8px 24px 28px;" align="center">
                        <a href="{{ $detailUrl }}" class="btn">View booking</a>
                        <p class="muted" style="margin:16px 0 0;font-size:12px;">Manage this reservation from your B2B portal.</p>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="divider" style="padding:18px 24px 28px;background-color:#fafbfc;">
                        <p class="footer">
                            &copy; {{ date('Y') }} {{ $brand }}. Booking <strong>{{ $booking->booking_number }}</strong>.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
