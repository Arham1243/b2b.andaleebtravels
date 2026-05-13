<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight booking {{ $booking->booking_number }}</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, sans-serif; line-height: 1.5; color: #1a2540; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p style="font-size: 14px; color: #4a5568;">{{ $intro }}</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14px;">
        <tr><td style="padding: 8px 0; color: #8492a6; width: 40%;">Booking #</td><td style="padding: 8px 0; font-weight: 700;">{{ $booking->booking_number }}</td></tr>
        @if($booking->sabre_record_locator)
        <tr><td style="padding: 8px 0; color: #8492a6;">PNR</td><td style="padding: 8px 0; font-family: monospace; font-weight: 700; color: #cd1b4f;">{{ $booking->sabre_record_locator }}</td></tr>
        @endif
        <tr><td style="padding: 8px 0; color: #8492a6;">Route</td><td style="padding: 8px 0;">{{ strtoupper($booking->from_airport ?? '') }} → {{ strtoupper($booking->to_airport ?? '') }}</td></tr>
        <tr><td style="padding: 8px 0; color: #8492a6;">Departure</td><td style="padding: 8px 0;">{{ $booking->departure_date?->format('d M Y') ?? '—' }}</td></tr>
        @if($booking->return_date)
        <tr><td style="padding: 8px 0; color: #8492a6;">Return</td><td style="padding: 8px 0;">{{ $booking->return_date->format('d M Y') }}</td></tr>
        @endif
        <tr><td style="padding: 8px 0; color: #8492a6;">Total</td><td style="padding: 8px 0; font-weight: 700;">{{ $booking->currency }} {{ number_format((float) $booking->total_amount, 2) }}</td></tr>
        @if(!empty($leadEmail))
        <tr><td style="padding: 8px 0; color: #8492a6;">Lead email</td><td style="padding: 8px 0;">{{ $leadEmail }}</td></tr>
        @endif
    </table>

    <p style="font-size: 12px; color: #8492a6; margin-top: 24px;">Andaleeb Travel Agency — B2B</p>
</body>
</html>
