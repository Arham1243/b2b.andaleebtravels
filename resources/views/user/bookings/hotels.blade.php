@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
<style>
/* Reuse bkt-* table styles (duplicated inline so this page is self-contained) */
.bkt { width: 100%; border-collapse: collapse; }
.bkt thead tr { background: #f5f7fa; border-bottom: 2px solid #e4e9f0; }
.bkt thead th {
    padding: 10px 14px; font-size: .7rem; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase; color: #8492a6;
    white-space: nowrap; text-align: left;
}
.bkt tbody tr { border-bottom: 1px solid #f0f3f8; transition: background .12s; }
.bkt tbody tr:last-child { border-bottom: none; }
.bkt tbody tr:hover { background: #fafbff; }
.bkt tbody tr.bkt-row--confirmed { border-left: 3px solid #34d399; }
.bkt tbody tr.bkt-row--cancelled { border-left: 3px solid #f87171; }
.bkt tbody tr.bkt-row--pending   { border-left: 3px solid #fb923c; }
.bkt tbody td { padding: 11px 14px; font-size: .82rem; vertical-align: middle; }
.bkt-logo-fallback {
    width: 34px; height: 34px; border-radius: 8px;
    border: 1px solid #e4e9f0; background: #f0fdf4;
    display: flex; align-items: center; justify-content: center;
    color: #16a34a; font-size: 1.1rem;
}
.bkt-route { font-weight: 800; color: #1a2540; font-size: .88rem; }
.bkt-dates { font-size: .72rem; color: #8492a6; margin-top: 2px; }
.bkt-num { font-weight: 700; color: var(--c-brand, #cd1b4f); font-size: .78rem; }
.bkt-created { font-size: .68rem; color: #b0bac8; margin-top: 2px; }
.bkt-amount { font-weight: 800; color: #1a2540; font-size: .88rem; white-space: nowrap; }
.bkt-view {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .78rem; font-weight: 700; color: var(--c-brand, #cd1b4f);
    text-decoration: none; padding: 5px 11px; border-radius: 6px;
    background: #fdf1f4; transition: all .12s; white-space: nowrap;
}
.bkt-view:hover { background: var(--c-brand, #cd1b4f); color: #fff; }
.bkt-wrap { background: #fff; border: 1px solid #e4e9f0; border-radius: 12px; overflow: hidden; }
.bkp-search-form { display: flex; align-items: center; gap: 0; }
.bkp-search-form input {
    border: 1px solid #e4e9f0; border-right: none; border-radius: 8px 0 0 8px;
    padding: 7px 12px; font-size: .82rem; outline: none; width: 200px;
}
.bkp-search-form button {
    border: 1px solid #e4e9f0; border-radius: 0 8px 8px 0; border-left: none;
    background: #f5f7fa; padding: 7px 10px; cursor: pointer; color: #8492a6; font-size: .9rem;
}
.bkp-search-form button:hover { background: var(--c-brand,#cd1b4f); color: #fff; border-color: var(--c-brand,#cd1b4f); }
.bkt-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-top: 1px solid #f0f3f8; font-size: .78rem; color: #8492a6;
}
.bkt-pagination .pagination { display: flex; gap: 4px; margin: 0; padding: 0; list-style: none; }
.bkt-pagination .page-item .page-link {
    display: flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e4e9f0;
    background: #fff; color: #4a5568; font-size: .78rem; text-decoration: none; transition: all .12s;
}
.bkt-pagination .page-item.active .page-link { background: var(--c-brand,#cd1b4f); color: #fff; border-color: var(--c-brand,#cd1b4f); }
.bkt-pagination .page-item.disabled .page-link { opacity: .4; pointer-events: none; }
</style>
@endsection

@section('content')
<div class="bkp">
    <div class="container">
        <div class="bkp-shell">

            @include('user.bookings._nav', ['activeSection' => 'hotels', 'counts' => $counts])

            <main class="bkp-main">

                <div class="bkp-header">
                    <div>
                        <h1 class="bkp-header__title"><i class="bx bxs-hotel"></i> Hotel Bookings</h1>
                        <p class="bkp-header__sub">{{ $hotelBookings->total() }} booking{{ $hotelBookings->total() !== 1 ? 's' : '' }} found</p>
                    </div>
                    <div class="bkp-header__actions">
                        <form class="bkp-search-form" method="GET" action="{{ route('user.bookings.hotels') }}">
                            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                            <input type="text" name="search" value="{{ $search }}" placeholder="Hotel name, booking #…">
                            <button type="submit"><i class="bx bx-search"></i></button>
                        </form>
                        <div class="bkp-filter-chips">
                            @foreach(['all' => 'All', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'pending' => 'Pending'] as $val => $label)
                                <a href="{{ route('user.bookings.hotels', array_filter(['status' => $val === 'all' ? null : $val, 'search' => $search ?: null])) }}"
                                   class="bkp-chip {{ $status === $val || ($val === 'all' && $status === 'all') ? 'active' : '' }}">
                                   {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if($hotelBookings->isEmpty())
                    <div class="bkp-empty">
                        <i class="bx bx-hotel"></i>
                        <p>No hotel bookings found.</p>
                        <a href="{{ route('user.hotels.index') }}" class="bkp-btn bkp-btn--primary">Search Hotels</a>
                    </div>
                @else

                <div class="bkt-wrap">
                    <table class="bkt">
                        <thead>
                            <tr>
                                <th style="width:42px;"></th>
                                <th>Hotel</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Nights</th>
                                <th>Booking #</th>
                                <th>Supplier</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($hotelBookings as $booking)
                        @php
                            $st     = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                            $nights = $booking->check_in_date && $booking->check_out_date
                                ? $booking->check_in_date->diffInDays($booking->check_out_date)
                                : null;
                        @endphp
                        <tr class="bkt-row--{{ $st }}">
                            {{-- Icon --}}
                            <td style="padding-right:0;">
                                <div class="bkt-logo-fallback"><i class="bx bxs-hotel"></i></div>
                            </td>

                            {{-- Hotel name --}}
                            <td>
                                <div class="bkt-route" style="font-size:.84rem;">
                                    {{ $booking->hotel_name ?? 'Hotel Booking' }}
                                </div>
                                <div class="bkt-dates">{{ ucfirst($booking->supplier ?? 'N/A') }}</div>
                            </td>

                            {{-- Check-in --}}
                            <td>
                                <div style="font-size:.8rem; color:#1a2540; font-weight:600;">
                                    {{ $booking->check_in_date?->format('d M Y') ?? '—' }}
                                </div>
                            </td>

                            {{-- Check-out --}}
                            <td>
                                <div style="font-size:.8rem; color:#1a2540; font-weight:600;">
                                    {{ $booking->check_out_date?->format('d M Y') ?? '—' }}
                                </div>
                            </td>

                            {{-- Nights --}}
                            <td style="font-size:.8rem; color:#4a5568; font-weight:700;">
                                {{ $nights ? $nights . ' night' . ($nights > 1 ? 's' : '') : '—' }}
                            </td>

                            {{-- Booking # --}}
                            <td>
                                <div class="bkt-num">{{ $booking->booking_number }}</div>
                                <div class="bkt-created">{{ $booking->created_at->format('d M Y') }}</div>
                            </td>

                            {{-- Supplier --}}
                            <td>
                                <span class="bkp-row__supplier">{{ strtoupper($booking->supplier ?? 'N/A') }}</span>
                            </td>

                            {{-- Amount --}}
                            <td>
                                <div class="bkt-amount">{!! formatPrice($booking->total_amount) !!}</div>
                                <div style="font-size:.68rem;color:#8492a6;">{{ ucfirst($booking->payment_method ?? '') }}</div>
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="bkp-badge bkp-badge--{{ $st }}">
                                    @if($st === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                    @elseif($st === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                    @else<i class="bx bx-dots-horizontal"></i> {{ ucfirst($st) }}
                                    @endif
                                </span>
                                <br>
                                <span class="bkp-badge bkp-badge--{{ $booking->payment_status }} mt-1">
                                    {{ ucfirst($booking->payment_status) }}
                                </span>
                            </td>

                            {{-- Action --}}
                            <td>
                                <a href="{{ route('user.bookings.hotels.detail', $booking->id) }}" class="bkt-view">
                                    View <i class="bx bx-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @if($hotelBookings->hasPages())
                    <div class="bkt-pagination">
                        <span>Showing {{ $hotelBookings->firstItem() }}–{{ $hotelBookings->lastItem() }} of {{ $hotelBookings->total() }}</span>
                        {{ $hotelBookings->links() }}
                    </div>
                    @endif
                </div>

                @endif

            </main>
        </div>
    </div>
</div>

{{-- Hotel cancel modal --}}
<div class="modal fade" id="cancelBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancellation Charges</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cancelBookingModalBody">
                <div class="text-center py-5">Loading…</div>
            </div>
        </div>
    </div>
</div>
@endsection
