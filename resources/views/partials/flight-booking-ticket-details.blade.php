@if(!empty($ticketDetails['tickets']))
<div class="bkpd-card mb-3">
    <div class="bkpd-card__section-head bkpd-card__section-head--green">
        <i class="bx bx-receipt"></i> E-Ticket Details
        @if(!empty($ticketDetails['source']) && $ticketDetails['source'] === 'live')
            <span class="bkpd-ticket-source">Live from {{ $booking->providerLabel() }}</span>
        @endif
    </div>

    @if(!empty($ticketDetails['error']) && ($ticketDetails['source'] ?? '') === 'fallback')
        <div class="bkpd-ticket-note">
            <i class="bx bx-info-circle"></i>
            Full ticket document could not be refreshed from {{ $booking->providerLabel() }}.
            Showing saved ticket numbers and itinerary segments.
        </div>
    @endif

    @foreach($ticketDetails['tickets'] as $ticket)
        <div class="bkpd-ticket">
            <div class="bkpd-ticket__head">
                <div>
                    <div class="bkpd-ticket__number">{{ $ticket['ticket_number'] ?? '—' }}</div>
                    @if(!empty($ticket['passenger_name']))
                        <div class="bkpd-ticket__pax">
                            {{ $ticket['passenger_name'] }}
                            @if(!empty($ticket['passenger_type']))
                                <span class="bkpd-ticket__pax-type">{{ $ticket['passenger_type'] }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                @if(!empty($ticket['ticket_status']))
                    <span class="bkp-badge bkp-badge--ticket">{{ $ticket['ticket_status'] }}</span>
                @endif
            </div>

            <div class="bkpd-info-rows bkpd-info-rows--compact">
                @if(!empty($ticket['pnr']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">PNR</span>
                        <span class="bkpd-info-row__val" style="font-family:monospace;font-weight:700;">{{ $ticket['pnr'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['plating_carrier']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Plating carrier</span>
                        <span class="bkpd-info-row__val">{{ $ticket['plating_carrier'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['issued_date']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Issued</span>
                        <span class="bkpd-info-row__val">{{ $ticket['issued_date'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['refundable']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Refundability</span>
                        <span class="bkpd-info-row__val">{{ $ticket['refundable'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['fare_basis']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Fare basis</span>
                        <span class="bkpd-info-row__val" style="font-family:monospace;">{{ $ticket['fare_basis'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['total_price']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Ticket total</span>
                        <span class="bkpd-info-row__val">{{ $ticket['total_price'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['base_price']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Base fare</span>
                        <span class="bkpd-info-row__val">{{ $ticket['base_price'] }}</span>
                    </div>
                @endif
                @if(!empty($ticket['taxes']))
                    <div class="bkpd-info-row">
                        <span class="bkpd-info-row__label">Taxes</span>
                        <span class="bkpd-info-row__val">{{ $ticket['taxes'] }}</span>
                    </div>
                @endif
            </div>

            @if(!empty($ticket['coupons']))
                <div class="bkpd-ticket__coupons">
                    <div class="bkpd-ticket__coupons-title">Flight coupons</div>
                    <div class="bkpd-ticket-coupon-grid">
                        @foreach($ticket['coupons'] as $coupon)
                            <div class="bkpd-ticket-coupon">
                                <div class="bkpd-ticket-coupon__flight">{{ $coupon['flight'] ?? '—' }}</div>
                                <div class="bkpd-ticket-coupon__route">{{ $coupon['route'] ?? '' }}</div>
                                @if(!empty($coupon['departure']))
                                    <div class="bkpd-ticket-coupon__meta">{{ $coupon['departure'] }}</div>
                                @endif
                                <div class="bkpd-ticket-coupon__meta">
                                    @if(!empty($coupon['booking_class']))
                                        Class {{ $coupon['booking_class'] }}
                                    @endif
                                    @if(!empty($coupon['fare_basis']))
                                        · {{ $coupon['fare_basis'] }}
                                    @endif
                                    @if(!empty($coupon['status']))
                                        · {{ $coupon['status'] }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
@endif
