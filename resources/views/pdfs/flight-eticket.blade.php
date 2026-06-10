<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>E-Ticket {{ $eticket['booking']['ref'] ?? '' }}</title>
    <style>
        @page {
            margin: 18px 22px;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1a2540;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #cd1b4f;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            max-width: 150px;
            max-height: 52px;
        }

        .agency-name {
            font-size: 13px;
            font-weight: bold;
            color: #cd1b4f;
            margin: 0 0 2px;
        }

        .agency-legal {
            font-size: 9px;
            font-weight: bold;
            color: #333;
            margin: 0 0 4px;
        }

        .agency-meta {
            font-size: 8.5px;
            color: #555;
            margin: 0;
        }

        .booking-meta {
            text-align: right;
            font-size: 8.5px;
        }

        .booking-meta .ref {
            font-size: 12px;
            font-weight: bold;
            color: #1a2540;
        }

        .status-pill {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            font-weight: bold;
            font-size: 8px;
            padding: 2px 8px;
            border-radius: 10px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .direction-block {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .direction-head {
            background: #eef1f5;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 9px;
            letter-spacing: 0.4px;
            border: 1px solid #d8dee6;
            border-bottom: none;
        }

        .direction-route {
            padding: 8px;
            border: 1px solid #d8dee6;
            border-bottom: none;
        }

        .direction-route h2 {
            font-size: 11px;
            margin: 0 0 2px;
            color: #1a2540;
        }

        .direction-route .sub {
            font-size: 8.5px;
            color: #666;
            margin: 0 0 6px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 2px 8px 2px 0;
            font-size: 8.5px;
        }

        .info-grid .label {
            color: #666;
            font-size: 8px;
        }

        .info-grid .value {
            font-weight: bold;
        }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d8dee6;
        }

        .flight-table th {
            background: #f8f9fb;
            font-size: 7.5px;
            text-transform: uppercase;
            color: #666;
            padding: 5px 6px;
            border-bottom: 1px solid #d8dee6;
            text-align: left;
        }

        .flight-table td {
            padding: 6px;
            border-bottom: 1px solid #eef1f5;
            vertical-align: top;
            font-size: 8.5px;
        }

        .flight-table tr:last-child td {
            border-bottom: none;
        }

        .flight-no {
            font-weight: bold;
            font-size: 9px;
        }

        .muted {
            color: #666;
            font-size: 7.5px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            margin: 14px 0 6px;
            color: #1a2540;
        }

        .traveler-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d8dee6;
        }

        .traveler-table th {
            background: #f8f9fb;
            font-size: 7.5px;
            text-transform: uppercase;
            color: #666;
            padding: 5px 6px;
            border-bottom: 1px solid #d8dee6;
            text-align: left;
        }

        .traveler-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #eef1f5;
            vertical-align: middle;
            font-size: 9px;
        }

        .traveler-table tr:last-child td {
            border-bottom: none;
        }

        .barcode {
            width: 88px;
            height: auto;
        }

        .traveler-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .ticket-no {
            font-family: DejaVu Sans Mono, monospace;
            font-weight: bold;
            text-align: right;
        }

        .baggage-box {
            border: 1px solid #d8dee6;
            border-top: none;
            padding: 8px;
            font-size: 8px;
            color: #444;
        }

        .baggage-box .title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 8.5px;
        }

        .fare-box {
            margin-top: 12px;
            border: 1px solid #d8dee6;
            padding: 8px;
            page-break-inside: avoid;
        }

        .fare-box h3 {
            margin: 0 0 6px;
            font-size: 9px;
            color: #cd1b4f;
        }

        .fare-row {
            width: 100%;
            border-collapse: collapse;
        }

        .fare-row td {
            padding: 2px 0;
            font-size: 8.5px;
        }

        .fare-row td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .notes {
            margin-top: 14px;
            padding: 8px;
            background: #fff8f0;
            border-left: 3px solid #f59e0b;
            font-size: 7.5px;
            color: #555;
            page-break-inside: avoid;
        }

        .notes p {
            margin: 0 0 4px;
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
            <td width="55%">
                @if (!empty($agency['logo_data_uri']))
                    <img src="{{ $agency['logo_data_uri'] }}" alt="Logo" class="logo"><br>
                @endif
                <p class="agency-name">{{ $agency['name'] ?? '' }}</p>
                @if (!empty($agency['legal_name']) && ($agency['legal_name'] ?? '') !== ($agency['name'] ?? ''))
                    <p class="agency-legal">{{ $agency['legal_name'] }}</p>
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
            <td width="45%" class="booking-meta">
                <div class="ref">Ref. No: {{ $booking['ref'] ?? '—' }}</div>
                <div>Date of Booking: <strong>{{ $booking['date'] ?? '—' }}</strong></div>
                <div>Status: <span class="status-pill">{{ $booking['status'] ?? 'CONFIRMED' }}</span></div>
                @if (!empty($booking['pnr']))
                    <div style="margin-top:6px;">Airline Ref: <strong>{{ $booking['airline_ref'] }}</strong></div>
                    <div>CRS Ref: <strong>{{ $booking['crs_ref'] }}</strong></div>
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
                            <div class="label">{{ $direction['airline'] ?? '' }}</div>
                            <div class="value">Travel Class: {{ $direction['travel_class'] ?? 'Economy' }}</div>
                        </td>
                        <td>
                            <div class="label">Check-In Baggage</div>
                            <div class="value">{{ $direction['check_in_baggage'] ?? 'Refer to airline policy' }}</div>
                            <div class="label" style="margin-top:4px;">Cabin Baggage</div>
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
                                {{ $segment['stops_label'] ?? 'Non Stop' }}
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
                        <th width="30%">Ticket No.</th>
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
                            <td class="ticket-no">{{ $traveler['ticket_number'] ?? '—' }}</td>
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
