@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
@endsection

@section('content')
<div class="bkp">
    <div class="container">
        <div class="bkp-shell">

            @include('user.bookings._nav', ['activeSection' => 'flights', 'counts' => $counts])

            <main class="bkp-main">

                {{-- Page header --}}
                <div class="bkp-header">
                    <div>
                        <h1 class="bkp-header__title"><i class="bx bxs-plane-take-off"></i> Flight Bookings</h1>
                        <p class="bkp-header__sub">{{ $flightBookings->count() }} booking{{ $flightBookings->count() !== 1 ? 's' : '' }} found</p>
                    </div>
                    <div class="bkp-header__actions">
                        <div class="bkp-search-box">
                            <i class="bx bx-search"></i>
                            <input type="text" id="flSearch" placeholder="Search booking #, route…">
                        </div>
                        <div class="bkp-filter-chips" id="flStatusFilter">
                            <button class="bkp-chip active" data-status="all">All</button>
                            <button class="bkp-chip" data-status="hold">On Hold</button>
                            <button class="bkp-chip" data-status="confirmed">Confirmed</button>
                            <button class="bkp-chip" data-status="cancelled">Cancelled</button>
                        </div>
                    </div>
                </div>

                @if($flightBookings->isEmpty())
                    <div class="bkp-empty">
                        <i class="bx bx-plane-take-off"></i>
                        <p>No flight bookings yet.</p>
                        <a href="{{ route('user.flights.index') }}" class="bkp-btn bkp-btn--primary">Search Flights</a>
                    </div>
                @else

                {{-- Flight cards --}}
                <div id="flList">
                    @foreach($flightBookings as $booking)
                    @php
                        $status   = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                        $isHold   = $booking->payment_method === 'hold';
                        $isRound  = !empty($booking->return_date);
                        $legs     = $booking->itinerary_data['legs'] ?? [];
                        $carrier  = data_get($legs, '0.segments.0.carrier', '');
                        $totalPax = max(1, $booking->adults + $booking->children + $booking->infants);

                        $paxStr = $booking->adults . 'A';
                        if ($booking->children) $paxStr .= ' · ' . $booking->children . 'C';
                        if ($booking->infants)  $paxStr .= ' · ' . $booking->infants . 'I';

                        $searchStr = strtolower($booking->booking_number . ' ' . ($booking->from_airport ?? '') . ' ' . ($booking->to_airport ?? '') . ' ' . ($booking->sabre_record_locator ?? ''));
                    @endphp
                    <div class="bkp-row"
                         data-status="{{ $status }}"
                         data-search="{{ $searchStr }}">
                        <div class="bkp-row__main">
                            {{-- Airline logo --}}
                            <div class="bkp-row__logo-wrap">
                                @if($carrier)
                                <img src="https://pics.avs.io/60/60/{{ strtoupper($carrier) }}.png"
                                     class="bkp-row__logo"
                                     alt="{{ $carrier }}"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                @endif
                                <div class="bkp-row__logo-fallback" {{ $carrier ? 'style=display:none' : '' }}>
                                    <i class="bx bx-plane"></i>
                                </div>
                            </div>

                            {{-- Route --}}
                            <div class="bkp-row__route">
                                <div class="bkp-row__cities">
                                    <span class="bkp-row__city">{{ strtoupper($booking->from_airport ?? '—') }}</span>
                                    <span class="bkp-row__arrow">{{ $isRound ? '⇄' : '→' }}</span>
                                    <span class="bkp-row__city">{{ strtoupper($booking->to_airport ?? '—') }}</span>
                                </div>
                                <div class="bkp-row__dates">
                                    <i class="bx bx-calendar"></i>
                                    {{ $booking->departure_date?->format('d M Y') ?? '—' }}
                                    @if($isRound) &nbsp;–&nbsp; {{ $booking->return_date->format('d M Y') }} @endif
                                    &nbsp;·&nbsp; {{ $paxStr }}
                                </div>
                            </div>

                            {{-- Booking info --}}
                            <div class="bkp-row__meta">
                                <div class="bkp-row__num">{{ $booking->booking_number }}</div>
                                @if($booking->sabre_record_locator)
                                <div class="bkp-row__pnr">PNR: <strong>{{ $booking->sabre_record_locator }}</strong></div>
                                @endif
                                <div class="bkp-row__date">{{ $booking->created_at->format('d M Y, h:i A') }}</div>
                            </div>

                            {{-- Amount --}}
                            <div class="bkp-row__amount">
                                <div class="bkp-row__price"><span class="dirham">AED</span> {{ number_format((float)$booking->total_amount, 2) }}</div>
                                @if($isHold)
                                    <div class="bkp-row__hold-tag">Hold · Free</div>
                                @else
                                    <div style="font-size:.7rem;color:#8492a6;">{{ ucfirst($booking->payment_method ?? 'N/A') }}</div>
                                @endif
                            </div>

                            {{-- Status --}}
                            <div class="bkp-row__status">
                                <span class="bkp-badge bkp-badge--{{ $status }}">
                                    @if($status === 'hold')<i class="bx bx-time-five"></i> On Hold
                                    @elseif($status === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                    @elseif($status === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                    @else<i class="bx bx-dots-horizontal"></i> {{ ucfirst($status) }}
                                    @endif
                                </span>
                                @if($booking->ticket_status)
                                    <span class="bkp-badge bkp-badge--ticket">
                                        <i class="bx bx-receipt"></i> {{ ucfirst($booking->ticket_status) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Action --}}
                            <a href="{{ route('user.bookings.flights.detail', $booking->id) }}" class="bkp-row__view">
                                View <i class="bx bx-chevron-right"></i>
                            </a>
                        </div>

                        {{-- Hold warning strip --}}
                        @if($isHold && $status !== 'cancelled')
                        <div class="bkp-row__hold-strip">
                            <i class="bx bx-time-five"></i>
                            This booking is on hold. PNR <strong>{{ $booking->sabre_record_locator }}</strong> — complete ticketing before it expires.
                            <a href="{{ route('user.bookings.flights.detail', $booking->id) }}">View details →</a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <div class="bkp-empty" id="flNoResults" style="display:none;">
                    <i class="bx bx-search-alt"></i>
                    <p>No bookings match your filter.</p>
                </div>
                @endif

            </main>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
// search + filter
const flRows = document.querySelectorAll('.bkp-row');

function flFilter() {
    const status = document.querySelector('#flStatusFilter .bkp-chip.active')?.dataset.status ?? 'all';
    const term   = (document.getElementById('flSearch')?.value ?? '').toLowerCase().trim();
    let vis = 0;
    flRows.forEach(r => {
        const matchStatus = status === 'all' || r.dataset.status === status;
        const matchSearch = !term || (r.dataset.search ?? '').includes(term);
        r.style.display = matchStatus && matchSearch ? '' : 'none';
        if (matchStatus && matchSearch) vis++;
    });
    document.getElementById('flNoResults').style.display = vis === 0 ? '' : 'none';
}

document.getElementById('flSearch')?.addEventListener('input', flFilter);
document.querySelectorAll('#flStatusFilter .bkp-chip').forEach(c => {
    c.addEventListener('click', () => {
        document.querySelectorAll('#flStatusFilter .bkp-chip').forEach(x => x.classList.remove('active'));
        c.classList.add('active');
        flFilter();
    });
});
</script>
@endpush
