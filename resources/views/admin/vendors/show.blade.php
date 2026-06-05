@extends('admin.layouts.main')

@push('css')
<style>
    /* ── Section title (matches dashboard) ─────────────────────── */
    .vs-section-title {
        position: relative;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #4b4753;
        margin: 0 0 0.85rem;
        padding-left: 0.85rem;
    }
    .vs-section-title::before {
        content: "";
        position: absolute;
        left: 0; top: 50%;
        transform: translateY(-50%);
        width: 3px; height: 0.85rem;
        border-radius: 2px;
        background: var(--color-primary, #cd1b4f);
    }

    /* ── White card (matches admin-card) ───────────────────────── */
    .vs-card {
        background: #fff;
        border: 1px solid #ebecf0;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(20,20,30,.04);
        padding: 1.25rem 1.35rem;
    }

    /* ── Stat tiles (matches admin-stat) ───────────────────────── */
    .vs-stat {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        padding: 1.1rem 1.2rem;
        background: #fff;
        border: 1px solid #ebecf0;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(20,20,30,.04);
    }
    .vs-stat__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .vs-stat__label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #6b6573;
        letter-spacing: .01em;
    }
    .vs-stat__icon {
        width: 34px; height: 34px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9px;
        background: rgba(205,27,79,.08);
        color: var(--color-primary, #cd1b4f);
        font-size: 1.05rem; flex-shrink: 0;
    }
    .vs-stat__icon i {
        font-family: 'boxicons' !important;
        font-style: normal;
        line-height: 1;
    }
    .vs-stat__value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.1;
        color: #18181b;
        letter-spacing: -0.02em;
    }
    .vs-stat__value.is-currency { font-size: 1.25rem; }

    .vs-vendor-head__title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem 0.85rem;
        margin-bottom: 0.45rem;
    }
    .vs-vendor-head__title-row h1 {
        font-size: clamp(1.5rem, 2.4vw, 1.9rem);
        font-weight: 700;
        color: #18181b;
        letter-spacing: -.02em;
        margin: 0;
    }
    .vs-vendor-head__status {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
    }
    .vs-vendor-head__meta {
        font-size: 0.88rem;
        color: #6b6573;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem 0.75rem;
        align-items: center;
    }

    /* ── Key-value info grid ────────────────────────────────────── */
    .vs-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }
    .vs-info-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 0.75rem 0.5rem;
        border-bottom: 1px solid #f1f3f5;
    }
    .vs-info-item:nth-child(odd)  { border-right: 1px solid #f1f3f5; padding-right: 1.5rem; }
    .vs-info-item:nth-child(even) { padding-left: 1.5rem; }
    .vs-info-item:nth-last-child(-n+2) { border-bottom: none; }
    .vs-info-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b6573;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .vs-info-value {
        font-size: 0.9rem;
        color: #18181b;
        font-weight: 500;
    }

    /* ── Tabs ───────────────────────────────────────────────────── */
    .vs-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #f0f1f4;
        margin: 0 -1.35rem 0;
        padding: 0 1.35rem;
    }
    .vs-tabs__btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.7rem 1rem 0.65rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #6b6573;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        transition: color .15s, border-color .15s;
        white-space: nowrap;
    }
    .vs-tabs__btn:hover { color: #18181b; }
    .vs-tabs__btn.active {
        color: var(--color-primary, #cd1b4f);
        border-bottom-color: var(--color-primary, #cd1b4f);
    }
    .vs-tabs__count {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 999px;
        background: #f1f2f5;
        color: #4b4753;
        transition: background .15s, color .15s;
    }
    .vs-tabs__btn.active .vs-tabs__count {
        background: rgba(205,27,79,.12);
        color: var(--color-primary, #cd1b4f);
    }
    .vs-tab-panel { display: none; padding-top: 1.25rem; }
    .vs-tab-panel.active { display: block; }

    /* ── Table view button ──────────────────────────────────────── */
    .vs-view-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--color-primary, #cd1b4f);
        background: rgba(205,27,79,.08);
        border: 1px solid rgba(205,27,79,.2);
        border-radius: 6px;
        text-decoration: none;
        white-space: nowrap;
        transition: background .15s, color .15s;
    }
    .vs-view-btn:hover {
        background: var(--color-primary, #cd1b4f);
        color: #fff;
        text-decoration: none;
    }

    .vs-ledger-modal .modal-content {
        border: 1px solid #ebecf0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(20, 20, 30, 0.12);
    }
    .vs-ledger-modal .modal-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #ebecf0;
        background: #fafbfc;
    }
    .vs-ledger-modal .modal-title {
        font-size: 1rem;
        font-weight: 700;
        color: #18181b;
    }
    .vs-ledger-modal .modal-body {
        padding: 1.15rem 1.25rem 1.25rem;
    }
    .vs-ledger-modal .modal-body__hint {
        font-size: 0.8rem;
        color: #6b6573;
        margin: 0 0 1rem;
        line-height: 1.45;
    }
    .vs-ledger-modal .modal-footer {
        padding: 0.85rem 1.25rem 1.15rem;
        border-top: 1px solid #ebecf0;
        background: #fafbfc;
        gap: 0.5rem;
    }
    .vs-ledger-modal .vs-ledger-modal__field {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        min-width: 0;
    }
    .vs-ledger-modal .vs-ledger-modal__field label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b6573;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin: 0;
        display: block;
    }
    .vs-ledger-modal .vs-ledger-modal__field .field,
    .vs-ledger-modal .vs-ledger-modal__field select.field {
        width: 100%;
        max-width: 100%;
        border: 1px solid #d8dbe2;
        border-radius: 8px;
        padding: 0.5rem 0.7rem;
        font-size: 0.88rem;
        color: #18181b;
        background: #fff;
        line-height: 1.35;
        box-sizing: border-box;
    }
    .vs-ledger-modal .vs-ledger-modal__field .field:focus,
    .vs-ledger-modal .vs-ledger-modal__field select.field:focus {
        outline: none;
        border-color: var(--color-primary, #cd1b4f);
        box-shadow: 0 0 0 3px rgba(205, 27, 79, 0.12);
    }
    .vs-ledger-modal .btn-modal-cancel {
        font-size: 0.85rem;
        padding: 0.45rem 1rem;
        border-radius: 8px;
        border: 1px solid #d8dbe2;
        background: #fff;
        color: #4b4753;
        font-weight: 600;
    }
    .vs-ledger-modal .btn-modal-cancel:hover {
        background: #f4f5f7;
        border-color: #c5c9d2;
    }
    .vs-ledger-modal .modal-footer .themeBtn {
        font-size: 0.85rem;
        padding: 0.45rem 1.1rem;
        margin: 0;
    }

    .pm-card   { background:#e3f2fd; color:#1565c0; }
    .pm-tabby  { background:#fff3e0; color:#e65100; }
    .pm-wallet { background:#e8f5e9; color:#2e7d32; }
    .pm-system { background:#f1f2f5; color:#4b4753; }

    @media (max-width: 576px) {
        .vs-info-grid { grid-template-columns: 1fr; }
        .vs-info-item:nth-child(odd)  { border-right: none; padding-right: 0.5rem; }
        .vs-info-item:nth-child(even) { padding-left: 0.5rem; }
        .vs-info-item:nth-last-child(-n+2) { border-bottom: 1px solid #f1f3f5; }
        .vs-info-item:last-child { border-bottom: none; }
        .vs-tabs { overflow-x: auto; }
    }
</style>
@include('partials.wallet-ledger-styles')
@include('partials.wallet-balance-metrics-styles')
@endpush

@section('content')
@php
    $canViewVendor = \App\Support\B2bAdminPortalUi::can('vendors_view');
    $canEditVendor = \App\Support\B2bAdminPortalUi::can('vendors_edit');
    $canDeleteVendor = \App\Support\B2bAdminPortalUi::can('vendors_delete');
    $canWalletManage = \App\Support\B2bAdminPortalUi::can('vendors_wallet_manage');
    $showSubAgentActions = $canViewVendor || $canEditVendor || $canDeleteVendor;
@endphp
<div class="col-md-12">
    <div class="dashboard-content py-3">
        {{ Breadcrumbs::render('admin.vendors.show', $vendor) }}

        {{-- ── Vendor header ──────────────────────────────────────── --}}
        <header class="mb-4">
            <div class="d-flex align-items-flex-start justify-content-between gap-3 flex-wrap">
                <div>
                    <div class="vs-vendor-head__title-row">
                        <h1>{{ $vendor->display_agency_name ?: $vendor->name }}</h1>
                        <span class="badge rounded-pill vs-vendor-head__status {{ $vendor->status === 'active' ? 'bg-success' : ($vendor->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </div>
                    <p class="vs-vendor-head__meta">
                        <span><i class="bx bx-envelope" style="vertical-align:middle;"></i> {{ $vendor->email }}</span>
                        <span style="color:#d0d0d8;">·</span>
                        <span><i class="bx bx-user" style="vertical-align:middle;"></i> {{ $vendor->username }}</span>
                        <span style="color:#d0d0d8;">·</span>
                        <span><i class="bx bx-id-card" style="vertical-align:middle;"></i> <code style="font-size:.85em; color:var(--color-primary);">{{ $vendor->agent_code }}</code></span>
                    </p>
                </div>
                <div class="flex-shrink-0 d-flex align-items-center gap-2 flex-wrap">
                    @if ($canWalletManage && $vendor->isAgencyAccount() && $vendor->status !== 'pending')
                        <form action="{{ route('admin.vendors.payment-reminder', $vendor) }}" method="POST" class="d-flex m-0"
                              onsubmit="return confirm('Send a payment reminder email to {{ addslashes($vendor->email) }}?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary fw-semibold px-3"
                                    style="border-radius:8px; font-size:.82rem;">
                                <i class="bx bx-bell"></i> Payment Reminder
                            </button>
                        </form>
                    @endif
                    @if ($canEditVendor)
                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-secondary fw-semibold px-3" style="border-radius:8px; font-size:.82rem;">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                    @endif
                    @if ($canEditVendor && $vendor->status !== 'pending')
                        <a href="{{ route('admin.vendors.change-status', $vendor->id) }}"
                            class="btn btn-sm {{ $vendor->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }} fw-semibold px-3"
                            style="border-radius:8px; font-size:.82rem;"
                            onclick="return confirm('Are you sure you want to {{ $vendor->status === 'active' ? 'deactivate' : 'activate' }} {{ addslashes($vendor->display_agency_name ?: $vendor->name) }}?')">
                            <i class="bx {{ $vendor->status === 'active' ? 'bx-pause-circle' : 'bx-play-circle' }}"></i>
                            {{ $vendor->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </a>
                    @endif
                </div>
            </div>
        </header>

        {{-- ── Stat tiles ─────────────────────────────────────────── --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="vs-stat">
                    <div class="vs-stat__head">
                        <span class="vs-stat__label">Available Balance</span>
                        <span class="vs-stat__icon"><i class="bx bx-wallet"></i></span>
                    </div>
                    <div class="vs-stat__value is-currency">{!! formatPrice($vendor->availableBalanceAmount()) !!}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="vs-stat">
                    <div class="vs-stat__head">
                        <span class="vs-stat__label">Used Balance</span>
                        <span class="vs-stat__icon"><i class="bx bx-trending-down"></i></span>
                    </div>
                    <div class="vs-stat__value is-currency">{!! formatPrice($vendor->usedBalanceAmount()) !!}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="vs-stat">
                    <div class="vs-stat__head">
                        <span class="vs-stat__label">Hotel Bookings</span>
                        <span class="vs-stat__icon"><i class="bx bx-hotel"></i></span>
                    </div>
                    <div class="vs-stat__value">{{ $stats['hotel_bookings'] }}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="vs-stat">
                    <div class="vs-stat__head">
                        <span class="vs-stat__label">Flight Bookings</span>
                        <span class="vs-stat__icon"><i class="bx bxs-plane"></i></span>
                    </div>
                    <div class="vs-stat__value">{{ $stats['flight_bookings'] }}</div>
                </div>
            </div>
        </div>

        {{-- ── Vendor details ─────────────────────────────────────── --}}
        <p class="vs-section-title">Vendor Details</p>
        <div class="vs-card mb-4">
            <div class="vs-info-grid">
                <div class="vs-info-item">
                    <span class="vs-info-label">Travel Agency</span>
                    <span class="vs-info-value">{{ $vendor->display_agency_name ?: '—' }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Agency Logo</span>
                    <span class="vs-info-value">
                        @if ($vendor->agencyLogoUrl())
                            <img src="{{ $vendor->agencyLogoUrl() }}" alt="Agency Logo"
                                style="max-height:48px; max-width:120px; object-fit:contain; border-radius:6px;">
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Name</span>
                    <span class="vs-info-value">{{ $vendor->contact_name ?: $vendor->name ?: '—' }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Designation</span>
                    <span class="vs-info-value">{{ $vendor->designation ?: '—' }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Email Address</span>
                    <span class="vs-info-value">{{ $vendor->email }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Username</span>
                    <span class="vs-info-value">{{ $vendor->username }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Agent Code</span>
                    <span class="vs-info-value"><code style="color:var(--color-primary); font-size:.88em;">{{ $vendor->agent_code }}</code></span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Trade License Number</span>
                    <span class="vs-info-value">{{ $vendor->trade_license_number ?: '—' }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Trade License Expiry</span>
                    <span class="vs-info-value">{{ $vendor->trade_license_expiry ? $vendor->trade_license_expiry->format('d M Y') : '—' }}</span>
                </div>
                @if ($vendor->isAgencyAccount())
                    <div class="vs-info-item">
                        <span class="vs-info-label">Available Balance</span>
                        <span class="vs-info-value fw-bold" style="color:var(--color-primary);">{!! formatPrice($vendor->availableBalanceAmount()) !!}</span>
                    </div>
                    <div class="vs-info-item">
                        <span class="vs-info-label">Used Balance</span>
                        <span class="vs-info-value">{!! formatPrice($vendor->usedBalanceAmount()) !!}</span>
                    </div>
                @endif
                <div class="vs-info-item">
                    <span class="vs-info-label">Registered</span>
                    <span class="vs-info-value">{{ formatDateTime($vendor->created_at) }}</span>
                </div>
                @if ($vendor->parentVendor)
                    <div class="vs-info-item">
                        <span class="vs-info-label">Parent Agency</span>
                        <span class="vs-info-value">
                            <a href="{{ route('admin.vendors.show', $vendor->parentVendor) }}">
                                {{ $vendor->parentVendor->display_agency_name ?: $vendor->parentVendor->name }}
                            </a>
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Tabs ───────────────────────────────────────────────── --}}
        <p class="vs-section-title">Activity</p>
        <div class="vs-card">

            <div class="vs-tabs" role="tablist">
                <button class="vs-tabs__btn active" onclick="vsTab(event,'panel-wallet')" type="button">
                    <i class="bx bx-wallet"></i> Wallet Ledger
                    <span class="vs-tabs__count">{{ $stats['ledger_entries'] }}</span>
                </button>
                <button class="vs-tabs__btn" onclick="vsTab(event,'panel-hotels')" type="button">
                    <i class="bx bx-hotel"></i> Hotel Bookings
                    <span class="vs-tabs__count">{{ $stats['hotel_bookings'] }}</span>
                </button>
                <button class="vs-tabs__btn" onclick="vsTab(event,'panel-flights')" type="button">
                    <i class="bx bxs-plane"></i> Flight Bookings
                    <span class="vs-tabs__count">{{ $stats['flight_bookings'] }}</span>
                </button>
                <button class="vs-tabs__btn" onclick="vsTab(event,'panel-sub-agents')" type="button">
                    <i class="bx bx-group"></i> Sub Agents
                    <span class="vs-tabs__count">{{ $stats['sub_agents'] }}</span>
                </button>
            </div>

            {{-- Wallet Ledger --}}
            <div class="vs-tab-panel active" id="panel-wallet">
                @include('partials.wallet-ledger-panel', [
                    'vendor' => $vendor,
                    'walletLedger' => $walletLedger,
                    'ledgerFilters' => $ledgerFilters,
                    'ledgerTotalCount' => $ledgerTotalCount,
                    'filterFormAction' => route('admin.vendors.show', $vendor),
                    'clearFiltersUrl' => route('admin.vendors.show', $vendor) . '?tab=wallet',
                    'filterHiddenInputs' => ['tab' => 'wallet'],
                    'showManualForm' => $canWalletManage,
                    'readOnly' => ! $canWalletManage,
                    'ledgerContext' => 'admin',
                ])
            </div>

            {{-- Hotel Bookings --}}
            <div class="vs-tab-panel" id="panel-hotels">
                @if ($hotelBookings->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Hotel</th>
                                    <th>Supplier</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Amount</th>
                                    <th>Pay Method</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Booked On</th>
                                    <th class="no-sort"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hotelBookings as $booking)
                                    <tr>
                                        <td class="fw-semibold" style="font-size:13px; color:var(--color-primary);">
                                            {{ $booking->booking_number }}
                                        </td>
                                        <td>
                                            <span title="{{ $booking->hotel_name }}" style="max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;">
                                                {{ $booking->hotel_name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary" style="font-size:10px; text-transform:uppercase;">
                                                {{ $booking->supplier ?? 'yalago' }}
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ $booking->check_in_date?->format('d M Y') }}</td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ $booking->check_out_date?->format('d M Y') }}</td>
                                        <td class="fw-bold">{!! formatPrice($booking->total_amount) !!}</td>
                                        <td>
                                            @if($booking->payment_method)
                                                @php
                                                    $pc = str_contains($booking->payment_method,'bank') ? 'pm-bank'
                                                        : ($booking->payment_method==='tabby' ? 'pm-tabby'
                                                        : ($booking->payment_method==='wallet' ? 'pm-wallet' : 'pm-card'));
                                                @endphp
                                                <span class="pm-pill {{ $pc }}">{{ ucfirst(str_replace('_',' ',$booking->payment_method)) }}</span>
                                            @else
                                                <span style="color:#aaa;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $booking->payment_status==='paid' ? 'success' : ($booking->payment_status==='pending' ? 'warning' : ($booking->payment_status==='refunded' ? 'info' : 'danger')) }}">
                                                {{ ucfirst($booking->payment_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ in_array($booking->booking_status,['confirmed','completed']) ? 'success' : ($booking->booking_status==='pending' ? 'warning' : ($booking->booking_status==='refunded' ? 'info' : 'danger')) }}">
                                                {{ ucfirst($booking->booking_status) }}
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ $booking->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.hotel-bookings.show', $booking->id) }}" class="vs-view-btn">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5" style="color:#6b6573;">
                        <i class="bx bx-hotel" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        No hotel bookings yet.
                    </div>
                @endif
            </div>

            {{-- Flight Bookings --}}
            <div class="vs-tab-panel" id="panel-flights">
                @if ($flightBookings->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Route</th>
                                    <th>Departure</th>
                                    <th>Return</th>
                                    <th>Pax</th>
                                    <th>Amount</th>
                                    <th>Pay Method</th>
                                    <th>Payment</th>
                                    <th>Booking</th>
                                    <th>Ticket</th>
                                    <th>Booked On</th>
                                    <th class="no-sort"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($flightBookings as $booking)
                                    <tr>
                                        <td class="fw-semibold" style="font-size:13px; color:var(--color-primary);">
                                            {{ $booking->booking_number }}
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <span class="fw-semibold">{{ $booking->from_airport }}</span>
                                            <i class="bx bx-right-arrow-alt" style="color:#aaa; vertical-align:middle;"></i>
                                            <span class="fw-semibold">{{ $booking->to_airport }}</span>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ $booking->departure_date?->format('d M Y') }}</td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ $booking->return_date?->format('d M Y') ?? '—' }}</td>
                                        <td style="white-space:nowrap; font-size:12px;">
                                            @if($booking->adults)   <span class="badge bg-secondary">{{ $booking->adults }}A</span> @endif
                                            @if($booking->children) <span class="badge bg-secondary">{{ $booking->children }}C</span> @endif
                                            @if($booking->infants)  <span class="badge bg-secondary">{{ $booking->infants }}I</span> @endif
                                        </td>
                                        <td class="fw-bold">{!! formatPrice($booking->total_amount) !!}</td>
                                        <td>
                                            @if($booking->payment_method)
                                                @php
                                                    $pc = str_contains($booking->payment_method,'bank') ? 'pm-bank'
                                                        : ($booking->payment_method==='tabby' ? 'pm-tabby'
                                                        : ($booking->payment_method==='wallet' ? 'pm-wallet' : 'pm-card'));
                                                @endphp
                                                <span class="pm-pill {{ $pc }}">{{ ucfirst(str_replace('_',' ',$booking->payment_method)) }}</span>
                                            @else
                                                <span style="color:#aaa;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $booking->payment_status==='paid' ? 'success' : ($booking->payment_status==='pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($booking->payment_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ in_array($booking->booking_status,['confirmed','completed']) ? 'success' : ($booking->booking_status==='pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($booking->booking_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $booking->ticket_status==='issued' ? 'success' : ($booking->ticket_status==='pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($booking->ticket_status) }}
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ $booking->created_at->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.flight-bookings.show', $booking->id) }}" class="vs-view-btn">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5" style="color:#6b6573;">
                        <i class="bx bxs-plane" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        No flight bookings yet.
                    </div>
                @endif
            </div>

            {{-- Sub Agents --}}
            <div class="vs-tab-panel" id="panel-sub-agents">
                @if (!$vendor->parent_vendor_id && $canEditVendor)
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.vendors.sub-agents.create', $vendor) }}" class="themeBtn" style="font-size:.82rem; padding:.4rem 1rem;">
                            <i class="bx bx-plus"></i> Add Sub Agent
                        </a>
                    </div>
                @endif

                @if ($subAgents->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Agent Code</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    @if ($showSubAgentActions)
                                        <th>Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subAgents as $i => $agent)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $agent->contact_name ?: $agent->name ?: '—' }}</td>
                                        <td>{{ $agent->email }}</td>
                                        <td>{{ $agent->username }}</td>
                                        <td><code style="font-size:.85em; color:var(--color-primary);">{{ $agent->loginAgentCode() }}</code></td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $agent->status === 'active' ? 'success' : 'danger' }}">
                                                {{ ucfirst($agent->status) }}
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ formatDateTime($agent->created_at) }}</td>
                                        @if ($showSubAgentActions)
                                            <td>
                                                <div class="vs-ledger-actions">
                                                    @if ($canEditVendor)
                                                        <a href="{{ route('admin.vendors.edit', $agent) }}" class="vs-view-btn">
                                                            <i class="bx bx-edit-alt"></i> Edit
                                                        </a>
                                                    @elseif ($canViewVendor)
                                                        <a href="{{ route('admin.vendors.show', $agent) }}" class="vs-view-btn">
                                                            <i class="bx bx-show"></i> View
                                                        </a>
                                                    @endif
                                                    @if ($canDeleteVendor)
                                                        <form action="{{ route('admin.vendors.destroy', $agent) }}" method="POST" class="d-inline"
                                                            onsubmit="return confirm('Delete this sub agent? This cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-ledger btn-ledger--void">
                                                                <i class="bx bx-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5" style="color:#6b6573;">
                        <i class="bx bx-group" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        <p class="mb-0">No sub agents for this agency yet.</p>
                    </div>
                @endif
            </div>

        </div>{{-- /vs-card --}}
    </div>
</div>

@if ($canWalletManage)
<div class="modal fade vs-ledger-modal" id="editLedgerModal" tabindex="-1" aria-labelledby="editLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="edit-ledger-form" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editLedgerModalLabel">Edit wallet transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="modal-body__hint">Changes recalculate this vendor&apos;s wallet balance from all active (non-voided) transactions.</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="el_type">Type</label>
                                <select name="type" id="el_type" class="field" required>
                                    <option value="credit">Credit</option>
                                    <option value="debit">Debit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="el_amount">Amount (AED)</label>
                                <input type="number" name="amount" id="el_amount" class="field" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="el_date">Date</label>
                                <input type="date" name="transaction_date" id="el_date" class="field" max="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="el_time">Time</label>
                                <input type="time" name="transaction_time" id="el_time" class="field">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="vs-ledger-modal__field">
                                <label for="el_description">Description</label>
                                <input type="text" name="description" id="el_description" class="field" maxlength="500" required placeholder="Transaction description">
                            </div>
                        </div>
                        <div class="col-12" id="el_attachment_wrap">
                            <div class="vs-ledger-modal__field">
                                <label for="el_attachment">Attachment</label>
                                @include('partials.file-upload-picker', [
                                    'inputId' => 'el_attachment',
                                    'inputName' => 'attachment',
                                    'previewId' => 'el_attachment_preview',
                                    'filenameId' => 'el_attachment_filename',
                                    'chooseLabel' => 'Choose file',
                                    'btnClass' => 'themeBtn agency-logo-upload__btn',
                                    'accept' => '.jpg,.jpeg,.png,.gif,.webp,.pdf',
                                ])
                                <div id="el_attachment_current" class="mt-2" style="display:none;">
                                    <a href="#" id="el_attachment_link" class="vs-ledger-attachment-btn" target="_blank" rel="noopener">
                                        <i class="bx bx-paperclip"></i> Current attachment
                                    </a>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="el_remove_attachment">
                                        <label class="form-check-label small" for="el_remove_attachment">Remove attachment</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="themeBtn"><i class="bx bx-check"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('js')
<script>
@if ($canWalletManage)
document.querySelectorAll('.btn-edit-ledger').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const form = document.getElementById('edit-ledger-form');
        const manualKind = btn.dataset.manualKind || '';
        const typeField = document.getElementById('el_type');
        const amountField = document.getElementById('el_amount');
        form.action = btn.dataset.updateUrl || '';
        typeField.value = btn.dataset.type || 'credit';
        amountField.value = btn.dataset.amount || '';
        document.getElementById('el_description').value = btn.dataset.description || '';
        document.getElementById('el_date').value = btn.dataset.date || '';
        document.getElementById('el_time').value = btn.dataset.time || '';

        const lockFields = manualKind === 'unpaid_credit' || manualKind === 'unpaid_credit_settlement';
        typeField.disabled = lockFields;
        amountField.readOnly = lockFields;

        const attCurrent = document.getElementById('el_attachment_current');
        const attLink = document.getElementById('el_attachment_link');
        const removeAtt = document.getElementById('el_remove_attachment');
        const attUrl = btn.dataset.attachmentUrl || '';
        const attPreview = document.getElementById('el_attachment_preview');
        const attFilename = document.getElementById('el_attachment_filename');
        if (attCurrent && attLink && removeAtt) {
            const show = btn.dataset.hasAttachment === '1' && attUrl !== '';
            attCurrent.style.display = show ? 'block' : 'none';
            if (show) {
                attLink.href = attUrl;
            }
            removeAtt.checked = false;
            if (attPreview) {
                const isImage = /\.(jpe?g|png|gif|webp)(\?.*)?$/i.test(attUrl);
                if (show && isImage) {
                    attPreview.src = attUrl;
                    attPreview.style.display = '';
                } else {
                    attPreview.src = '';
                    attPreview.style.display = 'none';
                }
            }
            if (attFilename) {
                attFilename.textContent = show ? 'Current file attached' : 'No file chosen';
            }
        }
        document.getElementById('el_attachment').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editLedgerModal')).show();
    });
});

document.getElementById('edit-ledger-form')?.addEventListener('submit', function(e) {
    const typeField = document.getElementById('el_type');
    const amountField = document.getElementById('el_amount');
    if (typeField?.disabled) {
        typeField.disabled = false;
    }
    const type = typeField?.value || 'credit';
    const amount = amountField?.value || '0';
    const message =
        'Update this wallet transaction?\n\n' +
        'Type: ' + type + '\n' +
        'Amount: ' + amount + ' AED\n\n' +
        'The vendor wallet balance will be recalculated.';
    if (!confirm(message)) {
        e.preventDefault();
    }
});

document.querySelectorAll('.ledger-void-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        const type = form.dataset.type || 'transaction';
        const amount = form.dataset.amount || '0';
        const message =
            'Void this ' + type + ' of ' + amount + ' AED?\n\n' +
            'It will be excluded from the wallet balance and cannot be edited afterward.';
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
});

document.getElementById('manual-wallet-form')?.addEventListener('submit', function(e) {
    const type = document.getElementById('mw_type')?.value || 'credit';
    const amount = document.getElementById('mw_amount')?.value || '0';
    const description = document.getElementById('mw_description')?.value || '';
    const vendor = @json($vendor->display_agency_name ?: $vendor->name);
    const isDebit = type === 'debit';
    const actionLabel = isDebit ? 'debit' : 'credit';
    const impact = isDebit
        ? 'This will reduce the vendor wallet balance.'
        : 'This will increase the vendor wallet balance.';

    const message =
        'Add manual wallet ' + actionLabel + '?\n\n' +
        'Vendor: ' + vendor + '\n' +
        'Amount: ' + amount + ' AED\n' +
        'Description: ' + description + '\n\n' +
        impact +
        '\n\nContinue?';
    if (!confirm(message)) {
        e.preventDefault();
    }
});
@endif

    function vsTab(e, panelId) {
        document.querySelectorAll('.vs-tabs__btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.vs-tab-panel').forEach(p => p.classList.remove('active'));
        e.currentTarget.classList.add('active');
        document.getElementById(panelId).classList.add('active');
    }

    (function activateWalletTabFromQuery() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('tab') !== 'wallet' && !params.has('ledger_category') && !params.has('ledger_from') && !params.has('ledger_till')) {
            return;
        }
        const walletBtn = document.querySelector('.vs-tabs__btn[onclick*="panel-wallet"]');
        if (walletBtn) {
            vsTab({ currentTarget: walletBtn }, 'panel-wallet');
        }
    })();

    document.getElementById('ledger-filter-form')?.addEventListener('submit', function(e) {
        const from = document.getElementById('ledger_from')?.value || '';
        const till = document.getElementById('ledger_till')?.value || '';
        if (from && till && from > till) {
            if (!confirm('From date is after till date. Swap dates and apply filter?')) {
                e.preventDefault();
                return;
            }
            document.getElementById('ledger_from').value = till;
            document.getElementById('ledger_till').value = from;
        }
    });
</script>
@endpush
