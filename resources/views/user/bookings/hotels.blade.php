@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
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
                        <p class="bkp-header__sub">{{ $hotelBookings->count() }} booking{{ $hotelBookings->count() !== 1 ? 's' : '' }} found</p>
                    </div>
                    <div class="bkp-header__actions">
                        <div class="bkp-search-box">
                            <i class="bx bx-search"></i>
                            <input type="text" id="htSearch" placeholder="Search hotel name, booking #…">
                        </div>
                        <div class="bkp-filter-chips" id="htStatusFilter">
                            <button class="bkp-chip active" data-status="all">All</button>
                            <button class="bkp-chip" data-status="confirmed">Confirmed</button>
                            <button class="bkp-chip" data-status="cancelled">Cancelled</button>
                            <button class="bkp-chip" data-status="pending">Pending</button>
                        </div>
                    </div>
                </div>

                @if($hotelBookings->isEmpty())
                    <div class="bkp-empty">
                        <i class="bx bx-hotel"></i>
                        <p>No hotel bookings yet.</p>
                        <a href="{{ route('user.hotels.index') }}" class="bkp-btn bkp-btn--primary">Search Hotels</a>
                    </div>
                @else

                <div id="htList">
                    @foreach($hotelBookings as $booking)
                    @php
                        $status   = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                        $nights   = $booking->check_in_date && $booking->check_out_date
                            ? $booking->check_in_date->diffInDays($booking->check_out_date)
                            : null;
                        $searchStr = strtolower($booking->booking_number . ' ' . ($booking->hotel_name ?? ''));
                    @endphp
                    <div class="bkp-row"
                         data-status="{{ $status }}"
                         data-search="{{ $searchStr }}">
                        <div class="bkp-row__main">

                            {{-- Hotel icon --}}
                            <div class="bkp-row__logo-wrap">
                                <div class="bkp-row__logo-fallback bkp-row__logo-fallback--hotel" style="display:flex;">
                                    <i class="bx bxs-hotel"></i>
                                </div>
                            </div>

                            {{-- Hotel info --}}
                            <div class="bkp-row__route">
                                <div class="bkp-row__cities" style="font-size:.95rem;">
                                    {{ $booking->hotel_name ?? 'Hotel Booking' }}
                                </div>
                                <div class="bkp-row__dates">
                                    <i class="bx bx-calendar"></i>
                                    {{ $booking->check_in_date?->format('d M Y') ?? '—' }}
                                    &nbsp;–&nbsp;
                                    {{ $booking->check_out_date?->format('d M Y') ?? '—' }}
                                    @if($nights) &nbsp;·&nbsp; {{ $nights }} night{{ $nights > 1 ? 's' : '' }} @endif
                                </div>
                            </div>

                            {{-- Booking info --}}
                            <div class="bkp-row__meta">
                                <div class="bkp-row__num">{{ $booking->booking_number }}</div>
                                <div class="bkp-row__date">
                                    <span class="bkp-row__supplier">{{ ucfirst($booking->supplier ?? 'Yalago') }}</span>
                                </div>
                                <div class="bkp-row__date">{{ $booking->created_at->format('d M Y, h:i A') }}</div>
                            </div>

                            {{-- Amount --}}
                            <div class="bkp-row__amount">
                                <div class="bkp-row__price">{!! formatPrice($booking->total_amount) !!}</div>
                                <div style="font-size:.7rem;color:#8492a6;">{{ ucfirst($booking->payment_method ?? 'N/A') }}</div>
                            </div>

                            {{-- Status --}}
                            <div class="bkp-row__status">
                                <span class="bkp-badge bkp-badge--{{ $status }}">
                                    @if($status === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                    @elseif($status === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                    @else<i class="bx bx-dots-horizontal"></i> {{ ucfirst($status) }}
                                    @endif
                                </span>
                                <span class="bkp-badge bkp-badge--{{ $booking->payment_status }}">
                                    {{ ucfirst($booking->payment_status) }}
                                </span>
                            </div>

                            <a href="{{ route('user.bookings.hotels.detail', $booking->id) }}" class="bkp-row__view">
                                View <i class="bx bx-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="bkp-empty" id="htNoResults" style="display:none;">
                    <i class="bx bx-search-alt"></i>
                    <p>No bookings match your filter.</p>
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

@push('js')
<script>
const htRows = document.querySelectorAll('.bkp-row');
function htFilter() {
    const status = document.querySelector('#htStatusFilter .bkp-chip.active')?.dataset.status ?? 'all';
    const term   = (document.getElementById('htSearch')?.value ?? '').toLowerCase().trim();
    let vis = 0;
    htRows.forEach(r => {
        const ok = (status === 'all' || r.dataset.status === status)
                && (!term || (r.dataset.search ?? '').includes(term));
        r.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    document.getElementById('htNoResults').style.display = vis === 0 ? '' : 'none';
}
document.getElementById('htSearch')?.addEventListener('input', htFilter);
document.querySelectorAll('#htStatusFilter .bkp-chip').forEach(c => {
    c.addEventListener('click', () => {
        document.querySelectorAll('#htStatusFilter .bkp-chip').forEach(x => x.classList.remove('active'));
        c.classList.add('active');
        htFilter();
    });
});
</script>
@endpush
