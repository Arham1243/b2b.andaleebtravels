<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $eticket['filename_ticket_number'] ?? ($eticket['travelers'][0]['ticket_number'] ?? '') }}</title>
    <style>
        @page {
            margin: 16px 20px;
        }

        body,
        table,
        td,
        th,
        div,
        span,
        p,
        a {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        body {
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        .header {
            width: 100%;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .header td {
            vertical-align: top;
        }

        .header-left {
            padding-right: 16px;
        }

        .company-logo-wrap {
            margin-bottom: 8px;
        }

        .logo {
            max-width: 100px;
            max-height: 42px;
        }

        .company-details {
            font-size: 11px;
            color: #444;
            line-height: 1.45;
        }

        .company-details strong {
            color: #111;
            font-size: 12px;
        }

        .doc-title {
            color: #cd1b4f;
            font-weight: bold;
            font-size: 18px;
            text-align: right;
            margin-bottom: 8px;
            font-family: DejaVu Sans, sans-serif;
        }

        .doc-details-table {
            width: auto;
            margin-left: auto;
            border-collapse: collapse;
        }

        .doc-details-table td {
            padding: 2px 0 2px 14px;
            text-align: right;
            white-space: nowrap;
            font-size: 11px;
            color: #333;
        }

        .doc-details-table td:first-child {
            color: #555;
        }

        .doc-details-table td strong {
            color: #111;
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
            color: #111;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            font-family: DejaVu Sans, sans-serif;
        }

        .direction-route {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            background: #fafafa;
        }

        .direction-route .route-title {
            font-size: 14px;
            margin: 0 0 4px;
            color: #111;
            font-weight: bold;
            font-family: DejaVu Sans, sans-serif;
        }

        .direction-route .sub {
            font-size: 11px;
            color: #444;
            margin: 0 0 8px;
            font-weight: normal;
            font-family: DejaVu Sans, sans-serif;
        }

        .direction-ref-pills {
            margin-top: 10px;
            margin-bottom: 2px;
            font-size: 10px;
            color: #444;
        }

        .direction-ref-pills span {
            display: inline-block;
            margin-right: 8px;
            padding: 4px 10px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
        }

        .direction-ref-pills strong {
            color: #111;
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
            color: #444;
            font-size: 10px;
            font-weight: bold;
            font-family: DejaVu Sans, sans-serif;
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
            table-layout: fixed;
        }

        .flight-table th {
            background: #f3f4f6;
            font-size: 9px;
            color: #444;
            font-weight: bold;
            padding: 6px 5px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
            white-space: nowrap;
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.2;
        }

        .flight-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 10px;
            color: #111827;
            word-wrap: break-word;
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
            color: #111;
            font-family: DejaVu Sans, sans-serif;
        }

        .traveler-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }

        .traveler-table th {
            background: #f3f4f6;
            font-size: 10px;
            color: #444;
            font-weight: bold;
            padding: 7px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
            font-family: DejaVu Sans, sans-serif;
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

        .fare-box .fare-title {
            margin: 0 0 8px;
            font-size: 12px;
            color: #cd1b4f;
            font-weight: bold;
            font-family: DejaVu Sans, sans-serif;
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
            <td width="50%" valign="top" class="header-left">
                @if (!empty($agency['logo_data_uri']))
                    <div class="company-logo-wrap">
                        <img src="{{ $agency['logo_data_uri'] }}" alt="Logo" class="logo">
                    </div>
                @endif
                <div class="company-details">
                    <strong>{{ $agency['legal_name'] ?? ($agency['name'] ?? '') }}</strong><br>
                    @if (!empty($agency['address']))
                        {{ $agency['address'] }}<br>
                    @endif
                    @if (!empty($agency['phone']))
                        {{ $agency['phone'] }}<br>
                    @endif
                    @if (!empty($agency['email']))
                        {{ $agency['email'] }}
                    @endif
                </div>
            </td>
            <td width="50%" valign="top">
                <div class="doc-title">E-TICKET</div>
                <table class="doc-details-table" cellpadding="0" cellspacing="0">
                    @if (!empty($booking['ref']))
                        <tr>
                            <td>System Ref:</td>
                            <td><strong>{{ $booking['ref'] }}</strong></td>
                        </tr>
                    @endif
                    <tr>
                        <td>Date of Booking:</td>
                        <td><strong>{{ $booking['date'] ?? '—' }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @foreach ($directions as $direction)
        <div class="direction-block">
            <div class="direction-head">{{ $direction['label'] ?? 'FLIGHT' }}</div>
            <div class="direction-route">
                <div class="route-title">{{ $direction['route_title'] ?? '' }}</div>
                <div class="sub">{{ $direction['meta_line'] ?? '' }}</div>
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
                @if (!empty($direction['airline_ref']) || !empty($direction['crs_ref']))
                    <div class="direction-ref-pills">
                        @if (!empty($direction['airline_ref']))
                            <span>Airline Ref: <strong>{{ $direction['airline_ref'] }}</strong></span>
                        @endif
                        @if (!empty($direction['crs_ref']))
                            <span>CRS Ref: <strong>{{ $direction['crs_ref'] }}</strong></span>
                        @endif
                    </div>
                @endif
            </div>

            <table class="flight-table">
                <colgroup>
                    <col width="11%">
                    <col width="20%">
                    <col width="17%">
                    <col width="12%">
                    <col width="20%">
                    <col width="20%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Flight Number</th>
                        <th>From (Terminal)</th>
                        <th>Departure date &amp; time</th>
                        <th>Stops</th>
                        <th>To (Terminal)</th>
                        <th>Arrival date &amp; time</th>
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
                                @if (!empty($segment['from_terminal']))
                                    <div class="muted">Terminal {{ $segment['from_terminal'] }}</div>
                                @endif
                                @if (!empty($segment['from_airport']))
                                    <div class="muted">{{ $segment['from_airport'] }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $segment['departure_time'] ?? '—' }}</strong>
                                @if (!empty($segment['departure_date']))
                                    <div class="muted">{{ $segment['departure_date'] }}</div>
                                @endif
                            </td>
                            <td>{{ $segment['stops_display'] ?? ($segment['stops_label'] ?? 'Non Stop') }}</td>
                            <td>
                                <strong>{{ $segment['to_code'] ?? '' }}</strong>
                                @if (!empty($segment['to_terminal']))
                                    <div class="muted">Terminal {{ $segment['to_terminal'] }}</div>
                                @endif
                                @if (!empty($segment['to_airport']))
                                    <div class="muted">{{ $segment['to_airport'] }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $segment['arrival_time'] ?? '—' }}</strong>
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
                <div class="fare-title">Fare Details</div>
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
