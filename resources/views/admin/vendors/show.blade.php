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
                        {{ $vendor->name }}
                    </h1>
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
                <div class="flex-shrink-0">
                    <a href="{{ route('admin.vendors.change-status', $vendor->id) }}"
                        class="btn btn-sm {{ $vendor->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }} fw-semibold px-3"
                        style="border-radius:8px; font-size:.82rem;"
                        onclick="return confirm('Are you sure you want to {{ $vendor->status === 'active' ? 'deactivate' : 'activate' }} {{ addslashes($vendor->name) }}?')">
                        <i class="bx {{ $vendor->status === 'active' ? 'bx-pause-circle' : 'bx-play-circle' }}"></i>
                        {{ $vendor->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </a>
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
                        <span class="vs-stat__icon"><i class="bx bx-plane"></i></span>
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
                    <span class="vs-info-label">Full Name</span>
                    <span class="vs-info-value">{{ $vendor->name }}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Agent Code</span>
                    <span class="vs-info-value"><code style="color:var(--color-primary); font-size:.88em;">{{ $vendor->agent_code }}</code></span>
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
                    <span class="vs-info-label">Wallet Balance</span>
                    <span class="vs-info-value fw-bold" style="color:var(--color-primary);">{!! formatPrice($vendor->main_balance ?? 0) !!}</span>
                </div>
                <div class="vs-info-item">
                    <span class="vs-info-label">Registered</span>
                    <span class="vs-info-value">{{ formatDateTime($vendor->created_at) }}</span>
                </div>
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
                    <i class="bx bx-plane"></i> Flight Bookings
                    <span class="vs-tabs__count">{{ $stats['flight_bookings'] }}</span>
                </button>
            </div>

            {{-- Wallet Ledger --}}
            <div class="vs-tab-panel active" id="panel-wallet">
                @if ($walletLedger->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table">
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
                                @foreach ($walletLedger as $i => $entry)
                                    @php
                                        $pm = null; $pmClass = 'pm-system'; $pmLabel = '—';
                                        if ($entry->reference instanceof \App\Models\B2bWalletRecharge) {
                                            $pm = $entry->reference->payment_method;
                                            [$pmLabel, $pmClass] = match($pm) {
                                                'bank_transfer' => ['Bank Transfer', 'pm-bank'],
                                                'card'          => ['Card', 'pm-card'],
                                                'tabby'         => ['Tabby', 'pm-tabby'],
                                                'wallet'        => ['Wallet', 'pm-wallet'],
                                                default         => [ucfirst(str_replace('_',' ',$pm??'')), 'pm-system'],
                                            };
                                        } elseif ($entry->reference_type) {
                                            $pmLabel = 'System';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td data-order="{{ $entry->created_at->timestamp }}" style="white-space:nowrap; font-size:12px;">
                                            {{ $entry->created_at->format('d M Y') }}<br>
                                            <small class="text-muted">{{ $entry->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $entry->isCredit() ? 'success' : 'danger' }}">
                                                {{ ucfirst($entry->type) }}
                                            </span>
                                        </td>
                                        <td><span class="pm-pill {{ $pmClass }}">{{ $pmLabel }}</span></td>
                                        <td class="fw-bold {{ $entry->isCredit() ? 'text-success' : 'text-danger' }}">
                                            {{ $entry->isCredit() ? '+' : '-' }}{!! formatPrice($entry->amount) !!}
                                        </td>
                                        <td>{!! formatPrice($entry->balance_before) !!}</td>
                                        <td class="fw-semibold">{!! formatPrice($entry->balance_after) !!}</td>
                                        <td style="font-size:13px;">{{ $entry->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
                        <i class="bx bx-plane" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
                        No flight bookings yet.
                    </div>
                @endif
            </div>

        </div>{{-- /vs-card --}}
    </div>
</div>
@endsection

@push('js')
<script>
    function vsTab(e, panelId) {
        document.querySelectorAll('.vs-tabs__btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.vs-tab-panel').forEach(p => p.classList.remove('active'));
        e.currentTarget.classList.add('active');
        document.getElementById(panelId).classList.add('active');
    }
</script>
@endpush
