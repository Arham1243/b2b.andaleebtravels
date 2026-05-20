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

    /* ── Payment method pill ────────────────────────────────────── */
    .pm-pill {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .pm-bank   { background:#f3e5f5; color:#6a1b9a; }
    .pm-debit-booking { background:#ffebee; color:#b71c1c; }
    .pm-refund { background:#e3f2fd; color:#1565c0; }
    .pm-recharge { background:#e8f5e9; color:#2e7d32; }
    .pm-manual { background:#fff8e1; color:#f57f17; }
    .pm-void {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-weight: 700;
        letter-spacing: .06em;
    }
    .badge-voided {
        background: #dc2626 !important;
        color: #fff !important;
        font-weight: 700;
        letter-spacing: .04em;
        font-size: 0.68rem;
    }

    .vs-ledger-row--voided td { opacity: .72; }
    .vs-ledger-row--voided .vs-ledger-amount { text-decoration: line-through; }
    .vs-ledger-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .vs-ledger-actions .btn-ledger {
        font-size: 0.72rem;
        padding: 0.25rem 0.55rem;
        border-radius: 6px;
        border: 1px solid #d8dbe2;
        background: #fff;
        color: #4b4753;
        cursor: pointer;
        line-height: 1.2;
    }
    .vs-ledger-actions .btn-ledger:hover { border-color: var(--color-primary, #cd1b4f); color: var(--color-primary, #cd1b4f); }
    .vs-ledger-actions .btn-ledger--void { border-color: #fecaca; color: #b91c1c; }
    .vs-ledger-actions .btn-ledger--void:hover { background: #fef2f2; }

    .vs-ledger-attachment-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.28rem 0.55rem;
        border-radius: 6px;
        border: 1px solid #d8dbe2;
        background: #fff;
        color: var(--color-primary, #cd1b4f);
        text-decoration: none;
        white-space: nowrap;
    }
    .vs-ledger-attachment-btn:hover {
        border-color: var(--color-primary, #cd1b4f);
        background: rgba(205, 27, 79, 0.06);
        color: var(--color-primary, #cd1b4f);
    }
    .vs-ledger-attachment-empty {
        color: #9ca3af;
        font-size: 0.85rem;
    }
    .vs-wallet-form {
        border: 1px solid #ebecf0;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        margin-bottom: 1.25rem;
        background: #fafbfc;
    }
    .vs-wallet-form__title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #18181b;
        margin: 0 0 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .vs-wallet-form__hint {
        font-size: 0.78rem;
        color: #6b6573;
        margin: 0 0 0.85rem;
    }
    .vs-wallet-form .field {
        width: 100%;
        border: 1px solid #d8dbe2;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
        font-size: 0.88rem;
    }
    .vs-wallet-form label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b6573;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 0.25rem;
        display: block;
    }

    .vs-ledger-filters {
        border: 1px solid #ebecf0;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        margin-bottom: 1.25rem;
        background: #fff;
    }
    .vs-ledger-filters__title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #18181b;
        margin: 0 0 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .vs-ledger-filters__meta {
        font-size: 0.78rem;
        color: #6b6573;
        margin: 0.65rem 0 0;
    }
    .vs-ledger-filters .field {
        width: 100%;
        border: 1px solid #d8dbe2;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
        font-size: 0.88rem;
    }
    .vs-ledger-filters label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b6573;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 0.25rem;
        display: block;
    }
    .vs-ledger-filters__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .vs-ledger-filters__clear {
        font-size: 0.82rem;
        color: var(--color-primary, #cd1b4f);
        text-decoration: none;
        font-weight: 600;
    }
    .vs-ledger-filters__clear:hover { text-decoration: underline; }

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
@endpush

@section('content')
<div class="col-md-12">
    <div class="dashboard-content py-3">
        {{ Breadcrumbs::render('admin.vendors.show', $vendor) }}

        {{-- ── Vendor header ──────────────────────────────────────── --}}
        <header class="mb-4">
            <div class="d-flex align-items-flex-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h1 style="font-size:clamp(1.5rem,2.4vw,1.9rem); font-weight:700; color:#18181b; letter-spacing:-.02em; margin:0 0 .3rem;">
                        {{ $vendor->display_agency_name ?: $vendor->name }}
                    </h1>
                    @if ($vendor->contact_name)
                        <p style="font-size:.9rem; color:#6b6573; margin:0 0 .35rem;">{{ $vendor->contact_name }}{{ $vendor->designation ? ' · ' . $vendor->designation : '' }}</p>
                    @endif
                    <p style="font-size:.88rem; color:#6b6573; margin:0; display:flex; flex-wrap:wrap; gap:.4rem .75rem; align-items:center;">
                        <span><i class="bx bx-envelope" style="vertical-align:middle;"></i> {{ $vendor->email }}</span>
                        <span style="color:#d0d0d8;">·</span>
                        <span><i class="bx bx-user" style="vertical-align:middle;"></i> {{ $vendor->username }}</span>
                        <span style="color:#d0d0d8;">·</span>
                        <span><i class="bx bx-id-card" style="vertical-align:middle;"></i> <code style="font-size:.85em; color:var(--color-primary);">{{ $vendor->agent_code }}</code></span>
                        <span style="color:#d0d0d8;">·</span>
                        <span class="badge rounded-pill {{ $vendor->status === 'active' ? 'bg-success' : 'bg-danger' }}" style="font-size:.72rem;">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </p>
                </div>
                <div class="flex-shrink-0 d-flex gap-2">
                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-secondary fw-semibold px-3" style="border-radius:8px; font-size:.82rem;align-self: center;">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                    @if ($vendor->status !== 'pending')
                        <a href="{{ route('admin.vendors.change-status', $vendor->id) }}"
                            class="btn btn-sm {{ $vendor->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }} fw-semibold px-3"
                            style="border-radius:8px; font-size:.82rem;align-self: center;"
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
                        <span class="vs-stat__label">Wallet Balance</span>
                        <span class="vs-stat__icon"><i class="bx bx-wallet"></i></span>
                    </div>
                    <div class="vs-stat__value is-currency">{!! formatPrice($vendor->main_balance ?? 0) !!}</div>
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
            <div class="col-6 col-xl-3">
                <div class="vs-stat">
                    <div class="vs-stat__head">
                        <span class="vs-stat__label">Total Spent</span>
                        <span class="vs-stat__icon"><i class="bx bx-money-withdraw"></i></span>
                    </div>
                    <div class="vs-stat__value is-currency">{!! formatPrice($stats['total_spent']) !!}</div>
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
                        @if ($vendor->agency_logo)
                            <img src="{{ asset($vendor->agency_logo) }}" alt="Agency Logo"
                                style="max-height:48px; max-width:120px; object-fit:contain; border-radius:6px;">
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Contact Name</span>
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
                <div class="vs-info-item">
                    <span class="vs-info-label">Wallet Balance</span>
                    <span class="vs-info-value fw-bold" style="color:var(--color-primary);">{!! formatPrice($vendor->main_balance ?? 0) !!}</span>
                </div>
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
                <div class="vs-wallet-form">
                    <div class="vs-wallet-form__title"><i class="bx bx-plus-circle"></i> Add manual transaction</div>
                    <p class="vs-wallet-form__hint">
                        Record an admin credit or debit (e.g. hotel payment taken from wallet offline). Use <strong>Edit</strong> or <strong>Void</strong> on any row below to correct mistakes — the wallet balance is recalculated automatically.
                        Current balance: <strong>{!! formatPrice($vendor->main_balance ?? 0) !!}</strong>
                    </p>
                    <form action="{{ route('admin.vendors.wallet-transactions.store', $vendor) }}" method="POST"
                        id="manual-wallet-form" class="row g-3 align-items-end" enctype="multipart/form-data">
                        @csrf
                        <div class="col-md-2">
                            <label for="mw_type">Type</label>
                            <select name="type" id="mw_type" class="field" required>
                                <option value="credit" {{ old('type', 'credit') === 'credit' ? 'selected' : '' }}>Credit</option>
                                <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="mw_amount">Amount (AED)</label>
                            <input type="number" name="amount" id="mw_amount" class="field" step="0.01" min="0.01"
                                value="{{ old('amount') }}" required placeholder="0.00">
                        </div>
                        <div class="col-md-2">
                            <label for="mw_date">Date</label>
                            <input type="date" name="transaction_date" id="mw_date" class="field"
                                value="{{ old('transaction_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label for="mw_time">Time</label>
                            <input type="time" name="transaction_time" id="mw_time" class="field"
                                value="{{ old('transaction_time', now()->format('H:i')) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="mw_description">Description <span class="text-danger">*</span></label>
                            <input type="text" name="description" id="mw_description" class="field" maxlength="500"
                                value="{{ old('description') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="mw_attachment">Attachment</label>
                            <input type="file" name="attachment" id="mw_attachment" class="field" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="themeBtn" style="font-size:.85rem;">
                                <i class="bx bx-check"></i> Add to ledger
                            </button>
                        </div>
                    </form>
                </div>

                @if (($ledgerTotalCount ?? 0) > 0)
                    <div class="vs-ledger-filters">
                        <div class="vs-ledger-filters__title"><i class="bx bx-filter-alt"></i> Filter ledger</div>
                        <form method="GET" action="{{ route('admin.vendors.show', $vendor) }}" class="row g-3 align-items-end" id="ledger-filter-form">
                            <input type="hidden" name="tab" value="wallet">
                            <div class="col-md-3">
                                <label for="ledger_category">Category</label>
                                <select name="ledger_category" id="ledger_category" class="field">
                                    <option value="">All categories</option>
                                    @foreach (\App\Support\WalletLedgerDescription::ledgerFilterOptions() as $slug => $label)
                                        <option value="{{ $slug }}" {{ ($ledgerFilters['category'] ?? '') === $slug ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="ledger_from">From date</label>
                                <input type="date" name="ledger_from" id="ledger_from" class="field"
                                    value="{{ $ledgerFilters['from'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label for="ledger_till">Till date</label>
                                <input type="date" name="ledger_till" id="ledger_till" class="field"
                                    value="{{ $ledgerFilters['till'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <div class="vs-ledger-filters__actions">
                                    <button type="submit" class="themeBtn" style="font-size:.85rem;">
                                        <i class="bx bx-search"></i> Apply
                                    </button>
                                    @if (!empty($ledgerFilters['has_filters']))
                                        <a href="{{ route('admin.vendors.show', $vendor) }}?tab=wallet" class="vs-ledger-filters__clear">Clear</a>
                                    @endif
                                </div>
                            </div>
                        </form>
                        @if (!empty($ledgerFilters['has_filters']))
                            <p class="vs-ledger-filters__meta mb-0">
                                Showing <strong>{{ $walletLedger->count() }}</strong> of <strong>{{ $ledgerTotalCount }}</strong> transactions
                                @if (!empty($ledgerFilters['category']))
                                    · Category: <strong>{{ \App\Support\WalletLedgerDescription::ledgerFilterLabel($ledgerFilters['category']) }}</strong>
                                    @if (!in_array($ledgerFilters['category'], \App\Support\WalletLedgerDescription::ledgerFilterActiveSlugs(), true))
                                        <span class="text-muted">(no transactions for this product yet)</span>
                                    @endif
                                @endif
                                @if (!empty($ledgerFilters['from']) || !empty($ledgerFilters['till']))
                                    · Date:
                                    <strong>
                                        {{ $ledgerFilters['from'] ? \Carbon\Carbon::parse($ledgerFilters['from'])->format('d M Y') : '…' }}
                                        –
                                        {{ $ledgerFilters['till'] ? \Carbon\Carbon::parse($ledgerFilters['till'])->format('d M Y') : '…' }}
                                    </strong>
                                @endif
                            </p>
                        @endif
                    </div>
                @endif

                @if ($walletLedger->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table" id="wallet-ledger-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Amount</th>
                                    <th>Balance Before</th>
                                    <th>Balance After</th>
                                    <th>Description</th>
                                    <th class="no-sort">Attachment</th>
                                    <th class="no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($walletLedger as $entry)
                                    @php
                                        $refLink = $entry->adminReferenceLink();
                                        $isVoided = $entry->isVoided();
                                    @endphp
                                    <tr class="{{ $isVoided ? 'vs-ledger-row--voided' : '' }}"
                                        data-ledger-category="{{ \App\Support\WalletLedgerDescription::adminFilterCategory($entry) }}">
                                        <td data-order="{{ $entry->created_at->timestamp }}" style="white-space:nowrap; font-size:12px;">
                                            {{ $entry->created_at->format('d M Y') }}<br>
                                            <small class="text-muted">{{ $entry->created_at->format('h:i A') }}</small>
                                            @if ($isVoided && $entry->voided_at)
                                                <br><small class="text-danger">Voided {{ $entry->voided_at->format('d M Y') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $isVoided ? 'secondary' : ($entry->isCredit() ? 'success' : 'danger') }}">
                                                {{ ucfirst($entry->type) }}
                                            </span>
                                            @if ($isVoided)
                                                <span class="badge rounded-pill badge-voided ms-1">VOIDED</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isVoided)
                                                <span class="pm-pill pm-void">VOIDED</span>
                                            @else
                                                <span class="pm-pill {{ $entry->adminReasonClass() }}">{{ $entry->adminReasonLabel() }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold vs-ledger-amount {{ $isVoided ? 'text-muted' : ($entry->isCredit() ? 'text-success' : 'text-danger') }}">
                                            {{ $entry->isCredit() ? '+' : '-' }}{!! formatPrice($entry->amount) !!}
                                        </td>
                                        <td>{!! formatPrice($entry->balance_before) !!}</td>
                                        <td class="fw-semibold">{!! formatPrice($entry->balance_after) !!}</td>
                                        <td style="font-size:13px; max-width:320px;">
                                            <div>{{ $entry->description }}</div>
                                            @if (!empty($refLink['label']))
                                                @if (!empty($refLink['url']))
                                                    <a href="{{ $refLink['url'] }}" class="small" style="color:var(--color-primary,#cd1b4f);">{{ $refLink['label'] }}</a>
                                                @else
                                                    <span class="small text-muted">{{ $refLink['label'] }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center" style="white-space:nowrap;">
                                            @if ($entry->hasAttachment())
                                                <a href="{{ $entry->attachmentUrl() }}" class="vs-ledger-attachment-btn" target="_blank" rel="noopener" title="View attachment">
                                                    <i class="bx bx-show"></i> View
                                                </a>
                                            @else
                                                <span class="vs-ledger-attachment-empty">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isVoided)
                                                <span class="small text-muted">-</span>
                                            @else
                                                <div class="vs-ledger-actions">
                                                    <button type="button" class="btn-ledger btn-edit-ledger"
                                                        data-entry-id="{{ $entry->id }}"
                                                        data-type="{{ $entry->type }}"
                                                        data-amount="{{ $entry->amount }}"
                                                        data-description="{{ e($entry->description) }}"
                                                        data-date="{{ $entry->created_at->format('Y-m-d') }}"
                                                        data-time="{{ $entry->created_at->format('H:i') }}"
                                                        data-has-attachment="{{ $entry->hasAttachment() ? '1' : '0' }}"
                                                        data-attachment-url="{{ $entry->attachmentUrl() ?? '' }}"
                                                        data-update-url="{{ route('admin.vendors.wallet-transactions.update', [$vendor, $entry]) }}">
                                                        <i class="bx bx-edit-alt"></i> Edit
                                                    </button>
                                                    <form action="{{ route('admin.vendors.wallet-transactions.void', [$vendor, $entry]) }}" method="POST"
                                                        class="d-inline ledger-void-form"
                                                        data-amount="{{ number_format((float) $entry->amount, 2) }}"
                                                        data-type="{{ $entry->type }}">
                                                        @csrf
                                                        <button type="submit" class="btn-ledger btn-ledger--void">
                                                            <i class="bx bx-block"></i> Void
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif (($ledgerTotalCount ?? 0) > 0 && !empty($ledgerFilters['has_filters']))
                    <div class="text-center py-5" style="color:#6b6573;">
                        <i class="bx bx-filter-alt" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        <p class="mb-2">No transactions match your filters.</p>
                        <a href="{{ route('admin.vendors.show', $vendor) }}?tab=wallet" class="vs-ledger-filters__clear">Clear filters</a>
                    </div>
                @else
                    <div class="text-center py-5" style="color:#6b6573;">
                        <i class="bx bx-wallet" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        No wallet transactions yet.
                    </div>
                @endif
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
                @if (!$vendor->parent_vendor_id)
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subAgents as $i => $agent)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $agent->contact_name ?: $agent->name ?: '—' }}</td>
                                        <td>{{ $agent->email }}</td>
                                        <td>{{ $agent->username }}</td>
                                        <td><code style="font-size:.85em; color:var(--color-primary);">{{ $agent->agent_code }}</code></td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $agent->status === 'active' ? 'success' : 'danger' }}">
                                                {{ ucfirst($agent->status) }}
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px;">{{ formatDateTime($agent->created_at) }}</td>
                                        <td>
                                            <a href="{{ route('admin.vendors.show', $agent->id) }}" class="vs-view-btn">
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
                        <i class="bx bx-group" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        <p class="mb-0">No sub agents for this agency yet.</p>
                    </div>
                @endif
            </div>

        </div>{{-- /vs-card --}}
    </div>
</div>

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
                                <label for="el_attachment">Attachment <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="file" name="attachment" id="el_attachment" class="field" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                                <small class="text-muted d-block mt-1" style="font-size:.72rem;">PDF or image, max 5 MB</small>
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
@endsection

@push('js')
<script>
document.querySelectorAll('.btn-edit-ledger').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const form = document.getElementById('edit-ledger-form');
        form.action = btn.dataset.updateUrl || '';
        document.getElementById('el_type').value = btn.dataset.type || 'credit';
        document.getElementById('el_amount').value = btn.dataset.amount || '';
        document.getElementById('el_description').value = btn.dataset.description || '';
        document.getElementById('el_date').value = btn.dataset.date || '';
        document.getElementById('el_time').value = btn.dataset.time || '';
        const attCurrent = document.getElementById('el_attachment_current');
        const attLink = document.getElementById('el_attachment_link');
        const removeAtt = document.getElementById('el_remove_attachment');
        const attUrl = btn.dataset.attachmentUrl || '';
        if (attCurrent && attLink && removeAtt) {
            const show = btn.dataset.hasAttachment === '1' && attUrl !== '';
            attCurrent.style.display = show ? 'block' : 'none';
            if (show) {
                attLink.href = attUrl;
            }
            removeAtt.checked = false;
        }
        document.getElementById('el_attachment').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editLedgerModal')).show();
    });
});

document.getElementById('edit-ledger-form')?.addEventListener('submit', function(e) {
    const type = document.getElementById('el_type')?.value || 'credit';
    const amount = document.getElementById('el_amount')?.value || '0';
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
    const action = type === 'debit' ? 'debit' : 'credit';
    const message =
        'Add manual wallet ' + action + '?\n\n' +
        'Vendor: ' + vendor + '\n' +
        'Amount: ' + amount + ' AED\n' +
        'Description: ' + description + '\n\n' +
        (type === 'debit'
            ? 'This will reduce the vendor wallet balance.'
            : 'This will increase the vendor wallet balance.') +
        '\n\nContinue?';
    if (!confirm(message)) {
        e.preventDefault();
    }
});

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
