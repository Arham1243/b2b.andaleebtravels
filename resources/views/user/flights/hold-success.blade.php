@extends('user.layouts.main')

@section('css')
<style>
/* ── Page shell — fills viewport, no scroll ───────────────── */
.hs-page {
    min-height: calc(100vh - 120px);
    background: #f0fdf4;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 16px;
}

/* ── Card ─────────────────────────────────────────────────── */
.hs-card {
    width: 100%;
    max-width: 680px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 40px rgba(16,185,129,.12), 0 2px 12px rgba(0,0,0,.06);
    overflow: hidden;
}

/* ── Header strip ─────────────────────────────────────────── */
.hs-header {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-bottom: 1px solid #a7f3d0;
    padding: 18px 24px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

/* Animated SVG check */
.hs-check-wrap { width: 48px; height: 48px; flex-shrink: 0; }

.hs-check-circle {
    fill: none;
    stroke: #10b981;
    stroke-width: 3;
    stroke-dasharray: 160;
    stroke-dashoffset: 160;
    animation: hs-draw-circle .5s ease forwards .1s;
}

.hs-check-tick {
    fill: none;
    stroke: #10b981;
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 50;
    stroke-dashoffset: 50;
    animation: hs-draw-tick .35s ease forwards .6s;
}

@keyframes hs-draw-circle { to { stroke-dashoffset: 0; } }
@keyframes hs-draw-tick   { to { stroke-dashoffset: 0; } }

.hs-header__text { flex: 1; }

.hs-header__title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #064e3b;
    margin: 0 0 3px;
}

.hs-header__sub {
    font-size: .73rem;
    color: #065f46;
    margin: 0;
}

.hs-bk-num {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #fff;
    border: 1px solid #a7f3d0;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: .68rem;
    font-weight: 700;
    color: #065f46;
    white-space: nowrap;
}

/* ── Body (two columns) ───────────────────────────────────── */
.hs-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    animation: hs-fadein .4s ease both .7s;
}

@keyframes hs-fadein {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* LEFT col */
.hs-left {
    padding: 18px 20px;
    border-right: 1px solid #f0f3f8;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* PNR box */
.hs-pnr-box {
    background: #f8faff;
    border: 1.5px solid #e0e7ff;
    border-radius: 10px;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.hs-pnr-label {
    font-size: .58rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #8492a6;
    margin-bottom: 3px;
}

.hs-pnr-value {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 1.55rem;
    font-weight: 800;
    color: #cd1b4f;
    letter-spacing: .08em;
    line-height: 1;
}

.hs-pnr-copy {
    width: 30px; height: 30px;
    border: 1px solid #e0e7ff;
    border-radius: 7px;
    background: #fff;
    color: #8492a6;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .15s;
    font-size: .9rem;
}

.hs-pnr-copy:hover  { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.hs-pnr-copy.copied { background: #10b981; color: #fff; border-color: #10b981; }

/* Route */
.hs-route {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #f8faff;
    border: 1px solid #e4e9f0;
    border-radius: 10px;
    padding: 12px 14px;
}

.hs-route__city {
    font-size: 1.4rem;
    font-weight: 800;
    color: #1a2540;
    letter-spacing: .04em;
}

.hs-route__mid {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    color: #10b981;
    font-size: .95rem;
}

.hs-route__type {
    font-size: .58rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #10b981;
}

.hs-route__date {
    font-size: .63rem;
    color: #8492a6;
    margin-top: 2px;
}

/* Hold notice — compact */
.hs-notice {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 9px 11px;
    font-size: .72rem;
    color: #78350f;
    line-height: 1.45;
}

.hs-notice i { color: #d97706; font-size: .95rem; flex-shrink: 0; margin-top: 1px; }

/* Actions */
.hs-actions {
    display: flex;
    gap: 8px;
    margin-top: auto;
}

.hs-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .15s;
    border: none;
    cursor: pointer;
}

.hs-btn--primary { background: #cd1b4f; color: #fff; }
.hs-btn--primary:hover { background: #b01542; color: #fff; }
.hs-btn--outline { background: #fff; color: #4a5568; border: 1.5px solid #e4e9f0; }
.hs-btn--outline:hover { background: #f5f7fa; color: #1a2540; }

/* RIGHT col */
.hs-right {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.hs-info-rows {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.hs-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid #f0f3f8;
    gap: 10px;
}

.hs-info-row:last-child { border-bottom: none; }

.hs-info-label {
    font-size: .63rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #b0bac8;
    flex-shrink: 0;
}

.hs-info-value {
    font-size: .78rem;
    font-weight: 600;
    color: #1a2540;
    text-align: right;
}
</style>
@endsection

@section('content')
@php
    $passengers = $booking->passengers_data['passengers'] ?? [];
    $lead       = $booking->passengers_data['lead'] ?? [];
    $adults     = (int) $booking->adults;
    $children   = (int) $booking->children;
    $infants    = (int) $booking->infants;
    $isRound    = !empty($booking->return_date);
    $paxStr     = $adults . ' Adult' . ($adults > 1 ? 's' : '');
    if ($children) $paxStr .= ', ' . $children . 'C';
    if ($infants)  $paxStr .= ', ' . $infants . 'I';
    $leadName = strtoupper(trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''))) ?: '—';
    $ttl = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline') ?? null;
@endphp

<div class="hs-page">
    <div class="hs-card">

        {{-- ── HEADER ── --}}
        <div class="hs-header">
            <div class="hs-check-wrap">
                <svg viewBox="0 0 72 72" fill="none" width="48" height="48">
                    <circle cx="36" cy="36" r="33" class="hs-check-circle" stroke-linecap="round"/>
                    <polyline points="22,37 31,46 50,27" class="hs-check-tick"/>
                </svg>
            </div>
            <div class="hs-header__text">
                <h1 class="hs-header__title">Booking Placed on Hold!</h1>
                <p class="hs-header__sub">No payment charged &nbsp;·&nbsp; PNR created on Sabre &nbsp;·&nbsp; Complete ticketing before expiry</p>
            </div>
            <div class="hs-bk-num"><i class="bx bx-hash"></i>{{ $booking->booking_number }}</div>
        </div>

        {{-- ── BODY (2 cols) ── --}}
        <div class="hs-body">

            {{-- LEFT: PNR + route + warning + actions --}}
            <div class="hs-left">

                @if($booking->sabre_record_locator)
                <div class="hs-pnr-box">
                    <div>
                        <div class="hs-pnr-label">PNR / Record Locator</div>
                        <div class="hs-pnr-value" id="hsPnr">{{ $booking->sabre_record_locator }}</div>
                    </div>
                    <button class="hs-pnr-copy" id="hsCopyBtn" onclick="copyPnr()" title="Copy PNR">
                        <i class="bx bx-copy" id="hsCopyIcon"></i>
                    </button>
                </div>
                @endif

                <div class="hs-route">
                    <div style="text-align:center;">
                        <div class="hs-route__city">{{ strtoupper($booking->from_airport ?? '—') }}</div>
                        <div class="hs-route__date">{{ $booking->departure_date?->format('d M Y') ?? '' }}</div>
                    </div>
                    <div class="hs-route__mid">
                        <i class="{{ $isRound ? 'bx bx-transfer-alt' : 'bx bx-right-arrow-alt' }}"></i>
                        <span class="hs-route__type">{{ $isRound ? 'Round Trip' : 'One Way' }}</span>
                    </div>
                    <div style="text-align:center;">
                        <div class="hs-route__city">{{ strtoupper($booking->to_airport ?? '—') }}</div>
                        <div class="hs-route__date">{{ $isRound ? $booking->return_date->format('d M Y') : '' }}</div>
                    </div>
                </div>

                <div class="hs-notice">
                    <i class="bx bx-time-five"></i>
                    <span>
                        Airline sets the exact ticketing deadline on the PNR.
                        @if($ttl) Deadline: <strong>{{ $ttl }}</strong>. @else Typical window: <strong>1–24 hours</strong>. @endif
                        Release from My Bookings if no longer needed.
                    </span>
                </div>

                <div class="hs-actions">
                    <a href="{{ route('user.bookings.index') }}" class="hs-btn hs-btn--primary">
                        <i class="bx bx-list-ul"></i> My Bookings
                    </a>
                    <a href="{{ route('user.flights.index') }}" class="hs-btn hs-btn--outline">
                        <i class="bx bxs-plane-take-off"></i> New Search
                    </a>
                </div>

            </div>

            {{-- RIGHT: booking info rows --}}
            <div class="hs-right">
                <div class="hs-info-rows">
                    <div class="hs-info-row">
                        <span class="hs-info-label">Passengers</span>
                        <span class="hs-info-value">{{ $paxStr }}</span>
                    </div>
                    <div class="hs-info-row">
                        <span class="hs-info-label">Lead Passenger</span>
                        <span class="hs-info-value">{{ $leadName }}</span>
                    </div>
                    @if(!empty($lead['email']))
                    <div class="hs-info-row">
                        <span class="hs-info-label">Email</span>
                        <span class="hs-info-value" style="font-size:.72rem;word-break:break-all;">{{ $lead['email'] }}</span>
                    </div>
                    @endif
                    @if(!empty($lead['phone']))
                    <div class="hs-info-row">
                        <span class="hs-info-label">Phone</span>
                        <span class="hs-info-value">{{ $lead['phone'] }}</span>
                    </div>
                    @endif
                    <div class="hs-info-row">
                        <span class="hs-info-label">Total Fare</span>
                        <span class="hs-info-value" style="color:#cd1b4f;font-size:.88rem;">
                            <span class="dirham">AED</span> {{ number_format((float)$booking->total_amount, 2) }}
                        </span>
                    </div>
                    <div class="hs-info-row">
                        <span class="hs-info-label">Hold Deposit</span>
                        <span class="hs-info-value" style="color:#10b981;font-weight:800;">FREE</span>
                    </div>
                    <div class="hs-info-row">
                        <span class="hs-info-label">Held On</span>
                        <span class="hs-info-value">{{ $booking->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @if($ttl)
                    <div class="hs-info-row">
                        <span class="hs-info-label">TTL</span>
                        <span class="hs-info-value" style="color:#d97706;">{{ $ttl }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function copyPnr() {
    const pnr = document.getElementById('hsPnr')?.innerText?.trim();
    if (!pnr) return;
    navigator.clipboard.writeText(pnr).then(() => {
        const btn = document.getElementById('hsCopyBtn');
        const icon = document.getElementById('hsCopyIcon');
        btn.classList.add('copied');
        icon.className = 'bx bx-check';
        setTimeout(() => { btn.classList.remove('copied'); icon.className = 'bx bx-copy'; }, 2000);
    });
}
</script>
@endpush
