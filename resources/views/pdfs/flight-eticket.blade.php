<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>E-Ticket {{ $eticket['booking']['ref'] ?? '' }}</title>
    <style>
        @page {
            margin: 16px 20px;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #cd1b4f;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header td {
            vertical-align: top;
        }

        .header-logo-cell {
            width: 58%;
        }

        .header-meta-cell {
            width: 42%;
        }

        .logo {
            max-width: 200px;
            max-height: 58px;
            margin-bottom: 8px;
        }

        .agency-legal {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .agency-meta {
            font-size: 11px;
            color: #1f2937;
            margin: 0 0 3px;
            line-height: 1.45;
        }

        .agency-meta strong {
            color: #111827;
        }

        .booking-meta {
            text-align: right;
            font-size: 11px;
            color: #1f2937;
        }

        .booking-meta-row {
            margin-bottom: 5px;
        }

        .booking-meta-row strong {
            color: #111827;
        }

        .status-pill {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            font-weight: bold;
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .direction-block {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        .direction-head {
            background: #e5e7eb;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 0.5px;
            color: #111827;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            text-transform: uppercase;
        }

        .direction-route {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            background: #fafafa;
        }

        .direction-route h2 {
            font-size: 14px;
            margin: 0 0 3px;
            color: #111827;
            font-weight: bold;
        }

        .direction-route .sub {
            font-size: 11px;
            color: #374151;
            margin: 0 0 8px;
            font-weight: 600;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 3px 10px 3px 0;
            font-size: 11px;
        }

        .info-grid .label {
            color: #374151;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .info-grid .value {
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }

        .flight-table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
            font-weight: bold;
            padding: 7px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        .flight-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 11px;
            color: #111827;
        }

        .flight-table tr:last-child td {
            border-bottom: none;
        }

        .flight-no {
            font-weight: bold;
            font-size: 12px;
            color: #111827;
        }

        .muted {
            color: #4b5563;
            font-size: 10px;
            margin-top: 2px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 14px 0 6px;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .traveler-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }

        .traveler-table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
            font-weight: bold;
            padding: 7px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        .traveler-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 11px;
            color: #111827;
        }

        .traveler-table tr:last-child td {
            border-bottom: none;
        }

        .barcode {
            width: 96px;
            height: auto;
        }

        .traveler-name {
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            font-size: 12px;
        }

        .traveler-table th.ticket-col,
        .traveler-table td.ticket-col {
            text-align: left;
            vertical-align: middle;
            white-space: nowrap;
        }

        .ticket-no {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-weight: bold;
            color: #111827;
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        .baggage-box {
            border: 1px solid #cbd5e1;
            border-top: none;
            padding: 10px;
            font-size: 11px;
            color: #1f2937;
            background: #fafafa;
        }

        .baggage-box .title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
            color: #111827;
            text-transform: uppercase;
        }

        .fare-box {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            padding: 10px;
            page-break-inside: avoid;
            background: #fafafa;
        }

        .fare-box h3 {
            margin: 0 0 8px;
            font-size: 12px;
            color: #cd1b4f;
            font-weight: bold;
            text-transform: uppercase;
        }

        .fare-row {
            width: 100%;
            border-collapse: collapse;
        }

        .fare-row td {
            padding: 3px 0;
            font-size: 11px;
            color: #1f2937;
        }

        .fare-row td:last-child {
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .notes {
            margin-top: 16px;
            padding: 10px;
            background: #fff7ed;
            border-left: 3px solid #f59e0b;
            font-size: 10px;
            color: #374151;
            page-break-inside: avoid;
        }

        .notes p {
            margin: 0 0 5px;
            line-height: 1.5;
        }

        .notes strong {
            color: #111827;
        }
    </style>
</head>

<body>
    @php
        $agency = $eticket['agency'] ?? [];
        $booking = $eticket['booking'] ?? [];
        $directions = $eticket['directions'] ?? [];
        $travelers = $eticket['travelers'] ?? [];
        $includeFare = ! empty($eticket['include_fare']);
        $notes = $eticket['notes'] ?? [];
    @endphp

    <table class="header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="header-logo-cell">
                @if (!empty($agency['logo_data_uri']))
                    <img src="{{ $agency['logo_data_uri'] }}" alt="Logo" class="logo">
                @endif
                @if (!empty($agency['legal_name']))
                    <p class="agency-legal">{{ $agency['legal_name'] }}</p>
                @elseif (!empty($agency['name']))
                    <p class="agency-legal">{{ $agency['name'] }}</p>
                @endif
                @if (!empty($agency['address']))
                    <p class="agency-meta"><strong>Address:</strong> {{ $agency['address'] }}</p>
                @endif
                @if (!empty($agency['phone']))
                    <p class="agency-meta"><strong>Tel:</strong> {{ $agency['phone'] }}</p>
                @endif
                @if (!empty($agency['email']))
                    <p class="agency-meta"><strong>Email:</strong> {{ $agency['email'] }}</p>
                @endif
            </td>
            <td class="header-meta-cell booking-meta">
                <div class="booking-meta-row">
                    Date of Booking: <strong>{{ $booking['date'] ?? '—' }}</strong>
                </div>
                <div class="booking-meta-row">
                    Status: <span class="status-pill">{{ $booking['status'] ?? 'CONFIRMED' }}</span>
                </div>
                @if (!empty($booking['pnr']))
                    <div class="booking-meta-row">Airline Ref: <strong>{{ $booking['airline_ref'] }}</strong></div>
                    <div class="booking-meta-row">CRS Ref: <strong>{{ $booking['crs_ref'] }}</strong></div>
                @endif
            </td>
        </tr>
    </table>

    @foreach ($directions as $direction)
        <div class="direction-block">
            <div class="direction-head">{{ $direction['label'] ?? 'FLIGHT' }}</div>
            <div class="direction-route">
                <h2>{{ $direction['route_title'] ?? '' }}</h2>
                <p class="sub">{{ $direction['meta_line'] ?? '' }}</p>
                <table class="info-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div class="label">Airline</div>
                            <div class="value">{{ $direction['airline'] ?? '—' }}</div>
                            <div class="label" style="margin-top:6px;">Travel Class</div>
                            <div class="value">{{ $direction['travel_class'] ?? 'Economy' }}</div>
                        </td>
                        <td>
                            <div class="label">Check-In Baggage</div>
                            <div class="value">{{ $direction['check_in_baggage'] ?? 'Refer to airline policy' }}</div>
                            <div class="label" style="margin-top:6px;">Cabin Baggage</div>
                            <div class="value">{{ $direction['cabin_baggage'] ?? 'Refer to airline policy' }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="flight-table">
                <thead>
                    <tr>
                        <th width="14%">Flight Number</th>
                        <th width="28%">From (Terminal)</th>
                        <th width="22%">Departure</th>
                        <th width="10%">Stops</th>
                        <th width="26%">To (Terminal) / Arrival</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($direction['segments'] ?? [] as $segment)
                        <tr>
                            <td>
                                <div class="flight-no">{{ $segment['flight_number'] ?? '—' }}</div>
                                @if (!empty($segment['operated_by']))
                                    <div class="muted">Operated by: {{ $segment['operated_by'] }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $segment['from_code'] ?? '' }}</strong>
                                @if (!empty($segment['from_airport']))
                                    <div class="muted">{{ $segment['from_airport'] }}</div>
                                @endif
                                @if (!empty($segment['from_country']))
                                    <div class="muted">{{ $segment['from_country'] }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $segment['departure_time'] ?? '—' }}</strong>
                                @if (!empty($segment['departure_date']))
                                    <div class="muted">{{ $segment['departure_date'] }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $segment['stops_label'] ?? 'Non Stop' }}</strong>
                                @if (!empty($segment['duration_label']))
                                    <div class="muted">({{ $segment['duration_label'] }})</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $segment['to_code'] ?? '' }}</strong>
                                @if (!empty($segment['to_airport']))
                                    <div class="muted">{{ $segment['to_airport'] }}</div>
                                @endif
                                @if (!empty($segment['to_country']))
                                    <div class="muted">{{ $segment['to_country'] }}</div>
                                @endif
                                <div style="margin-top:4px;"><strong>{{ $segment['arrival_time'] ?? '—' }}</strong></div>
                                @if (!empty($segment['arrival_date']))
                                    <div class="muted">{{ $segment['arrival_date'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="section-title">Traveler(s) Information</div>
            <div class="direction-head">{{ $direction['label'] ?? '' }}</div>
            <table class="traveler-table">
                <thead>
                    <tr>
                        <th width="22%">Code</th>
                        <th width="48%">Name</th>
                        <th width="30%" class="ticket-col">Ticket No.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($travelers as $traveler)
                        @php
                            $barcode = $traveler['direction_barcodes'][$direction['key'] ?? 'onward'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                @if ($barcode)
                                    <img src="data:image/png;base64,{{ $barcode }}" alt="Barcode" class="barcode">
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="traveler-name">{{ $traveler['name'] ?? '—' }}</span></td>
                            <td class="ticket-col"><span class="ticket-no">{{ $traveler['ticket_number'] ?? '—' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="baggage-box">
                <div class="title">Baggage</div>
                @if (!empty($direction['cabin_baggage']))
                    <div>Carry-On: {{ $direction['cabin_baggage'] }}</div>
                @endif
                @if (!empty($direction['check_in_baggage']))
                    <div>Baggage Allowance: {{ $direction['check_in_baggage'] }}</div>
                @endif
                @foreach ($direction['baggage_notes'] ?? [] as $note)
                    <div>{{ $note }}</div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($includeFare)
        @php
            $fareTraveler = $travelers[0]['fare'] ?? [];
        @endphp
        @if (!empty($fareTraveler['total']) || !empty($fareTraveler['base']) || !empty($fareTraveler['taxes']))
            <div class="fare-box">
                <h3>Fare Details</h3>
                <table class="fare-row" cellpadding="0" cellspacing="0">
                    @if (!empty($fareTraveler['base']))
                        <tr>
                            <td>Base fare</td>
                            <td>{{ $fareTraveler['base'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($fareTraveler['taxes']))
                        <tr>
                            <td>Taxes</td>
                            <td>{{ $fareTraveler['taxes'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($fareTraveler['total']))
                        <tr>
                            <td>Ticket total</td>
                            <td>{{ $fareTraveler['total'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($fareTraveler['fare_basis']))
                        <tr>
                            <td>Fare basis</td>
                            <td>{{ $fareTraveler['fare_basis'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($fareTraveler['refundable']))
                        <tr>
                            <td>Refundability</td>
                            <td>{{ $fareTraveler['refundable'] }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif
    @endif

    @if (!empty($notes))
        <div class="notes">
            @foreach ($notes as $note)
                <p><strong>Important Note:</strong> {{ $note }}</p>
            @endforeach
        </div>
    @endif
</body>

</html>
