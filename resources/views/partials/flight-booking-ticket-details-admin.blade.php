@php
    $details = $adminEticketDetails ?? ['has_content' => false];
    $fareBreakdown = $details['fare_breakdown'] ?? [];
    $tickets = $details['tickets'] ?? [];
    $storedFares = $details['stored_fares'] ?? [];
    $itinerary = $details['itinerary'] ?? [];
    $currency = strtoupper((string) ($fareBreakdown['currency'] ?? ($itinerary['currency'] ?? 'AED')));
    $monoKeys = ['gds_pnr', 'supplier_pnr', 'air_reservation_locator', 'fare_basis', 'fare_calculation', 'fare_calculation_ind', 'booking_code'];
    $wideKeys = ['fare_calculation', 'fare_calculation_ind'];
@endphp

@if(!empty($details['has_content']))
<div class="bkpd-card mb-3 bkpd-eticket-admin">
    <div class="bkpd-card__section-head bkpd-card__section-head--green">
        <i class="bx bx-receipt"></i> E-Ticket Details
        @if(!empty($details['source']) && $details['source'] === 'live')
            <span class="bkpd-ticket-source">Live from {{ $booking->providerLabel() }}</span>
        @elseif(!empty($details['source']))
            <span class="bkpd-ticket-source">{{ ucfirst($details['source']) }} {{ $booking->providerLabel() }} data</span>
        @endif
    </div>

    @if(!empty($details['error']) && ($details['source'] ?? '') === 'fallback')
        <div class="bkpd-ticket-note">
            <i class="bx bx-info-circle"></i>
            Full ticket document could not be refreshed from {{ $booking->providerLabel() }}.
            Showing saved ticket numbers, stored fares, and booking fare breakdown.
        </div>
    @endif

    @php
        $pnrRefs = is_array($details['pnr_references'] ?? null) ? $details['pnr_references'] : [];
        $gdsPnr = strtoupper(trim((string) ($pnrRefs['gds_pnr'] ?? '')));
        $supplierPnr = strtoupper(trim((string) ($pnrRefs['supplier_pnr'] ?? '')));
        $supplierCode = strtoupper(trim((string) ($pnrRefs['supplier_code'] ?? '')));
    @endphp

    @if($gdsPnr !== '' || $supplierPnr !== '')
        <div class="bkpd-eticket-admin__section bkpd-eticket-admin__section--pnr">
            <div class="bkpd-eticket-admin__section-title">Record locators</div>
            <div class="bkpd-eticket-admin__pnr-grid">
                @if($gdsPnr !== '')
                    <div class="bkpd-eticket-admin__pnr-card">
                        <div class="bkpd-eticket-admin__pnr-label">GDS PNR</div>
                        <div class="bkpd-eticket-admin__pnr-value">{{ $gdsPnr }}</div>
                    </div>
                @endif
                @if($supplierPnr !== '')
                    <div class="bkpd-eticket-admin__pnr-card">
                        <div class="bkpd-eticket-admin__pnr-label">Supplier PNR{{ $supplierCode !== '' ? ' (' . $supplierCode . ')' : '' }}</div>
                        <div class="bkpd-eticket-admin__pnr-value">{{ $supplierPnr }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Booking fare summary --}}
    <div class="bkpd-eticket-admin__section">
        <div class="bkpd-eticket-admin__section-title">Booking fare summary</div>

        <div class="eta-stat-row">
            <div class="eta-stat eta-stat--primary">
                <span class="eta-stat__label">Quoted total</span>
                <span class="eta-stat__val">{!! formatPrice($booking->total_amount) !!}</span>
            </div>
            @if(($fareBreakdown['supplier_base'] ?? 0) > 0)
                <div class="eta-stat">
                    <span class="eta-stat__label">Supplier base (GDS)</span>
                    <span class="eta-stat__val">{{ $currency }} {{ number_format((float) $fareBreakdown['supplier_base'], 2) }}</span>
                </div>
            @endif
            @if(($fareBreakdown['supplier_tax'] ?? 0) > 0)
                <div class="eta-stat">
                    <span class="eta-stat__label">Supplier tax (GDS)</span>
                    <span class="eta-stat__val">{{ $currency }} {{ number_format((float) $fareBreakdown['supplier_tax'], 2) }}</span>
                </div>
            @endif
            @if(($fareBreakdown['base_fare'] ?? 0) > 0)
                <div class="eta-stat">
                    <span class="eta-stat__label">Sell base fare</span>
                    <span class="eta-stat__val">{{ $currency }} {{ number_format((float) $fareBreakdown['base_fare'], 2) }}</span>
                </div>
            @endif
            @if(($fareBreakdown['tax_charges'] ?? 0) > 0)
                <div class="eta-stat">
                    <span class="eta-stat__label">Sell tax &amp; charges</span>
                    <span class="eta-stat__val">{{ $currency }} {{ number_format((float) $fareBreakdown['tax_charges'], 2) }}</span>
                </div>
            @endif
            @if($fareBreakdown['show_discount'] ?? false)
                <div class="eta-stat eta-stat--earn">
                    <span class="eta-stat__label">Agency discount</span>
                    <span class="eta-stat__val eta-stat__val--discount">− {{ $currency }} {{ number_format((float) $fareBreakdown['discount'], 2) }}</span>
                </div>
            @endif
            @if($fareBreakdown['show_you_earn'] ?? false)
                <div class="eta-stat eta-stat--earn">
                    <span class="eta-stat__label">Agency markup / earn</span>
                    <span class="eta-stat__val">{{ $currency }} {{ number_format((float) $fareBreakdown['you_earn'], 2) }}</span>
                </div>
            @endif
            @if($fareBreakdown['show_net_fare'] ?? false)
                <div class="eta-stat">
                    <span class="eta-stat__label">Net fare</span>
                    <span class="eta-stat__val">{{ $currency }} {{ number_format((float) $fareBreakdown['net_fare'], 2) }}</span>
                </div>
            @endif
        </div>

        @include('admin.flight-bookings.partials.eticket-kv-grid', [
            'items' => array_filter([
                'itinerary_fare_basis' => !empty($itinerary['fare_basis']) ? ['label' => 'Itinerary fare basis', 'value' => e($itinerary['fare_basis']), 'mono' => true] : null,
                'booking_code' => !empty($itinerary['booking_code']) ? ['label' => 'Booking class', 'value' => e(strtoupper($itinerary['booking_code'])), 'mono' => true] : null,
            ]),
        ])

        @if(!empty($fareBreakdown['base_lines']) || !empty($fareBreakdown['tax_lines']))
            <div class="eta-tables-duo">
                @if(!empty($fareBreakdown['base_lines']))
                    <div class="bkpd-eticket-admin__table-wrap">
                        <table class="bkpd-eticket-admin__table">
                            <thead>
                                <tr>
                                    <th>Passenger type</th>
                                    <th>Cnt</th>
                                    <th>Base / pax</th>
                                    <th>Base total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fareBreakdown['base_lines'] as $line)
                                    <tr>
                                        <td>{{ $line['label'] ?? '—' }}</td>
                                        <td>{{ $line['count'] ?? 0 }}</td>
                                        <td>{{ $currency }} {{ number_format((float) ($line['unit'] ?? 0), 2) }}</td>
                                        <td>{{ $currency }} {{ number_format((float) ($line['total'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(!empty($fareBreakdown['tax_lines']))
                    <div class="bkpd-eticket-admin__table-wrap">
                        <table class="bkpd-eticket-admin__table">
                            <thead>
                                <tr>
                                    <th>Passenger type</th>
                                    <th>Cnt</th>
                                    <th>Tax / pax</th>
                                    <th>Tax total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fareBreakdown['tax_lines'] as $line)
                                    <tr>
                                        <td>{{ $line['label'] ?? '—' }}</td>
                                        <td>{{ $line['count'] ?? 0 }}</td>
                                        <td>{{ $currency }} {{ number_format((float) ($line['unit'] ?? 0), 2) }}</td>
                                        <td>{{ $currency }} {{ number_format((float) ($line['total'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Stored GDS fares --}}
    @if($storedFares !== [])
        <div class="bkpd-eticket-admin__section">
            <div class="bkpd-eticket-admin__section-title">Stored GDS fares ({{ $booking->providerLabel() }})</div>
            <div class="eta-fare-grid">
                @foreach($storedFares as $fare)
                    <div class="bkpd-eticket-admin__fare-block">
                        <div class="bkpd-eticket-admin__fare-head">
                            <strong>{{ $fare['passenger_type'] ?? 'Passenger' }}</strong>
                            @if(!empty($fare['passenger_type_code']))
                                <span class="bkpd-ticket__pax-type">{{ $fare['passenger_type_code'] }}</span>
                            @endif
                            @if(!empty($fare['total_price']))
                                <span class="ms-auto fw-bold">{{ $fare['total_price'] }}</span>
                            @endif
                        </div>

                        <div style="padding:.65rem .7rem .7rem;">
                            @include('admin.flight-bookings.partials.eticket-kv-grid', [
                                'items' => array_filter([
                                    'base_price' => !empty($fare['base_price']) ? ['label' => 'Base', 'value' => e($fare['base_price'])] : null,
                                    'taxes' => !empty($fare['taxes']) ? ['label' => 'Taxes total', 'value' => e($fare['taxes'])] : null,
                                    'pricing_method' => !empty($fare['pricing_method']) ? ['label' => 'Pricing method', 'value' => e($fare['pricing_method'])] : null,
                                    'pricing_type' => !empty($fare['pricing_type']) ? ['label' => 'Pricing type', 'value' => e($fare['pricing_type'])] : null,
                                    'e_ticketability' => !empty($fare['e_ticketability']) ? ['label' => 'E-ticketable', 'value' => e($fare['e_ticketability'])] : null,
                                    'class_cabin' => (!empty($fare['booking_code']) || !empty($fare['cabin_class'])) ? ['label' => 'Class / cabin', 'value' => e(trim(($fare['booking_code'] ?? '') . ' / ' . ($fare['cabin_class'] ?? ''), ' /'))] : null,
                                    'fare_calculation' => !empty($fare['fare_calculation']) ? ['label' => 'Fare calculation', 'value' => e($fare['fare_calculation']), 'mono' => true, 'wide' => true] : null,
                                ]),
                            ])

                            @if(!empty($fare['tax_items']))
                                <div class="bkpd-eticket-admin__table-wrap" style="margin-top:.5rem;">
                                    <table class="bkpd-eticket-admin__table">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Description</th>
                                                <th>Amt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fare['tax_items'] as $tax)
                                                <tr>
                                                    <td style="font-family:monospace;">{{ $tax['category'] ?? '—' }}</td>
                                                    <td>{{ $tax['label'] ?? '—' }}</td>
                                                    <td>{{ $tax['amount'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @foreach($fare['fare_infos'] ?? [] as $fareInfo)
                                @if(!empty($fareInfo['fare_basis']) || !empty($fareInfo['endorsements']) || !empty($fareInfo['baggage']))
                                    <div class="eta-fare-info-chip">
                                        @if(!empty($fareInfo['fare_basis']))
                                            <div><strong>Fare basis</strong> <code>{{ $fareInfo['fare_basis'] }}</code>
                                                @if(!empty($fareInfo['origin']) && !empty($fareInfo['destination']))
                                                    · {{ $fareInfo['origin'] }}→{{ $fareInfo['destination'] }}
                                                @endif
                                            </div>
                                        @endif
                                        @if(!empty($fareInfo['baggage']))
                                            <div><strong>Baggage</strong> {{ $fareInfo['baggage'] }}</div>
                                        @endif
                                        @if(!empty($fareInfo['endorsements']))
                                            <div><strong>Endorsements</strong> {{ implode(' · ', $fareInfo['endorsements']) }}</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Per-ticket documents --}}
    @if($tickets !== [])
        <div class="bkpd-eticket-admin__section" style="padding-bottom:0;border-top:1px solid var(--c-line-inner);">
            <div class="bkpd-eticket-admin__section-title" style="margin-bottom:0;">Ticket documents ({{ count($tickets) }})</div>
        </div>
        <div class="eta-ticket-grid">
            @foreach($tickets as $ticket)
                <div class="bkpd-ticket bkpd-eticket-admin__ticket">
                    <div class="bkpd-ticket__head">
                        <div>
                            <div class="bkpd-ticket__number">{{ $ticket['ticket_number'] ?? '—' }}</div>
                            @if(!empty($ticket['passenger_name']))
                                <div class="bkpd-ticket__pax">
                                    {{ $ticket['passenger_name'] }}
                                    @if(!empty($ticket['passenger_type']))
                                        <span class="bkpd-ticket__pax-type">{{ $ticket['passenger_type'] }}</span>
                                    @endif
                                    @if(!empty($ticket['passenger_type_code']))
                                        <span class="bkpd-ticket__pax-type">{{ $ticket['passenger_type_code'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if(!empty($ticket['ticket_status']))
                            <span class="bkp-badge bkp-badge--ticket">{{ $ticket['ticket_status'] }}</span>
                        @endif
                    </div>

                    <div class="bkpd-ticket__body">
                        <div class="eta-group">
                            <div class="eta-group__title">Record locators &amp; carrier</div>
                            @include('admin.flight-bookings.partials.eticket-kv-grid', [
                                'items' => array_filter([
                                    'gds_pnr' => !empty($ticket['gds_pnr']) ? ['label' => 'GDS PNR', 'value' => e($ticket['gds_pnr']), 'mono' => true] : null,
                                    'supplier_pnr' => !empty($ticket['supplier_pnr']) ? ['label' => 'Supplier PNR', 'value' => e($ticket['supplier_pnr']), 'mono' => true] : null,
                                    'air_reservation_locator' => !empty($ticket['air_reservation_locator']) ? ['label' => 'Air reservation locator', 'value' => e($ticket['air_reservation_locator']), 'mono' => true] : null,
                                    'plating_carrier' => !empty($ticket['plating_carrier']) ? ['label' => 'Plating carrier', 'value' => e($ticket['plating_carrier'])] : null,
                                    'provider_code' => !empty($ticket['provider_code']) ? ['label' => 'Provider', 'value' => e($ticket['provider_code'])] : null,
                                    'iata_number' => !empty($ticket['iata_number']) ? ['label' => 'IATA number', 'value' => e($ticket['iata_number'])] : null,
                                    'pseudo_city_code' => !empty($ticket['pseudo_city_code']) ? ['label' => 'PCC', 'value' => e($ticket['pseudo_city_code'])] : null,
                                ]),
                                'monoKeys' => $monoKeys,
                            ])
                        </div>

                        <div class="eta-group">
                            <div class="eta-group__title">Passenger &amp; ticket</div>
                            @include('admin.flight-bookings.partials.eticket-kv-grid', [
                                'items' => array_filter([
                                    'passenger_dob' => !empty($ticket['passenger_dob']) ? ['label' => 'Date of birth', 'value' => e($ticket['passenger_dob'])] : null,
                                    'passenger_gender' => !empty($ticket['passenger_gender']) ? ['label' => 'Gender', 'value' => e($ticket['passenger_gender'])] : null,
                                    'issued_date' => !empty($ticket['issued_date']) ? ['label' => 'Issued', 'value' => e($ticket['issued_date'])] : null,
                                    'refundable' => !empty($ticket['refundable']) ? ['label' => 'Refundability', 'value' => e($ticket['refundable'])] : null,
                                    'exchangeable' => !empty($ticket['exchangeable']) ? ['label' => 'Exchangeable', 'value' => e($ticket['exchangeable'])] : null,
                                ]),
                            ])
                        </div>

                        <div class="eta-group">
                            <div class="eta-group__title">Fare amounts</div>
                            @include('admin.flight-bookings.partials.eticket-kv-grid', [
                                'items' => array_filter([
                                    'fare_basis' => !empty($ticket['fare_basis']) ? ['label' => 'Fare basis', 'value' => e($ticket['fare_basis']), 'mono' => true] : null,
                                    'payment_amount' => !empty($ticket['payment_amount']) ? ['label' => 'Payment amount', 'value' => e($ticket['payment_amount'])] : null,
                                    'total_price' => !empty($ticket['total_price']) ? ['label' => 'Ticket total', 'value' => e($ticket['total_price'])] : null,
                                    'base_price' => !empty($ticket['base_price']) ? ['label' => 'Base fare', 'value' => e($ticket['base_price'])] : null,
                                    'taxes' => !empty($ticket['taxes']) ? ['label' => 'Taxes total', 'value' => e($ticket['taxes'])] : null,
                                    'fare_calculation' => !empty($ticket['fare_calculation']) ? ['label' => 'Fare calculation', 'value' => e($ticket['fare_calculation']), 'mono' => true, 'wide' => true] : null,
                                ]),
                                'monoKeys' => $monoKeys,
                                'wideKeys' => $wideKeys,
                            ])
                        </div>
                    </div>

                    @php $pricing = is_array($ticket['pricing'] ?? null) ? $ticket['pricing'] : []; @endphp
                    @if($pricing !== [])
                        <div class="bkpd-eticket-admin__subblock">
                            <div class="bkpd-eticket-admin__section-title">Pricing details</div>
                            @include('admin.flight-bookings.partials.eticket-kv-grid', [
                                'items' => array_filter([
                                    'pricing_method' => !empty($pricing['pricing_method']) ? ['label' => 'Pricing method', 'value' => e($pricing['pricing_method'])] : null,
                                    'pricing_type' => !empty($pricing['pricing_type']) ? ['label' => 'Pricing type', 'value' => e($pricing['pricing_type'])] : null,
                                    'e_ticketability' => !empty($pricing['e_ticketability']) ? ['label' => 'E-ticketable', 'value' => e($pricing['e_ticketability'])] : null,
                                    'fare_calculation_ind' => !empty($pricing['fare_calculation_ind']) ? ['label' => 'Fare calc indicator', 'value' => e($pricing['fare_calculation_ind']), 'wide' => true] : null,
                                    'latest_ticketing_time' => !empty($pricing['latest_ticketing_time']) ? ['label' => 'Latest ticketing time', 'value' => e($pricing['latest_ticketing_time'])] : null,
                                    'true_last_date_to_ticket' => !empty($pricing['true_last_date_to_ticket']) ? ['label' => 'Last date to ticket', 'value' => e($pricing['true_last_date_to_ticket'])] : null,
                                    'approximate_base_price' => !empty($pricing['approximate_base_price']) ? ['label' => 'Approx. base', 'value' => e($pricing['approximate_base_price'])] : null,
                                    'approximate_total_price' => !empty($pricing['approximate_total_price']) ? ['label' => 'Approx. total', 'value' => e($pricing['approximate_total_price'])] : null,
                                    'booking_code' => !empty($pricing['booking_code']) ? ['label' => 'Booking class', 'value' => e($pricing['booking_code']), 'mono' => true] : null,
                                    'cabin_class' => !empty($pricing['cabin_class']) ? ['label' => 'Cabin', 'value' => e($pricing['cabin_class'])] : null,
                                ]),
                                'monoKeys' => $monoKeys,
                                'wideKeys' => $wideKeys,
                            ])

                            @if(!empty($pricing['tax_items']))
                                <div class="bkpd-eticket-admin__table-wrap" style="margin-top:.45rem;">
                                    <table class="bkpd-eticket-admin__table">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Description</th>
                                                <th>Amt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pricing['tax_items'] as $tax)
                                                <tr>
                                                    <td style="font-family:monospace;">{{ $tax['category'] ?? '—' }}</td>
                                                    <td>{{ $tax['label'] ?? '—' }}</td>
                                                    <td>{{ $tax['amount'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @foreach($pricing['fare_infos'] ?? [] as $fareInfo)
                                <div class="eta-fare-info-chip">
                                    @if(!empty($fareInfo['fare_basis']))
                                        <div><strong>Fare basis</strong> <code>{{ $fareInfo['fare_basis'] }}</code></div>
                                    @endif
                                    @if(!empty($fareInfo['origin']) && !empty($fareInfo['destination']))
                                        <div><strong>Market</strong> {{ $fareInfo['origin'] }} → {{ $fareInfo['destination'] }}</div>
                                    @endif
                                    @if(!empty($fareInfo['effective_date']))
                                        <div><strong>Effective</strong> {{ $fareInfo['effective_date'] }}</div>
                                    @endif
                                    @if(!empty($fareInfo['not_valid_before']) || !empty($fareInfo['not_valid_after']))
                                        <div><strong>Validity</strong> {{ $fareInfo['not_valid_before'] ?? '—' }} – {{ $fareInfo['not_valid_after'] ?? '—' }}</div>
                                    @endif
                                    @if(!empty($fareInfo['baggage']))
                                        <div><strong>Baggage</strong> {{ $fareInfo['baggage'] }}</div>
                                    @endif
                                    @if(!empty($fareInfo['endorsements']))
                                        <div><strong>Endorsements</strong> {{ implode(' · ', $fareInfo['endorsements']) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($ticket['ssrs']))
                        <div class="bkpd-eticket-admin__subblock">
                            <div class="bkpd-eticket-admin__section-title">SSR / remarks</div>
                            <div class="bkpd-eticket-admin__table-wrap">
                                <table class="bkpd-eticket-admin__table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>St</th>
                                            <th>Cxr</th>
                                            <th>Text</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ticket['ssrs'] as $ssr)
                                            <tr>
                                                <td style="font-family:monospace;">{{ $ssr['type'] ?? '—' }}</td>
                                                <td>{{ $ssr['status'] ?? '—' }}</td>
                                                <td>{{ $ssr['carrier'] ?? '—' }}</td>
                                                <td style="word-break:break-word;">{{ $ssr['free_text'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(!empty($ticket['coupons']))
                        <div class="bkpd-ticket__coupons">
                            <div class="bkpd-ticket__coupons-title">Flight coupons</div>
                            <div class="bkpd-eticket-admin__table-wrap">
                                <table class="bkpd-eticket-admin__table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Flight</th>
                                            <th>Route</th>
                                            <th>Departure</th>
                                            <th>Cls</th>
                                            <th>F/B</th>
                                            <th>Valid</th>
                                            <th>St</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ticket['coupons'] as $coupon)
                                            <tr>
                                                <td>{{ $coupon['coupon_number'] ?? '—' }}</td>
                                                <td>{{ $coupon['flight'] ?? '—' }}</td>
                                                <td>{{ $coupon['route'] ?? '' }}</td>
                                                <td style="white-space:nowrap;">{{ $coupon['departure'] ?? '—' }}</td>
                                                <td>{{ $coupon['booking_class'] ?? '—' }}</td>
                                                <td style="font-family:monospace;">{{ $coupon['fare_basis'] ?? '—' }}</td>
                                                <td style="white-space:nowrap;">
                                                    @if(!empty($coupon['not_valid_before']) || !empty($coupon['not_valid_after']))
                                                        {{ $coupon['not_valid_before'] ?? '—' }} – {{ $coupon['not_valid_after'] ?? '—' }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $coupon['status'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endif
