@php
    $details = $adminEticketDetails ?? ['has_content' => false];
    $fareBreakdown = $details['fare_breakdown'] ?? [];
    $tickets = $details['tickets'] ?? [];
    $storedFares = $details['stored_fares'] ?? [];
    $itinerary = $details['itinerary'] ?? [];
    $currency = strtoupper((string) ($fareBreakdown['currency'] ?? ($itinerary['currency'] ?? 'AED')));
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
                        <div class="bkpd-eticket-admin__pnr-label">Supplier PNR@if($supplierCode !== '') ({{ $supplierCode }})@endif</div>
                        <div class="bkpd-eticket-admin__pnr-value">{{ $supplierPnr }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Order-level fare breakdown --}}
    <div class="bkpd-eticket-admin__section">
        <div class="bkpd-eticket-admin__section-title">Booking fare summary</div>

        <div class="bkpd-info-rows bkpd-info-rows--compact mb-2">
            <div class="bkpd-info-row">
                <span class="bkpd-info-row__label">Quoted total</span>
                <span class="bkpd-info-row__val">{!! formatPrice($booking->total_amount) !!}</span>
            </div>
            @if(($fareBreakdown['supplier_base'] ?? 0) > 0)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Supplier base (GDS)</span>
                    <span class="bkpd-info-row__val">{{ $currency }} {{ number_format((float) $fareBreakdown['supplier_base'], 2) }}</span>
                </div>
            @endif
            @if(($fareBreakdown['supplier_tax'] ?? 0) > 0)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Supplier tax (GDS)</span>
                    <span class="bkpd-info-row__val">{{ $currency }} {{ number_format((float) $fareBreakdown['supplier_tax'], 2) }}</span>
                </div>
            @endif
            @if(($fareBreakdown['base_fare'] ?? 0) > 0)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Sell base fare</span>
                    <span class="bkpd-info-row__val">{{ $currency }} {{ number_format((float) $fareBreakdown['base_fare'], 2) }}</span>
                </div>
            @endif
            @if(($fareBreakdown['tax_charges'] ?? 0) > 0)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Sell tax &amp; charges</span>
                    <span class="bkpd-info-row__val">{{ $currency }} {{ number_format((float) $fareBreakdown['tax_charges'], 2) }}</span>
                </div>
            @endif
            @if($fareBreakdown['show_discount'] ?? false)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Agency discount</span>
                    <span class="bkpd-info-row__val" style="color:#10b981;">− {{ $currency }} {{ number_format((float) $fareBreakdown['discount'], 2) }}</span>
                </div>
            @endif
            @if($fareBreakdown['show_you_earn'] ?? false)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Agency markup / earn</span>
                    <span class="bkpd-info-row__val">{{ $currency }} {{ number_format((float) $fareBreakdown['you_earn'], 2) }}</span>
                </div>
            @endif
            @if($fareBreakdown['show_net_fare'] ?? false)
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Net fare</span>
                    <span class="bkpd-info-row__val">{{ $currency }} {{ number_format((float) $fareBreakdown['net_fare'], 2) }}</span>
                </div>
            @endif
            @if(!empty($itinerary['fare_basis']))
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Itinerary fare basis</span>
                    <span class="bkpd-info-row__val" style="font-family:monospace;">{{ $itinerary['fare_basis'] }}</span>
                </div>
            @endif
            @if(!empty($itinerary['booking_code']))
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Booking class</span>
                    <span class="bkpd-info-row__val" style="font-family:monospace;">{{ strtoupper($itinerary['booking_code']) }}</span>
                </div>
            @endif
        </div>

        @if(!empty($fareBreakdown['base_lines']))
            <div class="bkpd-eticket-admin__table-wrap">
                <table class="bkpd-eticket-admin__table">
                    <thead>
                        <tr>
                            <th>Passenger type</th>
                            <th>Count</th>
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
            <div class="bkpd-eticket-admin__table-wrap mt-2">
                <table class="bkpd-eticket-admin__table">
                    <thead>
                        <tr>
                            <th>Passenger type</th>
                            <th>Count</th>
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

    {{-- Stored GDS fares (hold / ticket XML) --}}
    @if($storedFares !== [])
        <div class="bkpd-eticket-admin__section">
            <div class="bkpd-eticket-admin__section-title">Stored GDS fares ({{ $booking->providerLabel() }})</div>
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

                    <div class="bkpd-info-rows bkpd-info-rows--compact">
                        @if(!empty($fare['pricing_info_key']))
                            <div class="bkpd-info-row">
                                <span class="bkpd-info-row__label">Pricing info key</span>
                                <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.72rem;word-break:break-all;">{{ $fare['pricing_info_key'] }}</span>
                            </div>
                        @endif
                        @if(!empty($fare['base_price']))
                            <div class="bkpd-info-row"><span class="bkpd-info-row__label">Base</span><span class="bkpd-info-row__val">{{ $fare['base_price'] }}</span></div>
                        @endif
                        @if(!empty($fare['taxes']))
                            <div class="bkpd-info-row"><span class="bkpd-info-row__label">Taxes total</span><span class="bkpd-info-row__val">{{ $fare['taxes'] }}</span></div>
                        @endif
                        @if(!empty($fare['pricing_method']))
                            <div class="bkpd-info-row"><span class="bkpd-info-row__label">Pricing method</span><span class="bkpd-info-row__val">{{ $fare['pricing_method'] }}</span></div>
                        @endif
                        @if(!empty($fare['pricing_type']))
                            <div class="bkpd-info-row"><span class="bkpd-info-row__label">Pricing type</span><span class="bkpd-info-row__val">{{ $fare['pricing_type'] }}</span></div>
                        @endif
                        @if(!empty($fare['e_ticketability']))
                            <div class="bkpd-info-row"><span class="bkpd-info-row__label">E-ticketable</span><span class="bkpd-info-row__val">{{ $fare['e_ticketability'] }}</span></div>
                        @endif
                        @if(!empty($fare['booking_code']) || !empty($fare['cabin_class']))
                            <div class="bkpd-info-row">
                                <span class="bkpd-info-row__label">Class / cabin</span>
                                <span class="bkpd-info-row__val">{{ trim(($fare['booking_code'] ?? '') . ' / ' . ($fare['cabin_class'] ?? ''), ' /') }}</span>
                            </div>
                        @endif
                        @if(!empty($fare['fare_calculation']))
                            <div class="bkpd-info-row">
                                <span class="bkpd-info-row__label">Fare calculation</span>
                                <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.72rem;">{{ $fare['fare_calculation'] }}</span>
                            </div>
                        @endif
                    </div>

                    @if(!empty($fare['tax_items']))
                        <div class="bkpd-eticket-admin__table-wrap mt-2">
                            <table class="bkpd-eticket-admin__table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Tax description</th>
                                        <th>Amount</th>
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
                        @if(!empty($fareInfo['fare_basis']) || !empty($fareInfo['endorsements']))
                            <div class="bkpd-eticket-admin__subblock">
                                @if(!empty($fareInfo['fare_basis']))
                                    <div><strong>Fare basis:</strong> <code>{{ $fareInfo['fare_basis'] }}</code>
                                        @if(!empty($fareInfo['origin']) && !empty($fareInfo['destination']))
                                            · {{ $fareInfo['origin'] }}→{{ $fareInfo['destination'] }}
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($fareInfo['baggage']))
                                    <div><strong>Baggage:</strong> {{ $fareInfo['baggage'] }}</div>
                                @endif
                                @if(!empty($fareInfo['endorsements']))
                                    <div><strong>Endorsements:</strong> {{ implode(' · ', $fareInfo['endorsements']) }}</div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    {{-- Per-ticket documents --}}
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

            <div class="bkpd-info-rows bkpd-info-rows--compact">
                @foreach([
                    'gds_pnr' => 'GDS PNR',
                    'supplier_pnr' => 'Supplier PNR',
                    'air_reservation_locator' => 'Air reservation locator',
                    'plating_carrier' => 'Plating carrier',
                    'provider_code' => 'Provider',
                    'iata_number' => 'IATA number',
                    'pseudo_city_code' => 'PCC',
                    'passenger_dob' => 'Date of birth',
                    'passenger_gender' => 'Gender',
                    'issued_date' => 'Issued',
                    'refundable' => 'Refundability',
                    'exchangeable' => 'Exchangeable',
                    'fare_basis' => 'Fare basis',
                    'payment_amount' => 'Payment amount',
                    'total_price' => 'Ticket total',
                    'base_price' => 'Base fare',
                    'taxes' => 'Taxes total',
                    'fare_calculation' => 'Fare calculation',
                ] as $field => $label)
                    @if(!empty($ticket[$field]))
                        <div class="bkpd-info-row">
                            <span class="bkpd-info-row__label">{{ $label }}</span>
                            <span class="bkpd-info-row__val" @if(in_array($field, ['gds_pnr', 'supplier_pnr', 'air_reservation_locator', 'fare_basis', 'fare_calculation'], true)) style="font-family:monospace;font-size:.78rem;word-break:break-word;" @endif>{{ $ticket[$field] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            @php $pricing = is_array($ticket['pricing'] ?? null) ? $ticket['pricing'] : []; @endphp
            @if($pricing !== [])
                <div class="bkpd-eticket-admin__subblock">
                    <div class="bkpd-eticket-admin__section-title" style="margin-bottom:.35rem;">Pricing details</div>
                    <div class="bkpd-info-rows bkpd-info-rows--compact">
                        @foreach([
                            'pricing_info_key' => 'Pricing info key',
                            'pricing_method' => 'Pricing method',
                            'pricing_type' => 'Pricing type',
                            'e_ticketability' => 'E-ticketable',
                            'fare_calculation_ind' => 'Fare calc indicator',
                            'latest_ticketing_time' => 'Latest ticketing time',
                            'true_last_date_to_ticket' => 'Last date to ticket',
                            'approximate_base_price' => 'Approx. base',
                            'approximate_total_price' => 'Approx. total',
                            'booking_code' => 'Booking class',
                            'cabin_class' => 'Cabin',
                        ] as $field => $label)
                            @if(!empty($pricing[$field]))
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">{{ $label }}</span>
                                    <span class="bkpd-info-row__val" @if($field === 'pricing_info_key') style="font-family:monospace;font-size:.72rem;word-break:break-all;" @endif>{{ $pricing[$field] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if(!empty($pricing['tax_items']))
                        <div class="bkpd-eticket-admin__table-wrap mt-2">
                            <table class="bkpd-eticket-admin__table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Tax description</th>
                                        <th>Amount</th>
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
                        <div class="bkpd-eticket-admin__subblock" style="margin-top:.5rem;">
                            @if(!empty($fareInfo['fare_basis']))
                                <div><strong>Fare basis:</strong> <code>{{ $fareInfo['fare_basis'] }}</code></div>
                            @endif
                            @if(!empty($fareInfo['origin']) && !empty($fareInfo['destination']))
                                <div><strong>Market:</strong> {{ $fareInfo['origin'] }} → {{ $fareInfo['destination'] }}</div>
                            @endif
                            @if(!empty($fareInfo['effective_date']))
                                <div><strong>Effective:</strong> {{ $fareInfo['effective_date'] }}</div>
                            @endif
                            @if(!empty($fareInfo['not_valid_before']) || !empty($fareInfo['not_valid_after']))
                                <div><strong>Validity:</strong> {{ $fareInfo['not_valid_before'] ?? '—' }} – {{ $fareInfo['not_valid_after'] ?? '—' }}</div>
                            @endif
                            @if(!empty($fareInfo['baggage']))
                                <div><strong>Baggage:</strong> {{ $fareInfo['baggage'] }}</div>
                            @endif
                            @if(!empty($fareInfo['endorsements']))
                                <div><strong>Endorsements:</strong> {{ implode(' · ', $fareInfo['endorsements']) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($ticket['ssrs']))
                <div class="bkpd-eticket-admin__subblock">
                    <div class="bkpd-eticket-admin__section-title" style="margin-bottom:.35rem;">SSR / remarks</div>
                    <div class="bkpd-eticket-admin__table-wrap">
                        <table class="bkpd-eticket-admin__table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Carrier</th>
                                    <th>Text</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticket['ssrs'] as $ssr)
                                    <tr>
                                        <td style="font-family:monospace;">{{ $ssr['type'] ?? '—' }}</td>
                                        <td>{{ $ssr['status'] ?? '—' }}</td>
                                        <td>{{ $ssr['carrier'] ?? '—' }}</td>
                                        <td style="font-size:.75rem;word-break:break-word;">{{ $ssr['free_text'] ?? '—' }}</td>
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
                                    <th>Class</th>
                                    <th>Fare basis</th>
                                    <th>Valid</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticket['coupons'] as $coupon)
                                    <tr>
                                        <td>{{ $coupon['coupon_number'] ?? '—' }}</td>
                                        <td>{{ $coupon['flight'] ?? '—' }}</td>
                                        <td>{{ $coupon['route'] ?? '' }}</td>
                                        <td style="font-size:.75rem;">{{ $coupon['departure'] ?? '—' }}</td>
                                        <td>{{ $coupon['booking_class'] ?? '—' }}</td>
                                        <td style="font-family:monospace;font-size:.72rem;">{{ $coupon['fare_basis'] ?? '—' }}</td>
                                        <td style="font-size:.72rem;">
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
