@extends('admin.layouts.main')

@push('css')
<style>
    .vendor-profile-card {
        background: linear-gradient(135deg, var(--color-primary) 0%, #1a237e 100%);
        border-radius: 16px;
        color: #fff;
        padding: 28px 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .vendor-profile-card::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .vendor-avatar {
        width: 64px; height: 64px;
        border-radius: 50%;
        background: rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 28px; font-weight: 700;
        flex-shrink: 0;
    }
    .vendor-stat-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px 22px;
        display: flex; align-items: center; gap: 16px;
        transition: box-shadow .2s;
    }
    .vendor-stat-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.08); }
    .vendor-stat-icon {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
    }
    .vendor-stat-card .stat-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
    .vendor-stat-card .stat-label { font-size: 12px; color: #6c757d; margin-top: 2px; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .info-item {
        padding: 11px 16px;
        border-bottom: 1px solid #f1f3f5;
        display: flex; align-items: flex-start; gap: 8px;
    }
    .info-item:nth-child(odd) { border-right: 1px solid #f1f3f5; }
    .info-item:last-child, .info-item:nth-last-child(2):nth-child(odd) { border-bottom: none; }
    .info-label { font-size: 11px; font-weight: 600; color: #868e96; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; min-width: 110px; padding-top: 1px; }
    .info-value { font-size: 14px; color: #212529; font-weight: 500; }

    /* Tabs */
    .vendor-tabs .nav-link {
        color: #495057; font-weight: 500; border-radius: 8px 8px 0 0; padding: 10px 20px;
        border: 1px solid transparent; margin-right: 3px;
    }
    .vendor-tabs .nav-link.active {
        color: var(--color-primary); background: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    .vendor-tabs .nav-link .tab-badge {
        font-size: 10px; padding: 2px 7px; margin-left: 6px; border-radius: 20px;
        background: #e9ecef; color: #495057;
    }
    .vendor-tabs .nav-link.active .tab-badge { background: var(--color-primary); color: #fff; }
    .tab-pane-inner { padding: 24px 0 0; }

    .payment-method-badge {
        display: inline-block; font-size: 10px; padding: 3px 8px;
        border-radius: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
    }
    .pm-wallet    { background:#e8f5e9; color:#2e7d32; }
    .pm-card      { background:#e3f2fd; color:#1565c0; }
    .pm-tabby     { background:#fff3e0; color:#e65100; }
    .pm-bank      { background:#f3e5f5; color:#6a1b9a; }
    .pm-cash      { background:#f1f8e9; color:#558b2f; }
    .pm-unknown   { background:#f5f5f5; color:#757575; }

    @media (max-width: 768px) {
        .info-grid { grid-template-columns: 1fr; }
        .info-item:nth-child(odd) { border-right: none; }
        .info-item { border-bottom: 1px solid #f1f3f5; }
    }
</style>
@endpush

@section('content')

<div class="col-md-12">
    <div class="dashboard-content">
        {{ Breadcrumbs::render('admin.vendors.show', $vendor) }}

        {{-- ── Profile Hero ─────────────────────────────────────────── --}}
        <div class="vendor-profile-card">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="vendor-avatar">{{ strtoupper(substr($vendor->name, 0, 1)) }}</div>
                <div class="flex-grow-1">
                    <h4 class="mb-0 fw-bold" style="font-size:22px;">{{ $vendor->name }}</h4>
                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <span style="font-size:13px; opacity:.85;"><i class="bx bx-envelope"></i> {{ $vendor->email }}</span>
                        <span style="opacity:.5;">·</span>
                        <span style="font-size:13px; opacity:.85;"><i class="bx bx-user"></i> {{ $vendor->username }}</span>
                        <span style="opacity:.5;">·</span>
                        <span style="font-size:13px; opacity:.85;"><i class="bx bx-id-card"></i> {{ $vendor->agent_code }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.vendors.change-status', $vendor->id) }}"
                        class="btn btn-sm {{ $vendor->status === 'active' ? 'btn-danger' : 'btn-success' }} fw-semibold px-3"
                        style="border-radius:8px;"
                        onclick="return confirm('Are you sure you want to {{ $vendor->status === 'active' ? 'deactivate' : 'activate' }} {{ addslashes($vendor->name) }}?')">
                        <i class="bx {{ $vendor->status === 'active' ? 'bx-pause' : 'bx-play' }}"></i>
                        {{ $vendor->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </a>
                </div>
            </div>
        </div>

        {{-- ── Stat Cards ───────────────────────────────────────────── --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="vendor-stat-card">
                    <div class="vendor-stat-icon" style="background:#e8f5e9;">
                        <i class="bx bx-wallet" style="color:#2e7d32;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{!! formatPrice($vendor->main_balance ?? 0) !!}</div>
                        <div class="stat-label">Wallet Balance</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="vendor-stat-card">
                    <div class="vendor-stat-icon" style="background:#e3f2fd;">
                        <i class="bx bxs-hotel" style="color:#1565c0;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['hotel_bookings'] }}</div>
                        <div class="stat-label">Hotel Bookings</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="vendor-stat-card">
                    <div class="vendor-stat-icon" style="background:#fff3e0;">
                        <i class="bx bxs-plane-alt" style="color:#e65100;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['flight_bookings'] }}</div>
                        <div class="stat-label">Flight Bookings</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="vendor-stat-card">
                    <div class="vendor-stat-icon" style="background:#fce4ec;">
                        <i class="bx bx-money-withdraw" style="color:#c62828;"></i>
                    </div>
                    <div>
                        <div class="stat-value">{!! formatPrice($stats['total_spent']) !!}</div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Vendor Details Key-Value ─────────────────────────────── --}}
        <div class="custom-sec mb-4">
            <div class="custom-sec__header">
                <div class="section-content">
                    <h3 class="heading">Vendor Details</h3>
                </div>
                <span class="badge rounded-pill {{ $vendor->status === 'active' ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                    <i class="bx {{ $vendor->status === 'active' ? 'bx-check-circle' : 'bx-x-circle' }}"></i>
                    {{ ucfirst($vendor->status) }}
                </span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value">{{ $vendor->name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Agent Code</span>
                    <span class="info-value"><code class="text-primary">{{ $vendor->agent_code }}</code></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value">{{ $vendor->email }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Username</span>
                    <span class="info-value">{{ $vendor->username }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Wallet Balance</span>
                    <span class="info-value fw-bold text-success">{!! formatPrice($vendor->main_balance ?? 0) !!}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registered</span>
                    <span class="info-value">{{ formatDateTime($vendor->created_at) }}</span>
                </div>
            </div>
        </div>

        {{-- ── Tabs ─────────────────────────────────────────────────── --}}
        <div class="custom-sec">
            <ul class="nav vendor-tabs" id="vendorTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="tab-wallet" data-bs-toggle="tab" data-bs-target="#panel-wallet" type="button">
                        <i class="bx bx-wallet"></i> Wallet Ledger
                        <span class="tab-badge">{{ $stats['ledger_entries'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-hotels" data-bs-toggle="tab" data-bs-target="#panel-hotels" type="button">
                        <i class="bx bxs-hotel"></i> Hotel Bookings
                        <span class="tab-badge">{{ $stats['hotel_bookings'] }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-flights" data-bs-toggle="tab" data-bs-target="#panel-flights" type="button">
                        <i class="bx bxs-plane-alt"></i> Flight Bookings
                        <span class="tab-badge">{{ $stats['flight_bookings'] }}</span>
                    </button>
                </li>
            </ul>
            <hr class="mt-0 mb-0">

            <div class="tab-content" id="vendorTabsContent">

                {{-- ── Wallet Ledger Tab ────────────────────────────── --}}
                <div class="tab-pane fade show active" id="panel-wallet" role="tabpanel">
                    <div class="tab-pane-inner">
                        @if ($walletLedger->isNotEmpty())
                            <div class="table-responsive">
                                <table class="data-table" id="walletTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Payment Method</th>
                                            <th>Amount</th>
                                            <th>Balance Before</th>
                                            <th>Balance After</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($walletLedger as $index => $entry)
                                            @php
                                                $pm = null;
                                                $pmClass = 'pm-unknown';
                                                $pmLabel = '—';
                                                if ($entry->reference instanceof \App\Models\B2bWalletRecharge) {
                                                    $pm = $entry->reference->payment_method;
                                                    $pmLabel = match($pm) {
                                                        'bank_transfer' => 'Bank Transfer',
                                                        'card'          => 'Card',
                                                        'tabby'         => 'Tabby',
                                                        'wallet'        => 'Wallet',
                                                        'cash'          => 'Cash',
                                                        default         => ucfirst(str_replace('_', ' ', $pm ?? '')),
                                                    };
                                                    $pmClass = match($pm) {
                                                        'bank_transfer' => 'pm-bank',
                                                        'card'          => 'pm-card',
                                                        'tabby'         => 'pm-tabby',
                                                        'wallet'        => 'pm-wallet',
                                                        'cash'          => 'pm-cash',
                                                        default         => 'pm-unknown',
                                                    };
                                                } elseif ($entry->reference_type) {
                                                    $pmLabel = 'System';
                                                    $pmClass = 'pm-unknown';
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td data-order="{{ $entry->created_at->timestamp }}" style="white-space:nowrap; font-size:12px;">
                                                    {{ $entry->created_at->format('d M Y') }}<br>
                                                    <small class="text-muted">{{ $entry->created_at->format('h:i A') }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ $entry->isCredit() ? 'success' : 'danger' }}">
                                                        <i class="bx {{ $entry->isCredit() ? 'bx-plus' : 'bx-minus' }}"></i>
                                                        {{ ucfirst($entry->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="payment-method-badge {{ $pmClass }}">{{ $pmLabel }}</span>
                                                </td>
                                                <td class="fw-bold {{ $entry->isCredit() ? 'text-success' : 'text-danger' }}">
                                                    {{ $entry->isCredit() ? '+' : '-' }}{!! formatPrice($entry->amount) !!}
                                                </td>
                                                <td>{!! formatPrice($entry->balance_before) !!}</td>
                                                <td class="fw-semibold">{!! formatPrice($entry->balance_after) !!}</td>
                                                <td>{{ $entry->description }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bx bx-wallet" style="font-size:44px; opacity:.4;"></i>
                                <p class="mt-2 mb-0">No wallet transactions yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Hotel Bookings Tab ───────────────────────────── --}}
                <div class="tab-pane fade" id="panel-hotels" role="tabpanel">
                    <div class="tab-pane-inner">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.hotel-bookings.index', ['vendor_id' => $vendor->id]) }}"
                                class="themeBtn" target="_blank">
                                <i class="bx bx-link-external"></i> Open in Hotel Bookings
                            </a>
                        </div>
                        @if ($hotelBookings->isNotEmpty())
                            <div class="table-responsive">
                                <table class="data-table" id="hotelTable">
                                    <thead>
                                        <tr>
                                            <th>Booking #</th>
                                            <th>Hotel</th>
                                            <th>Supplier</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Amount</th>
                                            <th>Wallet Used</th>
                                            <th>Pay Method</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <th>Booked On</th>
                                            <th class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($hotelBookings as $booking)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('admin.hotel-bookings.show', $booking->id) }}"
                                                        class="fw-semibold link" style="color: var(--color-primary);">
                                                        {{ $booking->booking_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span title="{{ $booking->hotel_name }}"
                                                        style="max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;">
                                                        {{ $booking->hotel_name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-uppercase" style="font-size:10px;">
                                                        {{ $booking->supplier ?? 'yalago' }}
                                                    </span>
                                                </td>
                                                <td style="white-space:nowrap; font-size:12px;">{{ $booking->check_in_date?->format('d M Y') }}</td>
                                                <td style="white-space:nowrap; font-size:12px;">{{ $booking->check_out_date?->format('d M Y') }}</td>
                                                <td class="fw-bold">{!! formatPrice($booking->total_amount) !!}</td>
                                                <td>{!! formatPrice($booking->wallet_amount ?? 0) !!}</td>
                                                <td>
                                                    @if($booking->payment_method)
                                                        <span class="payment-method-badge pm-{{ str_contains($booking->payment_method, 'bank') ? 'bank' : ($booking->payment_method === 'tabby' ? 'tabby' : ($booking->payment_method === 'wallet' ? 'wallet' : 'card')) }}">
                                                            {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'pending' ? 'warning' : ($booking->payment_status === 'refunded' ? 'info' : 'danger')) }}">
                                                        {{ ucfirst($booking->payment_status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ in_array($booking->booking_status, ['confirmed', 'completed']) ? 'success' : ($booking->booking_status === 'pending' ? 'warning' : ($booking->booking_status === 'refunded' ? 'info' : 'danger')) }}">
                                                        {{ ucfirst($booking->booking_status) }}
                                                    </span>
                                                </td>
                                                <td style="white-space:nowrap; font-size:12px;">{{ $booking->created_at->format('d M Y') }}</td>
                                                <td>
                                                    <div class="d-flex flex-column gap-2">
                                                        <a href="{{ route('admin.hotel-bookings.show', $booking->id) }}"
                                                            class="btn btn-sm btn-outline-primary" style="white-space:nowrap;">
                                                            <i class="bx bxs-show"></i> View
                                                        </a>
                                                        <form method="POST" action="{{ route('admin.bookings.hotels.status', $booking->id) }}" class="d-flex gap-1 align-items-end flex-wrap">
                                                            @csrf
                                                            <div class="d-flex flex-column">
                                                                <small class="text-muted" style="font-size:10px;">Pay</small>
                                                                <select name="payment_status" class="form-select form-select-sm" style="min-width:100px; font-size:11px;">
                                                                @foreach (['pending', 'paid', 'failed', 'refunded'] as $st)
                                                                    <option value="{{ $st }}" {{ $booking->payment_status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                                @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <small class="text-muted" style="font-size:10px;">Booking</small>
                                                                <select name="booking_status" class="form-select form-select-sm" style="min-width:110px; font-size:11px;">
                                                                @foreach (['pending', 'confirmed', 'cancelled', 'completed', 'refunded', 'failed'] as $st)
                                                                    <option value="{{ $st }}" {{ $booking->booking_status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                                @endforeach
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-primary" style="font-size:11px;">Save</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bx bxs-hotel" style="font-size:44px; opacity:.4;"></i>
                                <p class="mt-2 mb-0">No hotel bookings yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Flight Bookings Tab ──────────────────────────── --}}
                <div class="tab-pane fade" id="panel-flights" role="tabpanel">
                    <div class="tab-pane-inner">
                        @if ($flightBookings->isNotEmpty())
                            <div class="table-responsive">
                                <table class="data-table" id="flightTable">
                                    <thead>
                                        <tr>
                                            <th>Booking #</th>
                                            <th>Route</th>
                                            <th>Departure</th>
                                            <th>Return</th>
                                            <th>Pax</th>
                                            <th>Amount</th>
                                            <th>Wallet Used</th>
                                            <th>Pay Method</th>
                                            <th>Payment</th>
                                            <th>Booking</th>
                                            <th>Ticket</th>
                                            <th>Booked On</th>
                                            <th class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($flightBookings as $booking)
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold" style="color: var(--color-primary);">
                                                        {{ $booking->booking_number }}
                                                    </span>
                                                </td>
                                                <td style="white-space:nowrap;">
                                                    <span class="fw-semibold">{{ $booking->from_airport }}</span>
                                                    <i class="bx bx-right-arrow-alt text-muted"></i>
                                                    <span class="fw-semibold">{{ $booking->to_airport }}</span>
                                                </td>
                                                <td style="white-space:nowrap; font-size:12px;">{{ $booking->departure_date?->format('d M Y') }}</td>
                                                <td style="white-space:nowrap; font-size:12px;">{{ $booking->return_date?->format('d M Y') ?? '—' }}</td>
                                                <td style="white-space:nowrap; font-size:12px;">
                                                    @if($booking->adults) <span class="badge bg-secondary">{{ $booking->adults }}A</span> @endif
                                                    @if($booking->children) <span class="badge bg-secondary">{{ $booking->children }}C</span> @endif
                                                    @if($booking->infants) <span class="badge bg-secondary">{{ $booking->infants }}I</span> @endif
                                                </td>
                                                <td class="fw-bold">{!! formatPrice($booking->total_amount) !!}</td>
                                                <td>{!! formatPrice($booking->wallet_amount ?? 0) !!}</td>
                                                <td>
                                                    @if($booking->payment_method)
                                                        <span class="payment-method-badge pm-{{ str_contains($booking->payment_method, 'bank') ? 'bank' : ($booking->payment_method === 'tabby' ? 'tabby' : ($booking->payment_method === 'wallet' ? 'wallet' : 'card')) }}">
                                                            {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($booking->payment_status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ in_array($booking->booking_status, ['confirmed', 'completed']) ? 'success' : ($booking->booking_status === 'pending' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($booking->booking_status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-{{ $booking->ticket_status === 'issued' ? 'success' : ($booking->ticket_status === 'pending' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($booking->ticket_status) }}
                                                    </span>
                                                </td>
                                                <td style="white-space:nowrap; font-size:12px;">{{ $booking->created_at->format('d M Y') }}</td>
                                                <td>
                                                    <form method="POST" action="{{ route('admin.bookings.flights.status', $booking->id) }}" class="d-flex gap-1 align-items-end flex-wrap">
                                                        @csrf
                                                        <div class="d-flex flex-column">
                                                            <small class="text-muted" style="font-size:10px;">Pay</small>
                                                            <select name="payment_status" class="form-select form-select-sm" style="min-width:95px; font-size:11px;">
                                                            @foreach (['pending', 'paid', 'failed', 'refunded'] as $st)
                                                                <option value="{{ $st }}" {{ $booking->payment_status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                            @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <small class="text-muted" style="font-size:10px;">Booking</small>
                                                            <select name="booking_status" class="form-select form-select-sm" style="min-width:105px; font-size:11px;">
                                                            @foreach (['pending', 'confirmed', 'cancelled', 'completed', 'refunded', 'failed'] as $st)
                                                                <option value="{{ $st }}" {{ $booking->booking_status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                            @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <small class="text-muted" style="font-size:10px;">Ticket</small>
                                                            <select name="ticket_status" class="form-select form-select-sm" style="min-width:95px; font-size:11px;">
                                                            @foreach (['pending', 'issued', 'failed', 'refunded'] as $st)
                                                                <option value="{{ $st }}" {{ $booking->ticket_status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                            @endforeach
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-primary" style="font-size:11px;">Save</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bx bxs-plane-alt" style="font-size:44px; opacity:.4;"></i>
                                <p class="mt-2 mb-0">No flight bookings yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>{{-- /tab-content --}}
        </div>{{-- /custom-sec --}}

    </div>
</div>
@endsection
