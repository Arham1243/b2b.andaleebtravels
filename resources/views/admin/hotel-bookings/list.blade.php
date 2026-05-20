@extends('admin.layouts.main')

@push('css')
@include('user.bookings._styles')
<style>
.bkp--admin { padding: 0; min-height: auto; background: transparent; }
.bkp--admin .bkp-main { min-width: 0; }
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
    padding: 7px 12px; font-size: .82rem; outline: none; width: 220px;
}
.bkp-search-form button {
    border: 1px solid #e4e9f0; border-radius: 0 8px 8px 0; border-left: none;
    background: #f5f7fa; padding: 7px 10px; cursor: pointer; color: #8492a6; font-size: .9rem;
}
.bkp-search-form button:hover { background: var(--c-brand,#cd1b4f); color: #fff; border-color: var(--c-brand,#cd1b4f); }
.bkt-pagination {
    padding: 12px 16px; border-top: 1px solid #f0f3f8; font-size: .78rem; color: #8492a6;
}
.bkt-pagination nav { width: 100%; }
.bkt-pagination .pagination { display: flex; flex-wrap: wrap; gap: 4px; margin: 0; padding: 0; list-style: none; }
.bkt-pagination .page-item .page-link {
    display: flex; align-items: center; justify-content: center;
    min-width: 30px; min-height: 30px; padding: 4px 8px; border-radius: 6px;
    border: 1px solid #e4e9f0; background: #fff; color: #4a5568; font-size: .78rem;
    text-decoration: none; transition: all .12s; box-sizing: border-box;
}
.bkt-pagination .page-item.active .page-link { background: var(--c-brand,#cd1b4f); color: #fff; border-color: var(--c-brand,#cd1b4f); }
.bkt-pagination .page-item.disabled .page-link { opacity: .4; pointer-events: none; }
.bkt-vendor-link { color: var(--c-brand, #cd1b4f); font-weight: 600; text-decoration: none; font-size: .8rem; }
.bkt-vendor-link:hover { text-decoration: underline; }
.bkp-header__actions-top {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
</style>
@endpush

@section('content')
<div class="col-md-12">
    <div class="dashboard-content">
        {{ Breadcrumbs::render('admin.hotel-bookings.index') }}

        <div class="bkp bkp--admin">
            <div class="bkp-main">
                <div class="bkp-header">
                    <div>
                        <h1 class="bkp-header__title"><i class="bx bx-restaurant"></i> Hotel Bookings</h1>
                        <p class="bkp-header__sub">
                            {{ $bookings->total() }} booking{{ $bookings->total() !== 1 ? 's' : '' }} found
                            @if (isset($filterVendor) && $filterVendor)
                                &middot; Vendor: <strong>{{ $filterVendor->name }}</strong>
                                &middot; <a href="{{ route('admin.vendors.show', $filterVendor) }}">Profile</a>
                                &middot; <a href="{{ route('admin.hotel-bookings.index') }}">Clear filter</a>
                            @endif
                        </p>
                    </div>
                    <div class="bkp-header__actions">
                        <div class="bkp-header__actions-top">
                            <a href="{{ route('admin.hotels.start') }}" class="bkp-btn bkp-btn--primary">
                                <i class="bx bx-search"></i> Search Hotels
                            </a>
                            <form class="bkp-search-form" method="GET" action="{{ route('admin.hotel-bookings.index') }}">
                                @if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                                @if (isset($filterVendor) && $filterVendor)<input type="hidden" name="vendor_id" value="{{ $filterVendor->id }}">@endif
                                <input type="text" name="search" value="{{ $search }}" placeholder="Hotel, booking #, vendor…">
                                <button type="submit"><i class="bx bx-search"></i></button>
                            </form>
                        </div>
                        <div class="bkp-filter-chips">
                            @foreach (['all' => 'All', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'pending' => 'Pending'] as $val => $label)
                                <a href="{{ route('admin.hotel-bookings.index', array_filter([
                                    'status' => $val === 'all' ? null : $val,
                                    'search' => $search ?: null,
                                    'vendor_id' => isset($filterVendor) && $filterVendor ? $filterVendor->id : null,
                                ])) }}"
                                   class="bkp-chip {{ $status === $val || ($val === 'all' && $status === 'all') ? 'active' : '' }}">
                                   {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($bookings->isEmpty())
                    <div class="bkp-empty">
                        <i class="bx bx-hotel"></i>
                        <p>No hotel bookings found.</p>
                        <a href="{{ route('admin.hotels.start') }}" class="bkp-btn bkp-btn--primary">Search Hotels</a>
                    </div>
                @else
                    <div class="bkt-wrap">
                        <table class="bkt">
                            <thead>
                                <tr>
                                    <th style="width:42px;"></th>
                                    <th>Hotel</th>
                                    <th>Vendor</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Nights</th>
                                    <th>Booking #</th>
                                    <th>Supplier</th>
                                    <th>Amount</th>
                                    <th>Booking</th>
                                    <th>Payment</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $booking)
                                    @php
                                        $st = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                                        $nights = $booking->check_in_date && $booking->check_out_date
                                            ? $booking->check_in_date->diffInDays($booking->check_out_date)
                                            : null;
                                    @endphp
                                    <tr class="bkt-row--{{ $st }}">
                                        <td style="padding-right:0;">
                                            <div class="bkt-logo-fallback"><i class="bx bx-restaurant"></i></div>
                                        </td>
                                        <td>
                                            <div class="bkt-route" style="font-size:.84rem;">
                                                {{ $booking->hotel_name ?? 'Hotel Booking' }}
                                            </div>
                                            <div class="bkt-dates">{{ formatBookingSupplierLabel($booking->supplier) }}</div>
                                        </td>
                                        <td>
                                            @if ($booking->vendor)
                                                <a href="{{ route('admin.vendors.show', $booking->vendor) }}" class="bkt-vendor-link">
                                                    {{ $booking->vendor->name }}
                                                </a>
                                                <div class="bkt-dates">{{ $booking->vendor->email }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <div style="font-size:.8rem; color:#1a2540; font-weight:600;">
                                                {{ $booking->check_in_date?->format('d M Y') ?? ' - ' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size:.8rem; color:#1a2540; font-weight:600;">
                                                {{ $booking->check_out_date?->format('d M Y') ?? ' - ' }}
                                            </div>
                                        </td>
                                        <td style="font-size:.8rem; color:#4a5568; font-weight:700;">
                                            {{ $nights ? $nights . ' night' . ($nights > 1 ? 's' : '') : ' - ' }}
                                        </td>
                                        <td>
                                            <div class="bkt-num">{{ $booking->booking_number }}</div>
                                            <div class="bkt-created">{{ $booking->created_at->format('d M Y') }}</div>
                                        </td>
                                        <td>
                                            <span class="bkp-row__supplier">{{ formatBookingSupplierLabel($booking->supplier) }}</span>
                                        </td>
                                        <td>
                                            <div class="bkt-amount"><span class="dirham">D</span> {{ number_format((float) $booking->total_amount, 2) }}</div>
                                            <div style="font-size:.68rem;color:#8492a6;">{{ ucfirst($booking->payment_method ?? '') }}</div>
                                        </td>
                                        <td>
                                            <span class="bkp-badge bkp-badge--{{ $st }}">
                                                @if ($st === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                                @elseif ($st === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                                @elseif ($st === 'failed')<i class="bx bx-error-circle"></i> Failed
                                                @else<i class="bx bx-dots-horizontal"></i> {{ ucfirst($st) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $ps = $booking->payment_status ?? 'pending';
                                                $psLabel = match ($ps) {
                                                    'paid' => ['icon' => 'bx-check-circle', 'text' => 'Paid', 'class' => 'bkp-badge--paid'],
                                                    'pending' => ['icon' => 'bx-time', 'text' => 'Pending', 'class' => 'bkp-badge--pending'],
                                                    'failed' => ['icon' => 'bx-error-circle', 'text' => 'Failed', 'class' => 'bkp-badge--failed'],
                                                    'refunded' => ['icon' => 'bx-revision', 'text' => 'Refunded', 'class' => 'bkp-badge--ticket'],
                                                    default => ['icon' => 'bx-dots-horizontal', 'text' => ucfirst($ps), 'class' => 'bkp-badge--pending'],
                                                };
                                            @endphp
                                            <span class="bkp-badge {{ $psLabel['class'] }}">
                                                <i class="bx {{ $psLabel['icon'] }}"></i> {{ $psLabel['text'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.hotel-bookings.show', $booking->id) }}" class="bkt-view">
                                                View <i class="bx bx-chevron-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($bookings->hasPages())
                            <div class="bkt-pagination">
                                {{ $bookings->links() }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('user.bookings._search-autosubmit')
@endpush
