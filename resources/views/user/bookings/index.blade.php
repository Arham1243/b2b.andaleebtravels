@extends('user.layouts.main')

@section('css')
<style>
/* ── Page shell ────────────────────────────────────────────── */
.bk { padding: 28px 0 48px; min-height: 80vh; }

.bk-shell {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    align-items: start;
}

/* ── Left sidebar ──────────────────────────────────────────── */
.bk-sidebar {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 14px;
    overflow: hidden;
    position: sticky;
    top: 80px;
    max-height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}

.bk-sidebar__head {
    padding: 16px 16px 0;
    flex-shrink: 0;
}

.bk-sidebar__title {
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8492a6;
    margin-bottom: 10px;
}

.bk-sidebar__search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f5f7fa;
    border: 1px solid #e4e9f0;
    border-radius: 8px;
    padding: 7px 10px;
    margin-bottom: 12px;
}

.bk-sidebar__search i { color: #8492a6; font-size: 1rem; }

.bk-sidebar__search input {
    border: none;
    background: transparent;
    font-size: .82rem;
    color: #1a2540;
    outline: none;
    width: 100%;
}

.bk-sidebar__search input::placeholder { color: #b0bac8; }

/* type tabs */
.bk-tabs {
    display: flex;
    gap: 4px;
    padding: 0 16px 10px;
    flex-shrink: 0;
}

.bk-tab {
    flex: 1;
    border: none;
    background: #f5f7fa;
    border-radius: 8px;
    padding: 6px 8px;
    font-size: .74rem;
    font-weight: 600;
    color: #8492a6;
    cursor: pointer;
    text-align: center;
    transition: background .15s, color .15s;
    white-space: nowrap;
}

.bk-tab.active, .bk-tab:hover {
    background: var(--c-brand, #cd1b4f);
    color: #fff;
}

/* status filters */
.bk-filters {
    display: flex;
    gap: 6px;
    padding: 0 16px 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.bk-filter-chip {
    border: 1px solid #e4e9f0;
    background: #fff;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: .7rem;
    font-weight: 600;
    color: #8492a6;
    cursor: pointer;
    transition: all .15s;
}

.bk-filter-chip.active {
    border-color: var(--c-brand, #cd1b4f);
    background: #fdf1f4;
    color: var(--c-brand, #cd1b4f);
}

/* booking list */
.bk-list {
    overflow-y: auto;
    flex: 1;
    padding: 0 8px 12px;
}

.bk-list::-webkit-scrollbar { width: 4px; }
.bk-list::-webkit-scrollbar-thumb { background: #e4e9f0; border-radius: 4px; }

.bk-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 10px;
    border-radius: 10px;
    cursor: pointer;
    transition: background .12s;
    border-bottom: 1px solid #f0f3f8;
    text-decoration: none;
    color: inherit;
}

.bk-item:last-child { border-bottom: none; }

.bk-item:hover { background: #f8faff; }

.bk-item.active {
    background: #fdf1f4;
    border-bottom-color: transparent;
}

.bk-item__icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.bk-item__icon--flight { background: #eef2ff; color: #4f61c4; }
.bk-item__icon--hotel  { background: #f0fdf4; color: #16a34a; }

.bk-item__body { flex: 1; min-width: 0; }

.bk-item__num {
    font-size: .72rem;
    font-weight: 700;
    color: var(--c-brand, #cd1b4f);
    letter-spacing: .03em;
}

.bk-item__route {
    font-size: .8rem;
    font-weight: 600;
    color: #1a2540;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bk-item__meta {
    font-size: .68rem;
    color: #8492a6;
    margin-top: 2px;
}

.bk-item__badge {
    flex-shrink: 0;
    font-size: .62rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: .03em;
    text-transform: uppercase;
    align-self: flex-start;
    margin-top: 2px;
}

.bk-item__badge--confirmed { background: #dcfce7; color: #15803d; }
.bk-item__badge--hold      { background: #fef9c3; color: #a16207; }
.bk-item__badge--expired   { background: #fee2e2; color: #991b1b; }
.bk-item__badge--cancelled { background: #fee2e2; color: #b91c1c; }
.bk-item__badge--failed    { background: #fee2e2; color: #b91c1c; }
.bk-item__badge--pending   { background: #fff7ed; color: #c2410c; }

/* ── Empty sidebar state ───────────────────────────────────── */
.bk-list-empty {
    text-align: center;
    padding: 30px 16px;
    color: #8492a6;
    font-size: .8rem;
}

/* ── Right detail panel ────────────────────────────────────── */
.bk-detail {
    display: none;
}

.bk-detail.active {
    display: block;
}

.bk-detail-empty {
    background: #fff;
    border: 1px dashed #dde3ef;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 420px;
    color: #b0bac8;
    gap: 12px;
}

.bk-detail-empty i { font-size: 3rem; }
.bk-detail-empty p { font-size: .9rem; margin: 0; }

/* detail card */
.bk-dcard {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 16px;
}

.bk-dcard__head {
    padding: 16px 20px 14px;
    border-bottom: 1px solid #f0f3f8;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
}

.bk-dcard__title {
    font-size: 1rem;
    font-weight: 700;
    color: #1a2540;
    margin: 0;
}

.bk-dcard__sub {
    font-size: .75rem;
    color: #8492a6;
    margin-top: 2px;
}

.bk-dcard__body {
    padding: 18px 20px;
}

/* status badges */
.bk-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
}

.bk-badge--confirmed { background: #dcfce7; color: #15803d; }
.bk-badge--hold      { background: #fef9c3; color: #a16207; }
.bk-badge--expired   { background: #fee2e2; color: #991b1b; }
.bk-badge--cancelled { background: #fee2e2; color: #b91c1c; }
.bk-badge--failed    { background: #fee2e2; color: #b91c1c; }
.bk-badge--pending   { background: #fff7ed; color: #c2410c; }
.bk-badge--issued    { background: #dbeafe; color: #1d4ed8; }

/* hold-expired banner */
.bk-hold-banner--expired {
    background: linear-gradient(135deg, #fff1f2, #fee2e2);
    border-color: #fca5a5;
}
.bk-hold-banner--expired i { color: #b91c1c; }
.bk-hold-banner--expired .bk-hold-banner__title { color: #7f1d1d; }
.bk-hold-banner--expired .bk-hold-banner__text  { color: #991b1b; }

/* hold alert banner */
.bk-hold-banner {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 13px 16px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 16px;
}

.bk-hold-banner i {
    font-size: 1.3rem;
    color: #d97706;
    flex-shrink: 0;
    margin-top: 1px;
}

.bk-hold-banner__body {}
.bk-hold-banner__title { font-size: .82rem; font-weight: 700; color: #92400e; }
.bk-hold-banner__text  { font-size: .75rem; color: #78350f; line-height: 1.5; margin-top: 3px; }

/* info grid */
.bk-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px 16px;
}

.bk-info-item {}
.bk-info-label {
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8492a6;
    margin-bottom: 3px;
}

.bk-info-value {
    font-size: .82rem;
    font-weight: 600;
    color: #1a2540;
}

.bk-info-value.pnr {
    font-family: 'JetBrains Mono', monospace;
    font-size: .98rem;
    color: var(--c-brand, #cd1b4f);
    letter-spacing: .05em;
}

/* flight leg visual */
.bk-leg {
    border: 1px solid #e4e9f0;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 10px;
}

.bk-leg:last-child { margin-bottom: 0; }

.bk-leg__label {
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8492a6;
    margin-bottom: 10px;
}

.bk-leg__row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bk-leg__logo {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    object-fit: contain;
    border: 1px solid #e4e9f0;
    padding: 2px;
    background: #fafbff;
    flex-shrink: 0;
}

.bk-leg__city {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1a2540;
    letter-spacing: .02em;
}

.bk-leg__time {
    font-size: .75rem;
    color: #8492a6;
}

.bk-leg__bridge {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 0 8px;
}

.bk-leg__dur { font-size: .7rem; font-weight: 700; color: #4a5568; }

.bk-leg__track {
    display: flex;
    align-items: center;
    width: 100%;
}

.bk-leg__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #cbd5e0;
    flex-shrink: 0;
}

.bk-leg__line {
    flex: 1;
    height: 1px;
    background: #cbd5e0;
}

.bk-leg__via {
    font-size: .6rem;
    background: #f5f7fa;
    border: 1px solid #e4e9f0;
    border-radius: 4px;
    padding: 1px 5px;
    color: #4a5568;
    font-weight: 600;
    margin: 0 3px;
}

.bk-leg__stops { font-size: .68rem; color: #8492a6; font-weight: 600; }

/* passengers */
.bk-pax-list { display: flex; flex-direction: column; gap: 8px; }

.bk-pax-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    background: #f8faff;
    border: 1px solid #e4e9f0;
    border-radius: 8px;
}

.bk-pax-avatar {
    width: 30px;
    height: 30px;
    background: var(--c-brand, #cd1b4f);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.bk-pax-name { font-size: .82rem; font-weight: 600; color: #1a2540; }
.bk-pax-type { font-size: .68rem; color: #8492a6; margin-top: 1px; }

.bk-pax-passport {
    margin-left: auto;
    font-size: .68rem;
    color: #8492a6;
    font-family: monospace;
}

/* amount */
.bk-amount-rows { display: flex; flex-direction: column; gap: 6px; }

.bk-amount-row {
    display: flex;
    justify-content: space-between;
    font-size: .8rem;
    color: #4a5568;
}

.bk-amount-row.total {
    border-top: 2px solid #e4e9f0;
    padding-top: 8px;
    margin-top: 4px;
    font-size: .9rem;
    font-weight: 700;
    color: #1a2540;
}

.bk-amount-row.total .bk-amount-val { color: var(--c-brand, #cd1b4f); }

/* actions */
.bk-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.bk-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    border: none;
    text-decoration: none;
}

.bk-btn--danger {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.bk-btn--danger:hover { background: #dc2626; color: #fff; }

.bk-btn--warning {
    background: #fef9c3;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.bk-btn--warning:hover { background: #d97706; color: #fff; }

.bk-btn--outline {
    background: #fff;
    color: #4a5568;
    border: 1px solid #e4e9f0;
}

.bk-btn--outline:hover { background: #f5f7fa; }

/* section heading inside detail */
.bk-section-label {
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #8492a6;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.bk-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f0f3f8;
}

/* hotel card specific */
.bk-hotel-info { display: flex; flex-direction: column; gap: 4px; }
.bk-hotel-name { font-size: .95rem; font-weight: 700; color: #1a2540; }
.bk-hotel-meta { font-size: .78rem; color: #8492a6; }

/* responsive */
@media (max-width: 900px) {
    .bk-shell {
        grid-template-columns: 1fr;
    }
    .bk-sidebar {
        position: static;
        max-height: 300px;
    }
}
</style>
@endsection

@section('content')
@php
    $allBookings = collect();
    foreach ($flightBookings as $fb) {
        $allBookings->push(['type' => 'flight', 'obj' => $fb]);
    }
    foreach ($hotelBookings as $hb) {
        $allBookings->push(['type' => 'hotel', 'obj' => $hb]);
    }
    $allBookings = $allBookings->sortByDesc(fn($b) => $b['obj']->created_at);

    // First non-cancelled booking to show by default, else just first
    $defaultId = $allBookings
        ->first(fn($b) => $b['obj']->booking_status !== 'cancelled')?? $allBookings->first();
    $defaultKey = $defaultId ? ($defaultId['type'] . '-' . $defaultId['obj']->id) : null;

    $statusColor = function (string $s): string {
        return match($s) {
            'confirmed', 'completed' => 'confirmed',
            'hold'      => 'hold',
            'expired'   => 'expired',
            'cancelled' => 'cancelled',
            'failed'    => 'failed',
            default     => 'pending',
        };
    };

    /* Resolve hold expiry: returns true if a hold booking's deadline has passed */
    $isHoldExpired = function ($bookingObj): bool {
        if (($bookingObj->booking_status ?? '') !== 'hold') return false;
        // 1. Explicit column
        if (!empty($bookingObj->hold_expires_at)) {
            try { return \Carbon\Carbon::parse($bookingObj->hold_expires_at)->isPast(); } catch (\Throwable $e) {}
        }
        // 2. Ticketing deadline from Sabre response
        $ttl = data_get($bookingObj->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline')
            ?? data_get($bookingObj->booking_response, 'CreatePassengerNameRecordRS.TravelItineraryAddInfo.AgencyInfo.Ticketing.Date')
            ?? null;
        if ($ttl) {
            try { return \Carbon\Carbon::parse($ttl)->isPast(); } catch (\Throwable $e) {}
        }
        // 3. Fallback: 24 h after creation
        return $bookingObj->created_at->addHours(24)->isPast();
    };
@endphp

<div class="bk">
    <div class="container">

        {{-- page header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <div>
                <h1 style="font-size:1.3rem;font-weight:800;color:#1a2540;margin:0;">My Bookings</h1>
                <p style="font-size:.78rem;color:#8492a6;margin:0;">
                    {{ $allBookings->count() }} total &mdash;
                    {{ $flightBookings->count() }} flight{{ $flightBookings->count() !== 1 ? 's' : '' }},
                    {{ $hotelBookings->count() }} hotel{{ $hotelBookings->count() !== 1 ? 's' : '' }}
                </p>
            </div>
            <a href="{{ route('user.flights.index') }}" class="bk-btn bk-btn--outline ms-auto" style="text-decoration:none;">
                <i class="bx bxs-plane-take-off"></i> New Flight Search
            </a>
        </div>

        @if($allBookings->isEmpty())
            <div class="bk-detail-empty" style="margin:0 auto;max-width:480px;">
                <i class="bx bx-calendar-x"></i>
                <p>No bookings yet.</p>
                <a href="{{ route('user.flights.index') }}" class="bk-btn bk-btn--outline">Search Flights</a>
            </div>
        @else
        <div class="bk-shell">

            {{-- ── LEFT SIDEBAR ── --}}
            <aside class="bk-sidebar">
                <div class="bk-sidebar__head">
                    <div class="bk-sidebar__title">All Bookings</div>
                    <div class="bk-sidebar__search">
                        <i class="bx bx-search"></i>
                        <input type="text" id="bkSearch" placeholder="Search booking #, route…">
                    </div>
                </div>

                {{-- type tabs --}}
                <div class="bk-tabs">
                    <button class="bk-tab active" data-bk-type="all">All <span style="opacity:.65;">({{ $allBookings->count() }})</span></button>
                    <button class="bk-tab" data-bk-type="flight"><i class="bx bx-plane"></i> Flights <span style="opacity:.65;">({{ $flightBookings->count() }})</span></button>
                    <button class="bk-tab" data-bk-type="hotel"><i class="bx bx-hotel"></i> Hotels <span style="opacity:.65;">({{ $hotelBookings->count() }})</span></button>
                </div>

                {{-- status filter chips --}}
                <div class="bk-filters">
                    <button class="bk-filter-chip active" data-bk-status="all">All</button>
                    <button class="bk-filter-chip" data-bk-status="hold">On Hold</button>
                    <button class="bk-filter-chip" data-bk-status="expired" style="border-color:#fca5a5;color:#991b1b;">Expired</button>
                    <button class="bk-filter-chip" data-bk-status="confirmed">Confirmed</button>
                    <button class="bk-filter-chip" data-bk-status="cancelled">Cancelled</button>
                </div>

                {{-- booking list --}}
                <div class="bk-list" id="bkList">
                    @foreach($allBookings as $bk)
                        @php
                            $bkObj  = $bk['obj'];
                            $bkType = $bk['type'];
                            $bkKey  = $bkType . '-' . $bkObj->id;
                            $bkStatus = $bkObj->booking_status;
                            if ($bkStatus === 'completed') $bkStatus = 'confirmed';
                            if ($bkStatus === 'hold' && $isHoldExpired($bkObj)) $bkStatus = 'expired';

                            if ($bkType === 'flight') {
                                $bkRoute = strtoupper($bkObj->from_airport ?? '') . ' → ' . strtoupper($bkObj->to_airport ?? '');
                                $bkMeta  = $bkObj->departure_date?->format('d M Y') ?? ' - ';
                            } else {
                                $bkRoute = $bkObj->hotel_name ?? 'Hotel Booking';
                                $bkMeta  = ($bkObj->check_in_date?->format('d M') ?? '') . ' – ' . ($bkObj->check_out_date?->format('d M Y') ?? '');
                            }
                        @endphp
                        <div class="bk-item {{ $bkKey === $defaultKey ? 'active' : '' }}"
                             data-bk-key="{{ $bkKey }}"
                             data-bk-type="{{ $bkType }}"
                             data-bk-status="{{ $bkStatus }}"
                             data-bk-search="{{ strtolower($bkObj->booking_number . ' ' . $bkRoute) }}"
                             onclick="showDetail('{{ $bkKey }}')">
                            <div class="bk-item__icon bk-item__icon--{{ $bkType }}">
                                <i class="bx {{ $bkType === 'flight' ? 'bx-plane' : 'bx-hotel' }}"></i>
                            </div>
                            <div class="bk-item__body">
                                <div class="bk-item__num">{{ $bkObj->booking_number }}</div>
                                <div class="bk-item__route">{{ $bkRoute }}</div>
                                <div class="bk-item__meta">{{ $bkMeta }}</div>
                            </div>
                            <span class="bk-item__badge bk-item__badge--{{ $bkStatus }}">
                                @if($bkStatus === 'hold') On Hold
                                @elseif($bkStatus === 'expired') Expired
                                @else {{ ucfirst($bkStatus) }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                    <div class="bk-list-empty" id="bkNoResults" style="display:none;">
                        No bookings match your filter.
                    </div>
                </div>
            </aside>

            {{-- ── RIGHT DETAIL PANEL ── --}}
            <main>

                {{-- Empty state shown when nothing selected --}}
                <div id="bk-empty-state" class="{{ $defaultKey ? 'd-none' : '' }} bk-detail-empty">
                    <i class="bx bx-receipt"></i>
                    <p>Select a booking to view its details.</p>
                </div>

                {{-- ── FLIGHT DETAIL PANELS ── --}}
                @foreach($flightBookings as $booking)
                @php
                    $bkKey     = 'flight-' . $booking->id;
                    $bkStatus  = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                    $isHold    = ($booking->payment_method === 'hold');
                    if ($bkStatus === 'hold' && $isHoldExpired($booking)) $bkStatus = 'expired';
                    $passengers = $booking->passengers_data['passengers'] ?? [];
                    $lead       = $booking->passengers_data['lead'] ?? [];
                    $legs       = $booking->itinerary_data['legs'] ?? [];
                    $totalPax   = max(1, $booking->adults + $booking->children + $booking->infants);

                    // airline logo helper
                    $bkLogo = function(?string $c): string {
                        return 'https://pics.avs.io/60/60/' . strtoupper(trim($c ?: 'XX')) . '.png';
                    };

                    // duration helper
                    $bkFmt = function(?int $m): string {
                        if (!$m || $m < 1) return ' - ';
                        $h = intdiv($m, 60); $r = $m % 60;
                        return $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$r}m";
                    };

                    $ttlDate = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline')
                            ?? data_get($booking->booking_response, 'CreatePassengerNameRecordRS.TravelItineraryAddInfo.AgencyInfo.Ticketing.Date')
                            ?? null;
                @endphp
                <div id="detail-{{ $bkKey }}" class="bk-detail {{ $bkKey === $defaultKey ? 'active' : '' }}">

                    {{-- Hold / Expired banner --}}
                    @if($isHold && !in_array($bkStatus, ['cancelled']))
                    <div class="bk-hold-banner {{ $bkStatus === 'expired' ? 'bk-hold-banner--expired' : '' }}">
                        <i class="bx {{ $bkStatus === 'expired' ? 'bx-error-circle' : 'bx-time-five' }}"></i>
                        <div class="bk-hold-banner__body">
                            @if($bkStatus === 'expired')
                                <div class="bk-hold-banner__title">Hold Expired &mdash; ticketing deadline has passed</div>
                                <div class="bk-hold-banner__text">
                                    PNR <strong>{{ $booking->sabre_record_locator ?? 'Pending' }}</strong> may have been auto-cancelled by the airline.
                                    @if($ttlDate) The ticketing deadline was <strong>{{ $ttlDate }}</strong>. @endif
                                    Please contact support or check the PNR directly on Sabre.
                                    Hold created: <strong>{{ $booking->created_at->format('d M Y, h:i A') }}</strong>.
                                </div>
                            @else
                                <div class="bk-hold-banner__title">This booking is On Hold &mdash; no payment charged</div>
                                <div class="bk-hold-banner__text">
                                    PNR <strong>{{ $booking->sabre_record_locator ?? 'Pending' }}</strong> has been created on Sabre.
                                    Ticketing time limit is set by the airline on the PNR.
                                    Typical hold window is <strong>1–24 hours</strong>  -  confirm ticketing before it expires to avoid auto-cancellation.<br>
                                    @if($ttlDate) Ticketing deadline: <strong>{{ $ttlDate }}</strong>. @endif
                                    Hold created: <strong>{{ $booking->created_at->format('d M Y, h:i A') }}</strong>.
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Header card --}}
                    <div class="bk-dcard">
                        <div class="bk-dcard__head">
                            <div>
                                <div class="bk-dcard__title">
                                    {{ strtoupper($booking->from_airport ?? '') }}
                                    @if($booking->return_date) ⇄ @else → @endif
                                    {{ strtoupper($booking->to_airport ?? '') }}
                                </div>
                                <div class="bk-dcard__sub">{{ $booking->booking_number }}</div>
                            </div>
                            <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                                <span class="bk-badge bk-badge--{{ $bkStatus }}">
                                    @php
                                        $badgeIcon = match($bkStatus) {
                                            'hold'      => 'bx-time-five',
                                            'expired'   => 'bx-error-circle',
                                            'confirmed' => 'bx-check-circle',
                                            default     => 'bx-x-circle',
                                        };
                                        $badgeLabel = match($bkStatus) {
                                            'hold'    => 'On Hold',
                                            'expired' => 'Hold Expired',
                                            default   => ucfirst($bkStatus),
                                        };
                                    @endphp
                                    <i class="bx {{ $badgeIcon }}"></i> {{ $badgeLabel }}
                                </span>
                                @if($booking->ticket_status)
                                    <span class="bk-badge bk-badge--{{ $booking->ticket_status }}">
                                        <i class="bx bx-receipt"></i> {{ ucfirst($booking->ticket_status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="bk-dcard__body">
                            {{-- Key info grid --}}
                            <div class="bk-info-grid mb-4">
                                @if($booking->sabre_record_locator)
                                <div class="bk-info-item" style="grid-column: span 1;">
                                    <div class="bk-info-label"><i class="bx bx-hash"></i> PNR / Locator</div>
                                    <div class="bk-info-value pnr">{{ $booking->sabre_record_locator }}</div>
                                </div>
                                @endif
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Departure</div>
                                    <div class="bk-info-value">{{ $booking->departure_date?->format('d M Y') ?? ' - ' }}</div>
                                </div>
                                @if($booking->return_date)
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Return</div>
                                    <div class="bk-info-value">{{ $booking->return_date->format('d M Y') }}</div>
                                </div>
                                @endif
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Passengers</div>
                                    <div class="bk-info-value">
                                        {{ $booking->adults }}A
                                        @if($booking->children) , {{ $booking->children }}C @endif
                                        @if($booking->infants) , {{ $booking->infants }}I @endif
                                    </div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Payment Method</div>
                                    <div class="bk-info-value">{{ $isHold ? 'Hold (Free)' : ucfirst($booking->payment_method ?? 'N/A') }}</div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Booked On</div>
                                    <div class="bk-info-value">{{ $booking->created_at->format('d M Y, h:i A') }}</div>
                                </div>
                                @if($booking->cancelled_at)
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Cancelled On</div>
                                    <div class="bk-info-value" style="color:#b91c1c;">{{ $booking->cancelled_at->format('d M Y, h:i A') }}</div>
                                </div>
                                @endif
                            </div>

                            {{-- Flight legs --}}
                            @if(!empty($legs))
                            <p class="bk-section-label"><i class="bx bx-plane"></i> Flight Route</p>
                            @foreach($legs as $li => $leg)
                                @php
                                    $segments  = $leg['segments'] ?? [];
                                    $firstSeg  = $segments[0] ?? [];
                                    $lastSeg   = end($segments);
                                    $stops     = count($segments) - 1;
                                    $carrier   = $firstSeg['marketing_carrier'] ?? ($firstSeg['airline'] ?? null);
                                    $durMins   = $leg['duration'] ?? null;
                                    $midApts   = [];
                                    for ($si = 0; $si < count($segments) - 1; $si++) {
                                        $midApts[] = $segments[$si]['arrival'] ?? $segments[$si]['arr'] ?? '';
                                    }
                                    $depCity   = $firstSeg['departure'] ?? $firstSeg['dep'] ?? ($li === 0 ? ($booking->from_airport ?? '') : ($booking->to_airport ?? ''));
                                    $arrCity   = $lastSeg['arrival']   ?? $lastSeg['arr']   ?? ($li === 0 ? ($booking->to_airport ?? '') : ($booking->from_airport ?? ''));
                                    $depTime   = $firstSeg['departure_time'] ?? $firstSeg['dep_time'] ?? '';
                                    $arrTime   = $lastSeg['arrival_time']   ?? $lastSeg['arr_time']   ?? '';
                                @endphp
                                <div class="bk-leg">
                                    <div class="bk-leg__label">{{ $li === 0 ? 'Outbound' : 'Return' }} &mdash; {{ strtoupper($depCity) }} to {{ strtoupper($arrCity) }}</div>
                                    <div class="bk-leg__row">
                                        <img src="{{ $bkLogo($carrier) }}" class="bk-leg__logo" alt="{{ $carrier }}" onerror="this.style.display='none'">
                                        <div style="min-width:60px">
                                            <div class="bk-leg__city">{{ strtoupper($depCity) }}</div>
                                            @if($depTime)<div class="bk-leg__time">{{ substr($depTime,0,5) }}</div>@endif
                                        </div>
                                        <div class="bk-leg__bridge">
                                            @if($durMins)<div class="bk-leg__dur">{{ $bkFmt($durMins) }}</div>@endif
                                            <div class="bk-leg__track">
                                                <span class="bk-leg__dot"></span>
                                                @foreach($midApts as $ma)
                                                    <span class="bk-leg__line" style="flex:1"></span>
                                                    <span class="bk-leg__via">{{ strtoupper($ma) }}</span>
                                                @endforeach
                                                <span class="bk-leg__line"></span>
                                                <span class="bk-leg__dot"></span>
                                            </div>
                                            <div class="bk-leg__stops">
                                                {{ $stops === 0 ? 'Non-stop' : ($stops . ' Stop' . ($stops > 1 ? 's' : '')) }}
                                            </div>
                                        </div>
                                        <div style="min-width:60px;text-align:right;">
                                            <div class="bk-leg__city">{{ strtoupper($arrCity) }}</div>
                                            @if($arrTime)<div class="bk-leg__time">{{ substr($arrTime,0,5) }}</div>@endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @endif

                        </div>
                    </div>

                    {{-- Passengers --}}
                    @if(!empty($passengers))
                    <div class="bk-dcard">
                        <div class="bk-dcard__head">
                            <div class="bk-dcard__title">Passengers</div>
                        </div>
                        <div class="bk-dcard__body">
                            <div class="bk-pax-list">
                                @foreach($passengers as $pi => $pax)
                                @php
                                    $pName = strtoupper(trim(($pax['title'] ?? '') . ' ' . ($pax['first_name'] ?? '') . ' ' . ($pax['last_name'] ?? '')));
                                    $initials = strtoupper(substr($pax['first_name'] ?? 'P', 0, 1) . substr($pax['last_name'] ?? '', 0, 1));
                                    $pType = match($pax['type'] ?? 'ADT') {
                                        'ADT'  => 'Adult',
                                        'C06', 'C11' => 'Child',
                                        'INF'  => 'Infant',
                                        default => ucfirst($pax['type'] ?? 'Passenger'),
                                    };
                                @endphp
                                <div class="bk-pax-item">
                                    <div class="bk-pax-avatar">{{ $initials }}</div>
                                    <div>
                                        <div class="bk-pax-name">{{ $pName }}</div>
                                        <div class="bk-pax-type">{{ $pType }} @if(!empty($pax['nationality'])) &bull; {{ strtoupper($pax['nationality']) }} @endif</div>
                                    </div>
                                    @if(!empty($pax['passport_no']))
                                        <div class="bk-pax-passport">{{ $pax['passport_no'] }}</div>
                                    @endif
                                </div>
                                @endforeach

                                @if(!empty($lead['email']) || !empty($lead['phone']))
                                <div class="bk-pax-item" style="background:#fff7ed;border-color:#fed7aa;">
                                    <div class="bk-pax-avatar" style="background:#ea580c;">
                                        <i class="bx bx-envelope" style="font-size:.8rem;"></i>
                                    </div>
                                    <div>
                                        <div class="bk-pax-name">Contact Details</div>
                                        <div class="bk-pax-type">
                                            @if(!empty($lead['phone'])) <i class="bx bx-phone"></i> {{ $lead['phone'] }} @endif
                                            @if(!empty($lead['email'])) &nbsp;&bull;&nbsp; <i class="bx bx-envelope"></i> {{ $lead['email'] }} @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Amount & Actions --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

                        <div class="bk-dcard">
                            <div class="bk-dcard__head"><div class="bk-dcard__title">Fare Summary</div></div>
                            <div class="bk-dcard__body">
                                <div class="bk-amount-rows">
                                    <div class="bk-amount-row">
                                        <span>Base Fare × {{ $totalPax }}</span>
                                        <span class="bk-amount-val">
                                            <span class="dirham">AED</span> {{ number_format((float)($booking->total_amount ?? 0), 2) }}
                                        </span>
                                    </div>
                                    @if($isHold)
                                    <div class="bk-amount-row">
                                        <span>Hold Deposit</span>
                                        <span class="bk-amount-val" style="color:#15803d;font-weight:700;">FREE</span>
                                    </div>
                                    @endif
                                    <div class="bk-amount-row total">
                                        <span>Total</span>
                                        <span class="bk-amount-val">
                                            <span class="dirham">AED</span> {{ number_format((float)($booking->total_amount ?? 0), 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bk-dcard">
                            <div class="bk-dcard__head"><div class="bk-dcard__title">Actions</div></div>
                            <div class="bk-dcard__body">
                                @if($bkStatus === 'cancelled')
                                    <p style="font-size:.8rem;color:#b91c1c;margin:0;">
                                        <i class="bx bx-x-circle"></i>
                                        Booking cancelled on {{ $booking->cancelled_at?->format('d M Y, h:i A') ?? ' - ' }}.
                                    </p>
                                @elseif($bkStatus === 'expired')
                                    <p style="font-size:.8rem;color:#991b1b;margin:0 0 10px;">
                                        <i class="bx bx-error-circle"></i>
                                        <strong>Hold has expired.</strong> The airline may have auto-cancelled PNR <strong>{{ $booking->sabre_record_locator ?? 'N/A' }}</strong>.
                                    </p>
                                    <div class="bk-actions">
                                        <form action="{{ route('user.bookings.flights.release-hold', $booking->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Mark this expired hold as released/cancelled?');">
                                            @csrf
                                            <button type="submit" class="bk-btn bk-btn--danger">
                                                <i class="bx bx-x-circle"></i> Mark as Cancelled
                                            </button>
                                        </form>
                                    </div>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:8px;">
                                        No payment was made. Marking as cancelled will update the booking status and remove it from active holds.
                                    </p>
                                @elseif($isHold)
                                    <div class="bk-actions">
                                        <form action="{{ route('user.bookings.flights.release-hold', $booking->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Release this hold? The PNR {{ $booking->sabre_record_locator }} will be cancelled on Sabre and cannot be recovered.');">
                                            @csrf
                                            <button type="submit" class="bk-btn bk-btn--warning">
                                                <i class="bx bx-x-circle"></i> Release Hold
                                            </button>
                                        </form>
                                    </div>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:8px;">
                                        Releasing the hold cancels the PNR at the airline end. No refund is needed since no payment was made.
                                    </p>
                                @elseif($bkStatus === 'confirmed' && $booking->payment_status === 'paid')
                                    <div class="bk-actions">
                                        <a href="{{ route('user.bookings.flights.cancel', $booking->id) }}"
                                           class="bk-btn bk-btn--danger"
                                           onclick="return confirm('Cancel this confirmed flight booking? Cancellation charges may apply.');">
                                            <i class="bx bx-x"></i> Cancel Booking
                                        </a>
                                    </div>
                                @else
                                    <p style="font-size:.8rem;color:#8492a6;margin:0;">No actions available for this booking.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>{{-- end flight detail --}}
                @endforeach

                {{-- ── HOTEL DETAIL PANELS ── --}}
                @foreach($hotelBookings as $booking)
                @php
                    $bkKey    = 'hotel-' . $booking->id;
                    $bkStatus = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
                @endphp
                <div id="detail-{{ $bkKey }}" class="bk-detail {{ $bkKey === $defaultKey ? 'active' : '' }}">

                    <div class="bk-dcard">
                        <div class="bk-dcard__head">
                            <div>
                                <div class="bk-dcard__title">{{ $booking->hotel_name ?? 'Hotel Booking' }}</div>
                                <div class="bk-dcard__sub">{{ $booking->booking_number }}</div>
                            </div>
                            <div class="ms-auto d-flex gap-2 align-items-center">
                                <span class="bk-badge bk-badge--{{ $bkStatus }}">
                                    <i class="bx {{ $bkStatus === 'confirmed' ? 'bx-check-circle' : 'bx-x-circle' }}"></i>
                                    {{ ucfirst($bkStatus) }}
                                </span>
                                <span class="bk-badge bk-badge--{{ $booking->payment_status }}">
                                    {{ ucfirst($booking->payment_status) }}
                                </span>
                            </div>
                        </div>
                        <div class="bk-dcard__body">
                            <div class="bk-info-grid mb-4">
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Check-in</div>
                                    <div class="bk-info-value">{{ $booking->check_in_date?->format('d M Y') ?? ' - ' }}</div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Check-out</div>
                                    <div class="bk-info-value">{{ $booking->check_out_date?->format('d M Y') ?? ' - ' }}</div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Supplier</div>
                                    <div class="bk-info-value">{{ ucfirst($booking->supplier ?? 'Yalago') }}</div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Payment</div>
                                    <div class="bk-info-value">{{ ucfirst($booking->payment_method ?? 'N/A') }}</div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Amount</div>
                                    <div class="bk-info-value" style="color:var(--c-brand,#cd1b4f);">{!! formatPrice($booking->total_amount) !!}</div>
                                </div>
                                <div class="bk-info-item">
                                    <div class="bk-info-label">Booked On</div>
                                    <div class="bk-info-value">{{ $booking->created_at->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            @if($bkStatus !== 'cancelled')
                            <p class="bk-section-label"><i class="bx bx-cog"></i> Actions</p>
                            <div class="bk-actions">
                                @if($bkStatus === 'confirmed' && $booking->payment_status === 'paid')
                                    @if(($booking->supplier ?? 'yalago') === 'tbo')
                                        <button type="button" class="bk-btn bk-btn--danger cancel-booking-btn-tbo"
                                                data-booking-id="{{ $booking->id }}">
                                            <i class="bx bx-x"></i> Cancel (TBO)
                                        </button>
                                    @else
                                        <button type="button" class="bk-btn bk-btn--danger cancel-booking-btn"
                                                data-booking-id="{{ $booking->id }}">
                                            <i class="bx bx-x"></i> Cancel Booking
                                        </button>
                                    @endif
                                @else
                                    <span style="font-size:.8rem;color:#8492a6;">No actions available.</span>
                                @endif
                            </div>
                            @else
                            <p style="font-size:.8rem;color:#b91c1c;margin:0;">
                                <i class="bx bx-x-circle"></i>
                                Booking cancelled{{ $booking->cancelled_at ? ' on ' . $booking->cancelled_at->format('d M Y') : '' }}.
                            </p>
                            @endif
                        </div>
                    </div>

                </div>{{-- end hotel detail --}}
                @endforeach

            </main>
        </div>
        @endif

    </div>
</div>

{{-- Hotel cancellation modal --}}
<div class="modal fade" id="cancelBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancellation Charges</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cancelBookingModalBody">
                <div class="text-center py-5">Loading cancellation policy…</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
// ── master-detail switch ────────────────────────────────────
function showDetail(key) {
    document.querySelectorAll('.bk-detail').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.bk-item').forEach(el => el.classList.remove('active'));

    const detail = document.getElementById('detail-' + key);
    if (detail) detail.classList.add('active');

    const item = document.querySelector('[data-bk-key="' + key + '"]');
    if (item) {
        item.classList.add('active');
        item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    const empty = document.getElementById('bk-empty-state');
    if (empty) empty.classList.add('d-none');
}

// ── filter tabs ─────────────────────────────────────────────
document.querySelectorAll('.bk-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.bk-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        applyFilters();
    });
});

document.querySelectorAll('.bk-filter-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.bk-filter-chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        applyFilters();
    });
});

document.getElementById('bkSearch')?.addEventListener('input', applyFilters);

function applyFilters() {
    const typeFilter   = document.querySelector('.bk-tab.active')?.dataset.bkType ?? 'all';
    const statusFilter = document.querySelector('.bk-filter-chip.active')?.dataset.bkStatus ?? 'all';
    const searchTerm   = (document.getElementById('bkSearch')?.value ?? '').toLowerCase().trim();

    let visible = 0;
    document.querySelectorAll('.bk-item').forEach(item => {
        const matchType   = typeFilter === 'all' || item.dataset.bkType === typeFilter;
        const matchStatus = statusFilter === 'all' || item.dataset.bkStatus === statusFilter;
        const matchSearch = !searchTerm || (item.dataset.bkSearch ?? '').includes(searchTerm);

        const show = matchType && matchStatus && matchSearch;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('bkNoResults').style.display = visible === 0 ? '' : 'none';
}

// ── hotel cancel (yalago) ────────────────────────────────────
$(document).on('click', '.cancel-booking-btn', function() {
    const bookingId = $(this).data('booking-id');
    const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
    $('#cancelBookingModalBody').html('<div class="text-center py-5">Loading cancellation policy…</div>');
    modal.show();

    $.post("{{ route('user.bookings.hotels.cancellation-charges') }}", {
        booking_id: bookingId,
        _token: "{{ csrf_token() }}"
    })
    .done(html => $('#cancelBookingModalBody').html(html))
    .fail(() => $('#cancelBookingModalBody').html('<p class="text-danger text-center py-3">Failed to load cancellation policy.</p>'));
});

// ── hotel cancel (tbo) ───────────────────────────────────────
$(document).on('click', '.cancel-booking-btn-tbo', function() {
    const bookingId = $(this).data('booking-id');
    if (!confirm('Cancel this booking?')) return;

    $.post("{{ route('user.bookings.hotels.cancel-tbo') }}", {
        booking_id: bookingId,
        _token: "{{ csrf_token() }}"
    })
    .done(() => window.location.reload())
    .fail(xhr => alert(xhr.responseJSON?.message || 'Unable to cancel booking.'));
});
</script>
@endpush
