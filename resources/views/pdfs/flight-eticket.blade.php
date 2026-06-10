<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $eticket['filename_ticket_number'] ?? ($eticket['travelers'][0]['ticket_number'] ?? '') }}</title>
    <style>
        @page {
            margin: 25px;
        }

        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #333;
            font-weight: normal;
            margin: 0;
            padding: 0;
        }

        strong,
        b {
            font-weight: bold;
        }

        th {
            font-weight: bold;
        }

        td {
            font-weight: normal;
        }

        .font-bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .header-table {
            margin-bottom: 10px;
        }

        .company-logo {
            margin-bottom: 8px;
        }

        .company-logo img {
            max-width: 120px;
            height: auto;
            display: block;
        }

        .company-details {
            font-size: 14px;
            color: #555;
            line-height: 1.4;
        }

        .block-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            font-family: sans-serif;
            margin: 14px 0 8px;
        }

        .block-title--inline {
            margin: 0 0 8px;
        }

        .block-title.in-box {
            margin: 0 0 8px;
        }

        .direction-head--attached {
            border-bottom: none;
        }

        .traveler-table--attached {
            border-top: none;
        }

        .doc-title {
            color: #cd1b4f;
            font-weight: bold;
            font-size: 20px;
            text-align: right;
            margin-bottom: 10px;
        }

        .doc-details-table {
            width: auto;
            margin-left: auto;
        }

        .doc-details-table td {
            padding: 2px 0 2px 15px;
            text-align: right;
            white-space: nowrap;
            font-size: 14px;
        }

        .doc-details-table td:first-child {
            color: #555;
        }

        .direction-block {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        .direction-block:first-of-type {
            margin-top: 24px;
        }

        div.direction-head {
            background: #e5e7eb;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 14px;
            color: #333;
            border: 1px solid #cbd5e1;
            border-top: none;
            font-family: sans-serif;
        }

        table.direction-head {
            width: 100%;
            border-collapse: collapse;
            background: #e5e7eb;
            border: none;
        }

        table.direction-head td {
            padding: 8px 10px;
            vertical-align: middle;
            font-family: sans-serif;
            border: none;
        }

        .direction-head__label-cell {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            text-align: left;
            width: 30%;
        }

        .direction-head__refs-cell {
            text-align: right;
            white-space: nowrap;
            width: 70%;
        }

        table.direction-head .ref-pill {
            margin-left: 6px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: normal;
            line-height: 1.3;
            vertical-align: middle;
            white-space: nowrap;
            display: inline-block;
        }

        table.direction-head .ref-pill__key {
            margin-right: 4px;
            display: inline;
            white-space: nowrap;
        }

        .direction-route {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-top: none;
            border-bottom: none;
            background: #fafafa;
        }

        .direction-route .route-title {
            font-size: 14px;
            margin: 0 0 8px;
            color: #333;
            font-weight: bold;
            font-family: 'DejaVu Sans', sans-serif;
        }

        .direction-route .sub {
            font-size: 14px;
            color: #555;
            margin: 0 0 8px;
            font-weight: normal;
            font-family: sans-serif;
        }

        .ref-pill {
            display: inline-block;
            padding: 4px 10px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            font-size: 14px;
            font-weight: normal;
            color: #333;
            line-height: 1.3;
        }

        .ref-pill__key {
            margin-right: 5px;
            color: #555;
        }

        .ref-pill strong {
            font-weight: normal;
            color: #333;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 3px 10px 3px 0;
            font-size: 14px;
            font-weight: normal;
        }

        .info-grid .field {
            margin-bottom: 10px;
        }

        .info-grid .field:last-child {
            margin-bottom: 0;
        }

        .info-grid .label {
            color: #333;
            font-size: 14px;
            font-weight: bold;
            font-family: sans-serif;
        }

        .info-grid .value {
            font-weight: normal;
            color: #333;
            margin-top: 2px;
        }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            table-layout: fixed;
        }

        .flight-table th,
        .flight-table td {
            font-size: 11px;
            color: #333;
            font-family: sans-serif;
            padding: 6px 4px;
            line-height: 1.35;
            vertical-align: top;
        }

        .flight-table th {
            background: #f3f4f6;
            font-weight: bold;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
            white-space: normal;
        }

        .flight-table td {
            font-weight: normal;
            border-bottom: 1px solid #e5e7eb;
            word-wrap: break-word;
        }

        .flight-table tr:last-child td {
            border-bottom: none;
        }

        .flight-table .flight-no,
        .flight-table .flight-primary {
            display: block;
            line-height: 1.15;
            margin: 0;
            font-weight: normal;
        }

        .flight-table .muted {
            margin-top: 0;
            font-size: 11px;
            font-weight: normal;
            color: #555;
            line-height: 1.35;
        }

        .muted {
            color: #555;
            font-size: 11px;
            font-weight: normal;
            margin-top: 2px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 24px 0 8px;
            color: #333;
            font-family: sans-serif;
        }

        .traveler-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }

        .traveler-table th {
            background: #f3f4f6;
            font-size: 14px;
            color: #333;
            font-weight: bold;
            padding: 8px 6px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
            white-space: nowrap;
            font-family: sans-serif;
            line-height: 1.2;
        }

        .traveler-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 14px;
            font-weight: normal;
            color: #333;
            line-height: 1.2;
            font-family: sans-serif;
        }

        .traveler-table tr:last-child td {
            border-bottom: none;
        }

        .barcode {
            width: 96px;
            height: auto;
        }

        .traveler-name,
        .ticket-no {
            font-family: sans-serif;
            font-weight: normal;
            color: #333;
            font-size: 14px;
            line-height: 1.2;
        }

        .traveler-name {
            text-transform: uppercase;
        }

        .traveler-table th.ticket-col,
        .traveler-table td.ticket-col {
            text-align: left;
            vertical-align: middle;
            white-space: nowrap;
        }

        .baggage-box {
            border: 1px solid #cbd5e1;
            border-top: none;
            padding: 10px;
            font-size: 14px;
            font-weight: normal;
            color: #333;
            background: #fafafa;
        }

        .baggage-box .title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin: 0 0 8px;
        }

        .baggage-box .baggage-line {
            margin-bottom: 3px;
            line-height: 1.35;
        }

        .baggage-box .baggage-line:last-child {
            margin-bottom: 0;
        }

        .fare-box {
            margin-top: 24px;
            border: 1px solid #cbd5e1;
            padding: 10px;
            page-break-inside: avoid;
            background: #fafafa;
        }

        .fare-box .fare-title {
            margin: 0 0 8px;
            font-size: 14px;
            color: #333;
            font-weight: bold;
            font-family: sans-serif;
        }

        .fare-row {
            width: 100%;
            border-collapse: collapse;
        }

        .fare-row td {
            padding: 3px 0;
            font-size: 14px;
            font-weight: normal;
            color: #333;
        }

        .fare-row td:last-child {
            text-align: right;
            font-weight: normal;
            color: #333;
        }

        .fare-row tr:last-child td {
            font-weight: bold;
        }

        .notes {
            margin-top: 16px;
            padding: 10px;
            background: #fff7ed;
            border-left: 3px solid #f59e0b;
            font-size: 14px;
            font-weight: normal;
            color: #333;
            page-break-inside: avoid;
        }

        .notes .block-title {
            margin: 0 0 8px;
        }

        .notes p {
            margin: 0 0 5px;
            line-height: 1.5;
        }

        .notes strong {
            font-size: 14px;
            font-weight: bold;
            color: #333;
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

    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                @if (!empty($agency['logo_data_uri']))
                    <div class="company-logo">
                        <img src="{{ $agency['logo_data_uri'] }}" alt="Logo">
                    </div>
                @endif
                <div class="company-details">
                    <strong>{{ $agency['legal_name'] ?? ($agency['name'] ?? '') }}</strong><br>
                    @if (!empty($agency['address']))
                        {{ $agency['address'] }}<br>
                    @endif
                    @if (!empty($agency['country']))
                        {{ $agency['country'] }}<br>
                    @endif
                    @if (!empty($agency['phone']))
                        {{ $agency['phone'] }}<br>
                    @endif
                    @if (!empty($agency['email']))
                        {{ $agency['email'] }}
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="doc-title">E-TICKET</div>
                <table class="doc-details-table" cellpadding="0" cellspacing="0">
                    @if (!empty($booking['ref']))
                        <tr>
                            <td>System Ref:</td>
                            <td>{{ $booking['ref'] }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Date of Booking:</td>
                        <td>{{ $booking['date'] ?? '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @foreach ($directions as $direction)
        <div class="direction-block">
            <table class="direction-head" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="direction-head__label-cell">{{ $direction['label'] ?? 'FLIGHT' }}</td>
                    <td class="direction-head__refs-cell">
                        @if (!empty($direction['airline_ref']))
                            <span class="ref-pill">Airline Ref: {{ $direction['airline_ref'] }}</span>
                        @endif
                        @if (!empty($direction['crs_ref']))
                            <span class="ref-pill">CRS Ref: {{ $direction['crs_ref'] }}</span>
                        @endif
                    </td>
                </tr>
            </table>
            <div class="direction-route">
                <div class="block-title block-title--inline route-title">{{ $direction['route_title'] ?? '' }}</div>
                <div class="sub">{{ $direction['meta_line'] ?? '' }}</div>
                <table class="info-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div class="field">
                                <div class="label">Airline</div>
                                <div class="value">{{ $direction['airline'] ?? '—' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Travel Class</div>
                                <div class="value">{{ $direction['travel_class'] ?? 'Economy' }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="field">
                                <div class="label">Check-In Baggage</div>
                                <div class="value">{{ $direction['check_in_baggage'] ?? 'Refer to airline policy' }}</div>
                            </div>
                            <div class="field">
                                <div class="label">Cabin Baggage</div>
                                <div class="value">{{ $direction['cabin_baggage'] ?? 'Refer to airline policy' }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="flight-table">
                <colgroup>
                    <col width="12%">
                    <col width="18%">
                    <col width="18%">
                    <col width="12%">
                    <col width="18%">
                    <col width="22%">
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
                                <span class="flight-primary">{{ $segment['from_code'] ?? '' }}</span>
                                @if (!empty($segment['from_terminal']))
                                    <div class="muted">Terminal {{ $segment['from_terminal'] }}</div>
                                @endif
                                @if (!empty($segment['from_airport']))
                                    <div class="muted">{{ $segment['from_airport'] }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="flight-primary">{{ $segment['departure_time'] ?? '—' }}</span>
                                @if (!empty($segment['departure_date']))
                                    <div class="muted">{{ $segment['departure_date'] }}</div>
                                @endif
                            </td>
                            <td>{{ $segment['stops_display'] ?? ($segment['stops_label'] ?? 'Non Stop') }}</td>
                            <td>
                                <span class="flight-primary">{{ $segment['to_code'] ?? '' }}</span>
                                @if (!empty($segment['to_terminal']))
                                    <div class="muted">Terminal {{ $segment['to_terminal'] }}</div>
                                @endif
                                @if (!empty($segment['to_airport']))
                                    <div class="muted">{{ $segment['to_airport'] }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="flight-primary">{{ $segment['arrival_time'] ?? '—' }}</span>
                                @if (!empty($segment['arrival_date']))
                                    <div class="muted">{{ $segment['arrival_date'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="block-title section-title">Traveler(s) Information</div>
            <div class="direction-head direction-head--attached">{{ $direction['label'] ?? '' }}</div>
            <table class="traveler-table traveler-table--attached">
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
                <div class="block-title in-box">BAGGAGE</div>
                @if (!empty($direction['cabin_baggage']))
                    <div class="baggage-line">Carry-On: {{ $direction['cabin_baggage'] }}</div>
                @endif
                @if (!empty($direction['check_in_baggage']))
                    <div class="baggage-line">Baggage Allowance: {{ $direction['check_in_baggage'] }}</div>
                @endif
                @foreach ($direction['baggage_notes'] ?? [] as $note)
                    <div class="baggage-line">{{ $note }}</div>
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
                <div class="block-title in-box">Fare Details</div>
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
            <div class="block-title">Important Note</div>
            @foreach ($notes as $note)
                <p>{{ $note }}</p>
            @endforeach
        </div>
    @endif
</body>

</html>
