@extends('admin.layouts.main')

@push('css')
@include('user.bookings._styles')
<style>
@include('user.flights.partials.fare-rules-styles')
.bkp--admin { padding: 0; min-height: auto; background: transparent; }
.bkp--admin .bkp-main { min-width: 0; }
.admin-flight-status .form-check { margin-bottom: .35rem; }
.admin-flight-status .form-check-label { font-size: .82rem; color: #4a5568; }
.admin-booking-vendor .bkpd-info-row__val a { color: var(--c-brand, #cd1b4f); font-weight: 600; text-decoration: none; }
.admin-booking-vendor .bkpd-info-row__val a:hover { text-decoration: underline; }
.bkp--admin {
    --c-ink: #1a2540;
    --c-muted: #8492a6;
    --c-line: #e8ecf1;
    --c-line-inner: #eef1f5;
    --c-bg: #f8f9fb;
    --c-green: #10b981;
    --c-brand: #cd1b4f;
}
.bkp--admin .fd-rules { padding: .85rem 1.1rem 1rem; font-size: .82rem; }
.bkp--admin .fd-rules__route { font-size: .88rem; }
.bkp--admin .fd-rules__row { font-size: .78rem; }
.bkp--admin .fd-rules__section-title,
.bkp--admin .fd-rules__component-route { font-size: .68rem; }
.bkp--admin .fd-rules__list li { font-size: .78rem; }
.bkp--admin .fd-rules__notes p { font-size: .74rem; }
.bkp--admin .fd-rules__full .fd-rules__section-title { font-size: .72rem; margin-bottom: .45rem; }
.bkp--admin .fd-rules__full-status { font-size: .78rem; }
.bkp--admin .fd-rules__full-body {
    max-height: 320px;
    overflow-x: hidden;
    overflow-y: auto;
    font-size: .72rem;
    line-height: 1.5;
    scrollbar-width: thin;
    scrollbar-color: #b8c2ce #eef1f5;
}
.bkp--admin .fd-rules__full-route { font-size: .76rem; }
.bkp--admin .fd-rules__full-section h4 { font-size: .72rem; }
.bkp--admin .fd-rules__full-section p { font-size: .72rem; }
.bkp--admin .fd-rules__component-grid div span { font-size: .62rem; }
.bkp--admin .fd-rules__component-grid div strong { font-size: .78rem; }
.bkp--admin .fd-rules__full-body::-webkit-scrollbar {
    width: 8px;
}
.bkp--admin .fd-rules__full-body::-webkit-scrollbar-track {
    background: #eef1f5;
    border-radius: 8px;
}
.bkp--admin .fd-rules__full-body::-webkit-scrollbar-thumb {
    background: #b8c2ce;
    border-radius: 8px;
}
.bkp--admin .fd-rules__full-body::-webkit-scrollbar-thumb:hover {
    background: #9aa8b8;
}
.bkp--admin .fd-rules__full-toolbar { margin: .25rem 0 .45rem; }
.bkp--admin .fd-rules__full-filter { font-size: .78rem; padding: .4rem .55rem; }
.bkp--admin .fd-rules__component-grid div span,
.bkp--admin .fd-rules__component-grid div strong {
    display: block;
}
@include('user.flights.partials.hold-confirm-styles')
.bkp--admin .hp-fare-acc { margin-top: .65rem; }
.bkp--admin .bkpd-fare__pax-breakdown { margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed var(--c-line); }
@include('admin.flight-bookings.partials.eticket-admin-styles')
.bkpd-pnr-row--dual {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
}
.bkpd-ticket-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .15rem;
}
.bkpd-ticket-chip {
    display: inline-block;
    padding: .2rem .45rem;
    font-family: monospace;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .02em;
    color: #1a2540;
    background: #f4f6f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    line-height: 1.3;
}
</style>
@endpush

@section('content')
@php
    $status = $booking->displayBookingStatus();
    $isHold = $booking->isOnHold();
    $isRound = !empty($booking->return_date);
    $legs = $legs ?? ($booking->itinerary_data['legs'] ?? []);
    $passengers = $booking->passengers_data['passengers'] ?? [];
    $lead = $booking->passengers_data['lead'] ?? [];
    $totalPax = max(1, $booking->adults + $booking->children + $booking->infants);

    $paxStr = $booking->adults . ' Adult' . ($booking->adults > 1 ? 's' : '');
    if ($booking->children) {
        $paxStr .= ', ' . $booking->children . ' Child' . ($booking->children > 1 ? 'ren' : '');
    }
    if ($booking->infants) {
        $paxStr .= ', ' . $booking->infants . ' Infant' . ($booking->infants > 1 ? 's' : '');
    }

    $payMethod = strtolower((string) ($booking->payment_method ?? ''));
    $payLabel = match ($payMethod) {
        'payby' => 'Card (PayBy)',
        'tabby' => 'Tabby',
        'tamara' => 'Tamara',
        'wallet' => 'Wallet',
        'hold' => 'Hold',
        default => $payMethod !== '' ? ucfirst(str_replace('_', ' ', $payMethod)) : ($isHold ? 'Hold (Free)' : 'N/A'),
    };

    $ttl = null;
    $ttlIsEstimate = false;
    if ($isHold) {
        $ttlIsEstimate = $booking->holdExpiryIsEstimate();
        $ttl = $booking->displayHoldExpiresAt();
    }

    $nonRefundable = null;
    $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
    if (array_key_exists('non_refundable', $itinerary)) {
        $nonRefundable = (bool) ($itinerary['non_refundable'] ?? false);
    } else {
        $passengerFare = data_get($booking->search_response, 'groupedItineraryResponse.itineraryGroups.0.itineraries.0.pricingInformation.0.fare.passengerInfoList.0.passengerInfo');
        if (is_array($passengerFare) && array_key_exists('nonRefundable', $passengerFare)) {
            $nonRefundable = (bool) ($passengerFare['nonRefundable'] ?? false);
        }
    }

    $fmtMins = function (?int $m): string {
        if (!$m || $m < 1) {
            return '—';
        }
        $h = intdiv($m, 60);
        $r = $m % 60;

        return $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$r}m";
    };
    $canEditBooking = \App\Support\B2bAdminPortalUi::can('flight_bookings_edit');
    $adminDetails = $adminDetails ?? [];
    $pnrRefs = is_array(($adminEticketDetails ?? [])['pnr_references'] ?? null)
        ? ($adminEticketDetails['pnr_references'] ?? [])
        : \App\Support\FlightBookingAdminEticketPresenter::resolvePnrReferences($booking);
    $gdsPnr = strtoupper(trim((string) ($pnrRefs['gds_pnr'] ?? '')));
    $airReservationLocator = strtoupper(trim((string) ($pnrRefs['air_reservation_locator'] ?? '')));
    $supplierPnr = strtoupper(trim((string) ($pnrRefs['supplier_pnr'] ?? '')));
    $supplierCode = strtoupper(trim((string) ($pnrRefs['supplier_code'] ?? '')));
    $needsFulfillmentRetry = $canEditBooking
        && $booking->needsTicketFulfillmentRetry()
        && ! $booking->isCancelled()
        && ! $booking->isOnHold();
@endphp

<div class="col-md-12">
    <div class="dashboard-content">
        <div class="bkpd-page-toolbar">
            <div class="bkpd-page-toolbar__trail">
                {{ Breadcrumbs::render('admin.flight-bookings.show', $booking) }}
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 bkpd-toolbar-actions">
                @include('partials.flight-eticket-export', [
                    'booking' => $booking,
                    'exportRoute' => route('admin.flight-bookings.eticket-pdf', $booking->id),
                    'variant' => 'compact',
                    'toolbar' => true,
                ])

                @if ($booking->isTravelport() && is_array($booking->booking_response) && $booking->booking_response !== [])
                    <a href="{{ route('admin.flight-bookings.travelport-cert-logs', $booking->id) }}"
                       class="eticket-icon-btn eticket-icon-btn--muted eticket-icon-btn--link"
                       title="Download Travelport certification logs">
                        <i class="bx bx-archive-in"></i>
                        <span>Travelport cert logs</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="bkp bkp--admin">
            <div class="bkp-main">
                @if ($needsFulfillmentRetry)
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                        <i class="bx bx-error-circle fs-5 mt-1"></i>
                        <div>
                            <strong>Payment received - booking incomplete</strong>
                            <p class="mb-0 small">
                                This booking was paid but PNR creation or ticketing did not complete via {{ $booking->providerLabel() }}.
                                @if ($booking->sabre_record_locator)
                                    PNR <strong>{{ $booking->sabre_record_locator }}</strong> is on record; ticketing may still be pending.
                                @else
                                    No airline PNR is on record yet.
                                @endif
                                Use <strong>Retry Booking</strong> in Actions to re-run the same steps as the payment success flow.
                            </p>
                        </div>
                    </div>
                @endif
                @if ($isHold && $status !== 'cancelled' && $ttl)
                    <div class="bkpd-hold-expiry mb-3 {{ $ttl->isPast() ? 'bkpd-hold-expiry--expired' : '' }}">
                        <div class="bkpd-hold-expiry__icon">
                            <i class="bx {{ $ttl->isPast() ? 'bx-error-circle' : 'bx-time-five' }}"></i>
                        </div>
                        <div class="bkpd-hold-expiry__body">
                            <div class="bkpd-hold-expiry__title">
                                @if ($ttl->isPast())
                                    Hold expired
                                @else
                                    {{ $ttlIsEstimate ? 'Estimated hold expiry (1 hour)' : 'Hold expires' }}:
                                    <strong>{{ $ttl->format('D, d M Y \a\t h:i A') }}</strong>
                                @endif
                            </div>
                            <div class="bkpd-hold-expiry__meta">
                                PNR: <strong>{{ $booking->sabre_record_locator ?? '—' }}</strong>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bkpd-grid">
                    <div>
                        @include('admin.hotel-bookings.partials.supplier-booking-details')

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__head">
                                <div>
                                    <div class="bkpd-card__title">
                                        {{ strtoupper($booking->from_airport ?? '') }}
                                        {{ $isRound ? '⇄' : '→' }}
                                        {{ strtoupper($booking->to_airport ?? '') }}
                                    </div>
                                    <div class="bkpd-card__sub">{{ $booking->booking_number }}</div>
                                </div>
                                <div class="d-flex gap-2 ms-auto flex-wrap align-items-center">
                                    @include('admin.partials.flight-booking-provider-badge', ['booking' => $booking])
                                    <span class="bkp-badge bkp-badge--{{ $status }}">
                                        @if ($status === 'hold')
                                            <i class="bx bx-time-five"></i> On Hold
                                        @elseif ($status === 'confirmed')
                                            <i class="bx bx-check-circle"></i> Confirmed
                                        @elseif ($status === 'cancelled')
                                            <i class="bx bx-x-circle"></i> Cancelled
                                        @else
                                            {{ ucfirst($status) }}
                                        @endif
                                    </span>
                                    @if ($booking->ticket_status)
                                        <span class="bkp-badge bkp-badge--ticket">
                                            <i class="bx bx-receipt"></i> {{ ucfirst($booking->ticket_status) }}
                                        </span>
                                    @endif
                                    <span class="bkp-badge bkp-badge--{{ $booking->payment_status }}">
                                        {{ ucfirst($booking->payment_status) }}
                                    </span>
                                </div>
                            </div>

                            @if ($gdsPnr !== '' || $airReservationLocator !== '' || $supplierPnr !== '')
                                <div class="bkpd-pnr-row bkpd-pnr-row--dual">
                                    @if ($gdsPnr !== '')
                                        <div>
                                            <div class="bkpd-pnr-label">GDS PNR</div>
                                            <div class="bkpd-pnr-value">{{ $gdsPnr }}</div>
                                        </div>
                                    @endif
                                    @if ($booking->isTravelport() && $airReservationLocator !== '' && $airReservationLocator !== $gdsPnr)
                                        <div>
                                            <div class="bkpd-pnr-label">Air reservation</div>
                                            <div class="bkpd-pnr-value">{{ $airReservationLocator }}</div>
                                        </div>
                                    @endif
                                    @if ($supplierPnr !== '')
                                        <div>
                                            <div class="bkpd-pnr-label">Supplier PNR{{ $supplierCode !== '' ? ' (' . $supplierCode . ')' : '' }}</div>
                                            <div class="bkpd-pnr-value">{{ $supplierPnr }}</div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @if (!empty($adminDetails['ticket_numbers']))
                                <div class="bkpd-pnr-row">
                                    <div style="width:100%;">
                                        <div class="bkpd-pnr-label">Ticket number{{ count($adminDetails['ticket_numbers']) > 1 ? 's' : '' }}</div>
                                        <div class="bkpd-ticket-chips">
                                            @foreach ($adminDetails['ticket_numbers'] as $ticketNo)
                                                <span class="bkpd-ticket-chip">{{ $ticketNo }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (!empty($legs))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--blue"><i class="bx bxs-plane"></i> Flight route</div>
                                @foreach ($legs as $li => $leg)
                                    @php
                                        $segs = $leg['segments'] ?? [];
                                        $first = $segs[0] ?? [];
                                        $last = end($segs) ?: [];
                                        $stops = count($segs) - 1;
                                        $durMins = $leg['elapsedTime'] ?? null;
                                        $midApts = [];
                                        for ($si = 0; $si < count($segs) - 1; $si++) {
                                            $midApts[] = $segs[$si]['to'] ?? '';
                                        }
                                    @endphp
                                    <div class="bkpd-leg {{ $li > 0 ? 'bkpd-leg--border' : '' }}">
                                        <div class="bkpd-leg__label">
                                            @if ($li === 0)
                                                <i class="bx bx-right-top-arrow-circle"></i> Outbound
                                            @else
                                                <i class="bx bx-left-top-arrow-circle"></i> Return
                                            @endif
                                            <span class="bkpd-leg__date ms-auto">
                                                {{ $li === 0
                                                    ? ($booking->departure_date?->format('d M Y') ?? '')
                                                    : ($booking->return_date?->format('d M Y') ?? '') }}
                                            </span>
                                        </div>

                                        <div class="bkpd-leg__visual">
                                            @if (!empty($first['carrier']))
                                                <img src="https://pics.avs.io/60/60/{{ strtoupper($first['carrier']) }}.png"
                                                    class="bkpd-leg__logo" alt="{{ $first['carrier'] }}"
                                                    onerror="this.style.display='none'">
                                            @endif

                                            <div class="bkpd-leg__dep">
                                                <div class="bkpd-leg__clock">{{ $first['departure_clock'] ?? '—' }}</div>
                                                <div class="bkpd-leg__city">{{ strtoupper($first['from'] ?? '') }}</div>
                                                @if (!empty($first['departure_city']))
                                                    <div class="bkpd-leg__city-name">{{ $first['departure_city'] }}</div>
                                                @endif
                                            </div>

                                            <div class="bkpd-leg__bridge">
                                                <div class="bkpd-leg__dur">{{ $fmtMins($durMins) }}</div>
                                                <div class="bkpd-leg__track">
                                                    <span class="bkpd-leg__dot"></span>
                                                    @foreach ($midApts as $via)
                                                        <span class="bkpd-leg__line"></span>
                                                        <span class="bkpd-leg__via">{{ strtoupper($via) }}</span>
                                                    @endforeach
                                                    <span class="bkpd-leg__line"></span>
                                                    <span class="bkpd-leg__dot"></span>
                                                </div>
                                                <div class="bkpd-leg__stops {{ $stops === 0 ? 'bkpd-leg__stops--direct' : '' }}">
                                                    {{ $stops === 0 ? 'Non-stop' : $stops . ' Stop' . ($stops > 1 ? 's' : '') }}
                                                </div>
                                            </div>

                                            <div class="bkpd-leg__arr">
                                                <div class="bkpd-leg__clock">
                                                    {{ $last['arrival_clock'] ?? '—' }}
                                                    @if (!empty($last['next_day_hint']))
                                                        <sup style="color:#cd1b4f;font-size:.6rem;">+1</sup>
                                                    @endif
                                                </div>
                                                <div class="bkpd-leg__city">{{ strtoupper($last['to'] ?? '') }}</div>
                                                @if (!empty($last['arrival_city']))
                                                    <div class="bkpd-leg__city-name">{{ $last['arrival_city'] }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        @if (count($segs) > 1)
                                            <div class="bkpd-segs">
                                                @foreach ($segs as $seg)
                                                    <div class="bkpd-seg">
                                                        <span class="bkpd-seg__flight">{{ $seg['carrier_display'] ?? ($seg['carrier'] ?? '') }}</span>
                                                        <span class="bkpd-seg__route">{{ strtoupper($seg['from'] ?? '') }} → {{ strtoupper($seg['to'] ?? '') }}</span>
                                                        <span class="bkpd-seg__time">{{ $seg['departure_clock'] ?? '' }} – {{ $seg['arrival_clock'] ?? '' }}</span>
                                                        @if (!empty($seg['cabin_code']))
                                                            <span class="bkpd-seg__cabin">{{ $seg['cabin_code'] }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="bkpd-seg-single">
                                                Flight <strong>{{ $first['carrier_display'] ?? ($first['carrier'] ?? '') }}</strong>
                                                @if (!empty($first['cabin_code']))
                                                    · {{ $first['cabin_code'] }} class
                                                @endif
                                                @if (!empty($first['booking_code']))
                                                    · Fare class {{ $first['booking_code'] }}
                                                @endif
                                                @if (!empty($first['equipment']))
                                                    · {{ $first['equipment'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @include('admin.flight-bookings.partials.fare-product-details', ['adminDetails' => $adminDetails ?? []])

                        @if (!empty($passengers))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-group"></i> Passengers</div>
                                <div class="bkpd-pax-list">
                                    @foreach ($passengers as $pi => $pax)
                                        @php
                                            $fullName = strtoupper(trim(($pax['title'] ?? '') . ' ' . ($pax['first_name'] ?? '') . ' ' . ($pax['last_name'] ?? '')));
                                            $initials = strtoupper(substr($pax['first_name'] ?? 'P', 0, 1) . substr($pax['last_name'] ?? '', 0, 1));
                                            $paxType = match ($pax['type'] ?? 'ADT') {
                                                'ADT' => 'Adult',
                                                'C06', 'C11' => 'Child',
                                                'INF' => 'Infant',
                                                default => ucfirst($pax['type'] ?? ''),
                                            };
                                        @endphp
                                        <div class="bkpd-pax">
                                            <div class="bkpd-pax__avatar">{{ $initials }}</div>
                                            <div class="bkpd-pax__body">
                                                <div class="bkpd-pax__name">{{ $fullName }}</div>
                                                <div class="bkpd-pax__meta">
                                                    {{ $paxType }}
                                                    @if (!empty($pax['nationality']))
                                                        · {{ strtoupper($pax['nationality']) }}
                                                    @endif
                                                    @if (!empty($pax['issuing_country']))
                                                        · Issued {{ strtoupper($pax['issuing_country']) }}
                                                    @endif
                                                    @if (!empty($pax['dob']))
                                                        · DOB: {{ \Carbon\Carbon::parse($pax['dob'])->format('d M Y') }}
                                                        @if ($ageLabel = \App\Models\B2bSavedPassenger::ageLabelFromDob($pax['dob']))
                                                            · Age {{ $ageLabel }}
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                            @if (!empty($pax['passport_no']))
                                                <div class="bkpd-pax__passport">
                                                    <i class="bx bx-id-card"></i> {{ $pax['passport_no'] }}
                                                    @if (!empty($pax['passport_exp']))
                                                        <span style="font-size:.65rem;color:#8492a6;font-weight:500;">
                                                            exp {{ \Carbon\Carbon::parse($pax['passport_exp'])->format('d M Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @include('partials.flight-booking-ticket-details-admin', [
                            'booking' => $booking,
                            'adminEticketDetails' => $adminEticketDetails ?? ['has_content' => false],
                        ])

                        @include('partials.flight-booking-fare-rules', ['booking' => $booking])
                    </div>

                    <div>
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--green"><i class="bx bx-receipt"></i> Fare summary</div>
                            @include('partials.flight-booking-fare-summary', [
                                'booking' => $booking,
                                'fareBreakdown' => $fareBreakdown ?? null,
                                'isHold' => $isHold,
                                'showVendorDiscount' => true,
                                'holdDepositLabel' => true,
                                'totalLabel' => 'Amount',
                            ])
                        </div>

                        @include('admin.partials.booking-vendor-detail-card', ['vendor' => $booking->vendor])

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-info-circle"></i> Booking info</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booking #</span>
                                    <span class="bkpd-info-row__val" style="color:#cd1b4f;font-weight:700;">{{ $booking->booking_number }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Provider</span>
                                    <span class="bkpd-info-row__val">{{ $booking->providerLabel() }}</span>
                                </div>
                                @if (!empty($adminDetails['travelport_universal_locator']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Universal record</span>
                                        <span class="bkpd-info-row__val" style="font-family:monospace;font-weight:700;">{{ $adminDetails['travelport_universal_locator'] }}</span>
                                    </div>
                                @endif
                                @if ($booking->ticket_status === 'issued' && $booking->isPaid() && empty($adminDetails['ticket_numbers']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Ticket number(s)</span>
                                        <span class="bkpd-info-row__val" style="color:#b45309;font-weight:600;">Not returned by {{ $booking->providerLabel() }} yet — use Retry Booking or check the GDS for e-ticket documents.</span>
                                    </div>
                                @endif
                                @if ($nonRefundable !== null)
                                    <div class="bkpd-info-row bkpd-info-row--refund">
                                        <span class="bkpd-info-row__label">Fare refund</span>
                                        <div class="bkpd-info-row__val bkpd-info-row__val--refund">
                                            @if ($nonRefundable)
                                                <span class="bkpd-refund-pill bkpd-refund-pill--no"><i class="bx bx-x-circle"></i> Non-refundable fare</span>
                                                <p class="bkpd-refund-note">Selected at booking from {{ $booking->providerLabel() }} shop results. Airline change/cancel rules still apply.</p>
                                            @else
                                                <span class="bkpd-refund-pill bkpd-refund-pill--yes"><i class="bx bx-check-shield"></i> Refundable fare</span>
                                                <p class="bkpd-refund-note">Selected at booking from {{ $booking->providerLabel() }} shop results. Refund eligibility depends on airline fare rules and timing.</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Payment</span>
                                    <span class="bkpd-info-row__val">{{ $payLabel }}</span>
                                </div>
                                @if (!empty($adminDetails['payment_reference']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Payment reference</span>
                                        <span class="bkpd-info-row__val" style="font-family:monospace;word-break:break-all;">{{ $adminDetails['payment_reference'] }}</span>
                                    </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Passengers</span>
                                    <span class="bkpd-info-row__val">{{ $paxStr }}</span>
                                </div>
                                @if (!empty($lead['email']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Email</span>
                                        <span class="bkpd-info-row__val" style="word-break:break-all;">{{ $lead['email'] }}</span>
                                    </div>
                                @endif
                                @if (!empty($lead['phone']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Phone</span>
                                        <span class="bkpd-info-row__val">{{ $lead['phone'] }}</span>
                                    </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booked on</span>
                                    <span class="bkpd-info-row__val">{{ $booking->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @if (!empty($adminDetails['confirmation_email_sent_at']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Confirmation email</span>
                                        <span class="bkpd-info-row__val">{{ $adminDetails['confirmation_email_sent_at']->format('d M Y, h:i A') }}</span>
                                    </div>
                                @endif
                                @if ($booking->cancelled_at)
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Cancelled</span>
                                        <span class="bkpd-info-row__val" style="color:#b91c1c;">
                                            {{ formatBookingCancelledAt($booking->cancelled_at) ?? '—' }}
                                            @if ($booking->cancelled_by)
                                                (by {{ formatBookingCancelledByLabel($booking->cancelled_by) }})
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            $vendorLabel = $booking->vendor
                                ? ($booking->vendor->display_agency_name ?: $booking->vendor->name)
                                : 'Vendor';
                            $walletRefundAlreadyCredited = \App\Models\B2bWalletLedger::refundCreditExists(
                                \App\Models\B2bFlightBooking::class,
                                $booking->id
                            );
                        @endphp
                        @if ($canEditBooking)
                        <form action="{{ route('admin.bookings.flights.status', $booking->id) }}" method="POST"
                            class="bkpd-card mb-3 admin-flight-status admin-booking-status-form"
                            data-booking-number="{{ $booking->booking_number }}"
                            data-total-amount="{{ number_format((float) $booking->total_amount, 2) }}"
                            data-vendor-name="{{ e($vendorLabel) }}"
                            data-current-payment-status="{{ $booking->payment_status }}"
                            data-current-booking-status="{{ $booking->booking_status }}"
                            data-current-ticket-status="{{ $booking->ticket_status }}"
                            data-wallet-refunded="{{ $walletRefundAlreadyCredited ? '1' : '0' }}"
                            data-booking-type="flight">
                            @csrf
                            <input type="hidden" name="skip_wallet_refund" value="0" class="js-skip-wallet-refund">
                            <div class="bkpd-card__section-head"><i class="bx bx-edit"></i> Update status</div>
                            <div class="bkpd-actions" style="padding:0 16px 16px;">
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Payment status</div>
                                    @if ($booking->booking_status !== 'cancelled')
                                        @foreach (['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'] as $val => $label)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_status" id="payment_{{ $val }}" value="{{ $val }}"
                                                    {{ old('payment_status', $booking->payment_status) === $val ? 'checked' : '' }}>
                                                <label class="form-check-label" for="payment_{{ $val }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    @endif
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_status" id="payment_refunded" value="refunded"
                                            {{ old('payment_status', $booking->payment_status) === 'refunded' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="payment_refunded">Refunded</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Booking status</div>
                                    @if ($booking->booking_status !== 'cancelled')
                                        @foreach (['confirmed' => 'Confirmed', 'pending' => 'Pending', 'hold' => 'On hold'] as $val => $label)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="booking_status" id="booking_{{ $val }}" value="{{ $val }}"
                                                    {{ old('booking_status', $booking->booking_status) === $val ? 'checked' : '' }}>
                                                <label class="form-check-label" for="booking_{{ $val }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    @endif
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="booking_status" id="booking_refunded" value="refunded"
                                            {{ old('booking_status', $booking->booking_status) === 'refunded' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="booking_refunded">Refunded</label>
                                    </div>
                                    @if ($booking->booking_status !== 'cancelled')
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="booking_status" id="booking_completed" value="completed"
                                                {{ old('booking_status', $booking->booking_status) === 'completed' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="booking_completed">Completed</label>
                                        </div>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Ticket status</div>
                                    @foreach (['pending' => 'Pending', 'issued' => 'Issued', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $val => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="ticket_status" id="ticket_{{ $val }}" value="{{ $val }}"
                                                {{ old('ticket_status', $booking->ticket_status) === $val ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ticket_{{ $val }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="bkp-btn bkp-btn--primary w-100">Save changes</button>
                            </div>
                        </form>
                        @endif

                        <div class="bkpd-card">
                            <div class="bkpd-card__section-head"><i class="bx bx-cog"></i> Actions</div>
                            <div class="bkpd-actions">
                                @if ($canEditBooking)
                                @if ($status === 'cancelled')
                                    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
                                @elseif ($isHold)
                                    <form action="{{ route('admin.bookings.flights.release-hold', $booking->id) }}" method="POST"
                                        onsubmit="return confirm('Release hold on PNR {{ $booking->sabre_record_locator }}? The booking will be cancelled at the airline end.');">
                                        @csrf
                                        <button type="submit" class="bkp-btn bkp-btn--warning w-100">
                                            <i class="bx bx-x-circle"></i> Release Hold
                                        </button>
                                    </form>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
                                        Releases the PNR via {{ $booking->providerLabel() }} — no charges since no payment was made.
                                    </p>
                                @elseif ($needsFulfillmentRetry)
                                    <form action="{{ route('admin.bookings.flights.retry-fulfillment', $booking->id) }}" method="POST"
                                          onsubmit="return confirm('Retry PNR creation and ticketing for this paid booking?');">
                                        @csrf
                                        <button type="submit" class="bkp-btn bkp-btn--primary w-100">
                                            <i class="bx bx-refresh"></i> Retry Booking
                                        </button>
                                    </form>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
                                        Re-runs airline PNR and ticketing via {{ $booking->providerLabel() }}.
                                    </p>
                                @elseif ($status === 'confirmed' && $booking->payment_status === 'paid')
                                    @include('partials.admin-flight-booking-cancel-actions', [
                                        'booking' => $booking,
                                        'cancellation' => $cancellation,
                                        'status' => $status,
                                    ])
                                @else
                                    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No supplier cancel action available for this state.</p>
                                @endif
                                @else
                                    <p class="bkpd-no-action"><i class="bx bx-lock"></i> You do not have permission to manage this booking.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('user.flights.partials.fare-rules-scripts')
@include('admin.bookings.partials.status-form-confirm')
<script>
$(document).on('click', '.cancel-booking-btn', function() {
    const amount = @json(number_format((float) $booking->total_amount, 2));
    const vendor = @json($vendorLabel ?? 'the vendor');
    const message =
        'Cancel this flight booking at the airline?\n\n' +
        'If payment was collected, ' + amount + ' AED will be credited to ' + vendor + '\'s wallet.\n\n' +
        'Continue?';
    if (!confirm(message)) {
        return;
    }

    $.post("{{ route('admin.bookings.flights.cancel', $booking->id) }}", {
        _token: "{{ csrf_token() }}"
    })
        .done(function() {
            window.location.reload();
        })
        .fail(function(xhr) {
            alert(xhr.responseJSON?.message || 'Unable to cancel booking.');
        });
});
</script>
@endpush
