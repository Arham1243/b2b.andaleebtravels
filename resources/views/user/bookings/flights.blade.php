@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
<style>
/* ── Bookings Table ──────────────────────────────────────── */
.bkt { width: 100%; border-collapse: collapse; }

.bkt thead tr {
    background: #f5f7fa;
    border-bottom: 2px solid #e4e9f0;
}
.bkt thead th {
    padding: 10px 14px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: #8492a6;
    white-space: nowrap;
    text-align: left;
}

.bkt tbody tr {
    border-bottom: 1px solid #f0f3f8;
    transition: background .12s;
}
.bkt tbody tr:last-child { border-bottom: none; }
.bkt tbody tr:hover { background: #fafbff; }
.bkt tbody tr.bkt-row--hold { border-left: 3px solid #fbbf24; }
.bkt tbody tr.bkt-row--confirmed { border-left: 3px solid #34d399; }
.bkt tbody tr.bkt-row--cancelled { border-left: 3px solid #f87171; }

.bkt tbody td { padding: 11px 14px; font-size: .82rem; vertical-align: middle; }

/* airline logo cell */
.bkt-logo {
    width: 34px; height: 34px;
    border-radius: 8px; border: 1px solid #e4e9f0;
    object-fit: contain; padding: 2px;
    background: #fafbff;
}
.bkt-logo-fallback {
    width: 34px; height: 34px; border-radius: 8px;
    border: 1px solid #e4e9f0; background: #f5f7fa;
    display: flex; align-items: center; justify-content: center;
    color: #8492a6; font-size: 1.1rem;
}

/* route cell */
.bkt-route { font-weight: 800; color: #1a2540; font-size: .88rem; }
.bkt-route span { color: #8492a6; font-weight: 400; margin: 0 3px; }
.bkt-dates { font-size: .72rem; color: #8492a6; margin-top: 2px; }

/* booking # */
.bkt-num { font-weight: 700; color: var(--c-brand, #cd1b4f); font-size: .78rem; }
.bkt-pnr { font-family: monospace; font-size: .72rem; color: #4a5568; margin-top: 2px; }
.bkt-created { font-size: .68rem; color: #b0bac8; margin-top: 2px; }

/* amount */
.bkt-amount { font-weight: 800; color: #1a2540; font-size: .88rem; white-space: nowrap; }
.bkt-hold-tag {
    display: inline-block; background: #fef9c3; color: #92400e;
    font-size: .62rem; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-top: 3px;
}

/* view btn */
.bkt-view {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .78rem; font-weight: 700; color: var(--c-brand, #cd1b4f);
    text-decoration: none; padding: 5px 11px; border-radius: 6px;
    background: #fdf1f4; transition: all .12s; white-space: nowrap;
}
.bkt-view:hover { background: var(--c-brand, #cd1b4f); color: #fff; }

/* Table wrapper */
.bkt-wrap {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 12px;
    overflow: hidden;
}

/* Search form */
.bkp-search-form { display: flex; align-items: center; gap: 0; }
.bkp-search-form input {
    border: 1px solid #e4e9f0; border-right: none; border-radius: 8px 0 0 8px;
    padding: 7px 12px; font-size: .82rem; outline: none; width: 200px;
}
.bkp-search-form button {
    border: 1px solid #e4e9f0; border-radius: 0 8px 8px 0; border-left: none;
    background: #f5f7fa; padding: 7px 10px; cursor: pointer; color: #8492a6;
    font-size: .9rem;
}
.bkp-search-form button:hover { background: var(--c-brand,#cd1b4f); color: #fff; border-color: var(--c-brand,#cd1b4f); }

/* Pagination */
.bkt-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-top: 1px solid #f0f3f8; font-size: .78rem; color: #8492a6;
}
.bkt-pagination .pagination {
    display: flex; gap: 4px; margin: 0; padding: 0; list-style: none;
}
.bkt-pagination .page-item .page-link {
    display: flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e4e9f0;
    background: #fff; color: #4a5568; font-size: .78rem; text-decoration: none;
    transition: all .12s;
}
.bkt-pagination .page-item.active .page-link {
    background: var(--c-brand, #cd1b4f); color: #fff; border-color: var(--c-brand, #cd1b4f);
}
.bkt-pagination .page-item.disabled .page-link { opacity: .4; pointer-events: none; }
</style>
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
                        <p class="bkp-header__sub">{{ $flightBookings->total() }} booking{{ $flightBookings->total() !== 1 ? 's' : '' }} found</p>
                    </div>
                    <div class="bkp-header__actions">
                        {{-- Search (server-side) --}}
                        <form class="bkp-search-form" method="GET" action="{{ route('user.bookings.flights') }}">
                            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                            <input type="text" name="search" value="{{ $search }}" placeholder="Booking #, route, PNR…">
                            <button type="submit"><i class="bx bx-search"></i></button>
                        </form>
                        {{-- Status filter chips --}}
                        <div class="bkp-filter-chips">
                            @foreach(['all' => 'All', 'hold' => 'On Hold', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $val => $label)
                                <a href="{{ route('user.bookings.flights', array_filter(['status' => $val === 'all' ? null : $val, 'search' => $search ?: null])) }}"
                                   class="bkp-chip {{ $status === $val || ($val === 'all' && $status === 'all') ? 'active' : '' }}">
                                   {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if($flightBookings->isEmpty())
                    <div class="bkp-empty">
                        <i class="bx bx-plane-take-off"></i>
                        <p>No flight bookings found.</p>
                        <a href="{{ route('user.flights.index') }}" class="bkp-btn bkp-btn--primary">Search Flights</a>
                    </div>
                @else

                <div class="bkt-wrap">
                    <table class="bkt">
                        <thead>
                            <tr>
                                <th style="width:42px;"></th>
                                <th>Route</th>
                                <th>Travel Dates</th>
                                <th>Booking #</th>
                                <th>Pax</th>
                                <th>PNR</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($flightBookings as $booking)
                        @php
                            $st      = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                            $isHold  = $booking->payment_method === 'hold';
                            $isRound = !empty($booking->return_date);
                            $legs    = $booking->itinerary_data['legs'] ?? [];
                            $carrier = data_get($legs, '0.segments.0.carrier', '');
                            $totalPax = max(1, $booking->adults + $booking->children + $booking->infants);
                            $paxStr  = $booking->adults . 'A';
                            if ($booking->children) $paxStr .= '+' . $booking->children . 'C';
                            if ($booking->infants)  $paxStr .= '+' . $booking->infants . 'I';
                        @endphp
                        <tr class="bkt-row--{{ $st }}">
                            {{-- Airline logo --}}
                            <td style="padding-right:0;">
                                @if($carrier)
                                    <img src="https://pics.avs.io/60/60/{{ strtoupper($carrier) }}.png"
                                         class="bkt-logo"
                                         alt="{{ $carrier }}"
                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                    <div class="bkt-logo-fallback" style="display:none;"><i class="bx bx-plane"></i></div>
                                @else
                                    <div class="bkt-logo-fallback"><i class="bx bx-plane"></i></div>
                                @endif
                            </td>

                            {{-- Route --}}
                            <td>
                                <div class="bkt-route">
                                    {{ strtoupper($booking->from_airport ?? '—') }}
                                    <span>{{ $isRound ? '⇄' : '→' }}</span>
                                    {{ strtoupper($booking->to_airport ?? '—') }}
                                </div>
                                <div class="bkt-dates">{{ $isRound ? 'Round Trip' : 'One Way' }}</div>
                            </td>

                            {{-- Dates --}}
                            <td>
                                <div style="font-size:.8rem; color:#1a2540; font-weight:600;">
                                    {{ $booking->departure_date?->format('d M Y') ?? '—' }}
                                </div>
                                @if($isRound)
                                    <div class="bkt-dates">↩ {{ $booking->return_date->format('d M Y') }}</div>
                                @endif
                            </td>

                            {{-- Booking # --}}
                            <td>
                                <div class="bkt-num">{{ $booking->booking_number }}</div>
                                <div class="bkt-created">{{ $booking->created_at->format('d M Y') }}</div>
                            </td>

                            {{-- Pax --}}
                            <td style="font-size:.8rem; color:#4a5568; font-weight:600;">{{ $paxStr }}</td>

                            {{-- PNR --}}
                            <td>
                                @if($booking->sabre_record_locator)
                                    <span class="bkt-pnr" style="font-size:.8rem; font-weight:700; color:#1a2540;">
                                        {{ $booking->sabre_record_locator }}
                                    </span>
                                @else
                                    <span style="color:#b0bac8; font-size:.75rem;">—</span>
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td>
                                <div class="bkt-amount">AED {{ number_format((float)$booking->total_amount, 2) }}</div>
                                @if($isHold)
                                    <div class="bkt-hold-tag">Hold · Free</div>
                                @else
                                    <div style="font-size:.68rem;color:#8492a6;">{{ ucfirst($booking->payment_method ?? '') }}</div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="bkp-badge bkp-badge--{{ $st }}">
                                    @if($st === 'hold')<i class="bx bx-time-five"></i> On Hold
                                    @elseif($st === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                    @elseif($st === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                    @else<i class="bx bx-dots-horizontal"></i> {{ ucfirst($st) }}
                                    @endif
                                </span>
                                @if($booking->ticket_status)
                                    <br><span class="bkp-badge bkp-badge--ticket mt-1">
                                        <i class="bx bx-receipt"></i> {{ ucfirst($booking->ticket_status) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td>
                                <a href="{{ route('user.bookings.flights.detail', $booking->id) }}" class="bkt-view">
                                    View <i class="bx bx-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>

                    {{-- Pagination --}}
                    @if($flightBookings->hasPages())
                    <div class="bkt-pagination">
                        <span>Showing {{ $flightBookings->firstItem() }}–{{ $flightBookings->lastItem() }} of {{ $flightBookings->total() }}</span>
                        {{ $flightBookings->links() }}
                    </div>
                    @endif
                </div>

                @endif

            </main>
        </div>
    </div>
</div>
@endsection
