@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
@endsection

@section('content')
@php
    $status     = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
    $isHold     = $booking->payment_method === 'hold';
    $isRound    = !empty($booking->return_date);
    $legs       = $booking->itinerary_data['legs'] ?? [];
    $passengers = $booking->passengers_data['passengers'] ?? [];
    $lead       = $booking->passengers_data['lead'] ?? [];
    $totalPax   = max(1, $booking->adults + $booking->children + $booking->infants);

    $paxStr = $booking->adults . ' Adult' . ($booking->adults > 1 ? 's' : '');
    if ($booking->children) $paxStr .= ', ' . $booking->children . ' Child' . ($booking->children > 1 ? 'ren' : '');
    if ($booking->infants)  $paxStr .= ', ' . $booking->infants . ' Infant' . ($booking->infants > 1 ? 's' : '');

    $ttl = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline') ?? null;

    $fmtMins = function(?int $m): string {
        if (!$m || $m < 1) return '—';
        $h = intdiv($m, 60); $r = $m % 60;
        return $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$r}m";
    };
@endphp

<div class="bkp">
    <div class="container">
        <div class="bkp-shell">

            @include('user.bookings._nav', ['activeSection' => 'flights', 'counts' => $counts])

            <main class="bkp-main">

                {{-- Breadcrumb --}}
                <nav class="bkp-crumb">
                    <a href="{{ route('user.bookings.flights') }}"><i class="bx bx-chevron-left"></i> Flight Bookings</a>
                    <span>{{ $booking->booking_number }}</span>
                </nav>

                {{-- Hold banner --}}
                @if($isHold && $status !== 'cancelled')
                <div class="bkp-hold-banner">
                    <i class="bx bx-time-five"></i>
                    <div>
                        <div class="bkp-hold-banner__title">Booking On Hold — no payment charged</div>
                        <div class="bkp-hold-banner__text">
                            PNR <strong>{{ $booking->sabre_record_locator ?? '—' }}</strong> created on Sabre.
                            Held on <strong>{{ $booking->created_at->format('d M Y, h:i A') }}</strong>.
                            @if($ttl) Ticketing deadline: <strong>{{ $ttl }}</strong>. @else Ticketing window is typically <strong>1–24 hours</strong> — check with the airline. @endif
                        </div>
                    </div>
                </div>
                @endif

                <div class="bkpd-grid">

                    {{-- ── LEFT (detail) ── --}}
                    <div>

                        {{-- Booking header card --}}
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
                                    <span class="bkp-badge bkp-badge--{{ $status }}">
                                        @if($status === 'hold')<i class="bx bx-time-five"></i> On Hold
                                        @elseif($status === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                        @elseif($status === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                        @else {{ ucfirst($status) }}
                                        @endif
                                    </span>
                                    @if($booking->ticket_status)
                                    <span class="bkp-badge bkp-badge--ticket">
                                        <i class="bx bx-receipt"></i> {{ ucfirst($booking->ticket_status) }}
                                    </span>
                                    @endif
                                </div>
                            </div>

                            @if($booking->sabre_record_locator)
                            <div class="bkpd-pnr-row">
                                <div>
                                    <div class="bkpd-pnr-label">PNR / Record Locator</div>
                                    <div class="bkpd-pnr-value" id="detPnr">{{ $booking->sabre_record_locator }}</div>
                                </div>
                                <button class="bkpd-pnr-copy" onclick="copyPnr()" id="detCopyBtn" title="Copy PNR">
                                    <i class="bx bx-copy" id="detCopyIcon"></i>
                                </button>
                            </div>
                            @endif
                        </div>

                        {{-- Flight legs --}}
                        @if(!empty($legs))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head">
                                <i class="bx bx-plane"></i> Flight Route
                            </div>
                            @foreach($legs as $li => $leg)
                            @php
                                $segs     = $leg['segments'] ?? [];
                                $first    = $segs[0] ?? [];
                                $last     = end($segs);
                                $stops    = count($segs) - 1;
                                $durMins  = $leg['elapsedTime'] ?? null;
                                $midApts  = [];
                                for ($si = 0; $si < count($segs) - 1; $si++) {
                                    $midApts[] = $segs[$si]['to'] ?? '';
                                }
                            @endphp
                            <div class="bkpd-leg {{ $li > 0 ? 'bkpd-leg--border' : '' }}">
                                <div class="bkpd-leg__label">
                                    @if($li === 0) <i class="bx bx-right-top-arrow-circle"></i> Outbound
                                    @else <i class="bx bx-left-top-arrow-circle"></i> Return
                                    @endif
                                    <span class="bkpd-leg__date ms-auto">
                                        {{ $li === 0
                                            ? ($booking->departure_date?->format('d M Y') ?? '')
                                            : ($booking->return_date?->format('d M Y') ?? '') }}
                                    </span>
                                </div>

                                <div class="bkpd-leg__visual">
                                    {{-- Airline logo --}}
                                    @if(!empty($first['carrier']))
                                    <img src="https://pics.avs.io/60/60/{{ strtoupper($first['carrier']) }}.png"
                                         class="bkpd-leg__logo"
                                         alt="{{ $first['carrier'] }}"
                                         onerror="this.style.display='none'">
                                    @endif

                                    <div class="bkpd-leg__dep">
                                        <div class="bkpd-leg__clock">{{ $first['departure_clock'] ?? '—' }}</div>
                                        <div class="bkpd-leg__city">{{ strtoupper($first['from'] ?? '') }}</div>
                                        @if(!empty($first['departure_city']))
                                            <div class="bkpd-leg__city-name">{{ $first['departure_city'] }}</div>
                                        @endif
                                    </div>

                                    <div class="bkpd-leg__bridge">
                                        <div class="bkpd-leg__dur">{{ $fmtMins($durMins) }}</div>
                                        <div class="bkpd-leg__track">
                                            <span class="bkpd-leg__dot"></span>
                                            @foreach($midApts as $via)
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
                                            @if(!empty($last['next_day_hint'])) <sup style="color:#cd1b4f;font-size:.6rem;">+1</sup> @endif
                                        </div>
                                        <div class="bkpd-leg__city">{{ strtoupper($last['to'] ?? '') }}</div>
                                        @if(!empty($last['arrival_city']))
                                            <div class="bkpd-leg__city-name">{{ $last['arrival_city'] }}</div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Segment breakdown --}}
                                @if(count($segs) > 1)
                                <div class="bkpd-segs">
                                    @foreach($segs as $seg)
                                    <div class="bkpd-seg">
                                        <span class="bkpd-seg__flight">{{ $seg['carrier_display'] ?? ($seg['carrier'] ?? '') }}</span>
                                        <span class="bkpd-seg__route">{{ strtoupper($seg['from'] ?? '') }} → {{ strtoupper($seg['to'] ?? '') }}</span>
                                        <span class="bkpd-seg__time">{{ $seg['departure_clock'] ?? '' }} – {{ $seg['arrival_clock'] ?? '' }}</span>
                                        @if(!empty($seg['cabin_code']))
                                            <span class="bkpd-seg__cabin">{{ $seg['cabin_code'] }}</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="bkpd-seg-single">
                                    Flight <strong>{{ $first['carrier_display'] ?? ($first['carrier'] ?? '') }}</strong>
                                    @if(!empty($first['cabin_code'])) &nbsp;·&nbsp; {{ $first['cabin_code'] }} class @endif
                                    @if(!empty($first['booking_code'])) &nbsp;·&nbsp; Fare class {{ $first['booking_code'] }} @endif
                                    @if(!empty($first['equipment'])) &nbsp;·&nbsp; {{ $first['equipment'] }} @endif
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Passengers --}}
                        @if(!empty($passengers))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head">
                                <i class="bx bx-group"></i> Passengers
                            </div>
                            <div class="bkpd-pax-list">
                                @foreach($passengers as $pi => $pax)
                                @php
                                    $fullName = strtoupper(trim(($pax['title'] ?? '') . ' ' . ($pax['first_name'] ?? '') . ' ' . ($pax['last_name'] ?? '')));
                                    $initials = strtoupper(substr($pax['first_name'] ?? 'P', 0, 1) . substr($pax['last_name'] ?? '', 0, 1));
                                    $paxType  = match($pax['type'] ?? 'ADT') {
                                        'ADT'        => 'Adult',
                                        'C06', 'C11' => 'Child',
                                        'INF'        => 'Infant',
                                        default      => ucfirst($pax['type'] ?? ''),
                                    };
                                @endphp
                                <div class="bkpd-pax">
                                    <div class="bkpd-pax__avatar">{{ $initials }}</div>
                                    <div class="bkpd-pax__body">
                                        <div class="bkpd-pax__name">{{ $fullName }}</div>
                                        <div class="bkpd-pax__meta">
                                            {{ $paxType }}
                                            @if(!empty($pax['nationality'])) &bull; {{ strtoupper($pax['nationality']) }} @endif
                                            @if(!empty($pax['dob'])) &bull; DOB: {{ $pax['dob'] }} @endif
                                        </div>
                                    </div>
                                    @if(!empty($pax['passport_no']))
                                    <div class="bkpd-pax__passport">
                                        <i class="bx bx-id-card"></i> {{ $pax['passport_no'] }}
                                        @if(!empty($pax['passport_exp'])) &nbsp;(exp {{ $pax['passport_exp'] }}) @endif
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                    </div>{{-- end left --}}

                    {{-- ── RIGHT (summary + actions) ── --}}
                    <div>

                        {{-- Fare summary --}}
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head"><i class="bx bx-receipt"></i> Fare Summary</div>
                            <div class="bkpd-fare">
                                <div class="bkpd-fare__row">
                                    <span>Base Fare <span style="color:#8492a6;font-weight:400;">(× {{ $totalPax }} pax)</span></span>
                                    <span><span class="dirham">AED</span> {{ number_format((float)$booking->total_amount, 2) }}</span>
                                </div>
                                @if($isHold)
                                <div class="bkpd-fare__row">
                                    <span>Hold Deposit</span>
                                    <span style="color:#10b981;font-weight:800;">FREE</span>
                                </div>
                                @endif
                                <div class="bkpd-fare__row bkpd-fare__row--total">
                                    <span>Total</span>
                                    <span><span class="dirham">AED</span> {{ number_format((float)$booking->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Booking meta --}}
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head"><i class="bx bx-info-circle"></i> Booking Info</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booking #</span>
                                    <span class="bkpd-info-row__val" style="font-weight:700;color:#cd1b4f;">{{ $booking->booking_number }}</span>
                                </div>
                                @if($booking->sabre_record_locator)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">PNR</span>
                                    <span class="bkpd-info-row__val" style="font-family:monospace;font-weight:700;">{{ $booking->sabre_record_locator }}</span>
                                </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Payment</span>
                                    <span class="bkpd-info-row__val">{{ $isHold ? 'Hold (Free)' : ucfirst($booking->payment_method ?? 'N/A') }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Passengers</span>
                                    <span class="bkpd-info-row__val">{{ $paxStr }}</span>
                                </div>
                                @if(!empty($lead['email']))
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Email</span>
                                    <span class="bkpd-info-row__val">{{ $lead['email'] }}</span>
                                </div>
                                @endif
                                @if(!empty($lead['phone']))
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Phone</span>
                                    <span class="bkpd-info-row__val">{{ $lead['phone'] }}</span>
                                </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booked On</span>
                                    <span class="bkpd-info-row__val">{{ $booking->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @if($booking->cancelled_at)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Cancelled</span>
                                    <span class="bkpd-info-row__val" style="color:#b91c1c;">{{ $booking->cancelled_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="bkpd-card">
                            <div class="bkpd-card__section-head"><i class="bx bx-cog"></i> Actions</div>
                            <div class="bkpd-actions">
                                @if($status === 'cancelled')
                                    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
                                @elseif($isHold)
                                    <form action="{{ route('user.bookings.flights.release-hold', $booking->id) }}" method="POST"
                                          onsubmit="return confirm('Release hold on PNR {{ $booking->sabre_record_locator }}? The booking will be cancelled at the airline end.');">
                                        @csrf
                                        <button type="submit" class="bkp-btn bkp-btn--warning w-100">
                                            <i class="bx bx-x-circle"></i> Release Hold
                                        </button>
                                    </form>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:8px;text-align:center;">
                                        This cancels the PNR at Sabre — no charges since no payment was made.
                                    </p>
                                @elseif($status === 'confirmed' && $booking->payment_status === 'paid')
                                    <a href="{{ route('user.bookings.flights.cancel', $booking->id) }}"
                                       class="bkp-btn bkp-btn--danger w-100"
                                       onclick="return confirm('Cancel this confirmed booking? Cancellation charges may apply.');">
                                        <i class="bx bx-x"></i> Cancel Booking
                                    </a>
                                @else
                                    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No actions available.</p>
                                @endif
                            </div>
                        </div>

                    </div>{{-- end right --}}

                </div>{{-- end bkpd-grid --}}

            </main>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
function copyPnr() {
    const pnr  = document.getElementById('detPnr')?.innerText?.trim();
    const btn  = document.getElementById('detCopyBtn');
    const icon = document.getElementById('detCopyIcon');
    if (!pnr) return;
    navigator.clipboard.writeText(pnr).then(() => {
        btn.classList.add('copied');
        icon.className = 'bx bx-check';
        setTimeout(() => { btn.classList.remove('copied'); icon.className = 'bx bx-copy'; }, 2000);
    });
}
</script>
@endpush
