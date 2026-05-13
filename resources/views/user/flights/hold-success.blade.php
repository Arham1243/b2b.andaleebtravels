@extends('user.layouts.main')

@section('css')
<style>
/* ── Page ──────────────────────────────────────────────────── */
.hs-page {
    min-height: 85vh;
    background: #f0fdf4;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    font-family: 'Inter', sans-serif;
}

/* ── Card ──────────────────────────────────────────────────── */
.hs-card {
    width: 100%;
    max-width: 560px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(16,185,129,.1), 0 2px 12px rgba(0,0,0,.06);
    overflow: hidden;
}

/* ── Header (green band) ───────────────────────────────────── */
.hs-header {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-bottom: 1px solid #a7f3d0;
    padding: 36px 32px 28px;
    text-align: center;
}

/* ── SVG animated check ────────────────────────────────────── */
.hs-check-wrap {
    margin: 0 auto 20px;
    width: 72px;
    height: 72px;
}

.hs-check-circle {
    fill: none;
    stroke: #10b981;
    stroke-width: 3;
    stroke-dasharray: 200;
    stroke-dashoffset: 200;
    animation: hs-draw-circle .55s ease forwards .1s;
    transform-origin: center;
}

.hs-check-tick {
    fill: none;
    stroke: #10b981;
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 60;
    stroke-dashoffset: 60;
    animation: hs-draw-tick .4s ease forwards .65s;
}

@keyframes hs-draw-circle {
    to { stroke-dashoffset: 0; }
}

@keyframes hs-draw-tick {
    to { stroke-dashoffset: 0; }
}

.hs-title {
    font-size: 1.45rem;
    font-weight: 800;
    color: #064e3b;
    margin: 0 0 6px;
    animation: hs-fade-up .4s ease both .9s;
}

.hs-sub {
    font-size: .85rem;
    color: #065f46;
    line-height: 1.6;
    margin: 0;
    animation: hs-fade-up .4s ease both 1s;
}

@keyframes hs-fade-up {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Body ──────────────────────────────────────────────────── */
.hs-body {
    padding: 28px 32px 32px;
    animation: hs-fade-up .5s ease both 1.1s;
}

/* PNR box */
.hs-pnr-box {
    background: #f8faff;
    border: 1.5px solid #e0e7ff;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 12px;
}

.hs-pnr-label {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #8492a6;
    margin-bottom: 4px;
}

.hs-pnr-value {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 1.8rem;
    font-weight: 800;
    color: #cd1b4f;
    letter-spacing: .08em;
    line-height: 1;
}

.hs-pnr-copy {
    width: 34px;
    height: 34px;
    border: 1px solid #e0e7ff;
    border-radius: 8px;
    background: #fff;
    color: #8492a6;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .15s;
    font-size: 1rem;
}

.hs-pnr-copy:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.hs-pnr-copy.copied { background: #10b981; color: #fff; border-color: #10b981; }

/* info rows */
.hs-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
    margin-bottom: 20px;
}

.hs-info-item {}
.hs-info-label {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: #b0bac8;
    margin-bottom: 3px;
}

.hs-info-value {
    font-size: .85rem;
    font-weight: 600;
    color: #1a2540;
}

/* route display */
.hs-route {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: #f8faff;
    border: 1px solid #e4e9f0;
    border-radius: 10px;
    padding: 14px 20px;
    margin-bottom: 20px;
}

.hs-route__city {
    font-size: 1.6rem;
    font-weight: 800;
    color: #1a2540;
    letter-spacing: .04em;
}

.hs-route__arrow {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    color: #8492a6;
}

.hs-route__arrow i { font-size: 1.1rem; color: #10b981; }

.hs-route__type {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #10b981;
}

/* hold notice */
.hs-hold-notice {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 13px 16px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 22px;
}

.hs-hold-notice i {
    font-size: 1.2rem;
    color: #d97706;
    flex-shrink: 0;
    margin-top: 1px;
}

.hs-hold-notice__title {
    font-size: .8rem;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 3px;
}

.hs-hold-notice__text {
    font-size: .74rem;
    color: #78350f;
    line-height: 1.55;
    margin: 0;
}

/* steps */
.hs-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 24px;
}

.hs-step {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.hs-step__num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #f0fdf4;
    border: 1.5px solid #a7f3d0;
    color: #065f46;
    font-size: .68rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.hs-step__text {
    font-size: .78rem;
    color: #4a5568;
    line-height: 1.5;
}

.hs-step__text strong { color: #1a2540; }

/* divider */
.hs-divider {
    height: 1px;
    background: #f0f3f8;
    margin: 0 0 22px;
}

/* actions */
.hs-actions {
    display: flex;
    gap: 10px;
}

.hs-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 11px 16px;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .15s;
    cursor: pointer;
    border: none;
}

.hs-btn--primary {
    background: #cd1b4f;
    color: #fff;
}

.hs-btn--primary:hover {
    background: #b01542;
    color: #fff;
}

.hs-btn--outline {
    background: #fff;
    color: #4a5568;
    border: 1.5px solid #e4e9f0;
}

.hs-btn--outline:hover {
    background: #f5f7fa;
    color: #1a2540;
}

/* booking number pill */
.hs-bk-num {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fff;
    border: 1px solid #a7f3d0;
    border-radius: 20px;
    padding: 3px 12px;
    font-size: .72rem;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 14px;
}
</style>
@endsection

@section('content')
@php
    $passengers  = $booking->passengers_data['passengers'] ?? [];
    $lead        = $booking->passengers_data['lead'] ?? [];
    $adults      = (int) $booking->adults;
    $children    = (int) $booking->children;
    $infants     = (int) $booking->infants;
    $isRound     = !empty($booking->return_date);
    $paxStr      = $adults . ' Adult' . ($adults > 1 ? 's' : '');
    if ($children) $paxStr .= ', ' . $children . ' Child' . ($children > 1 ? 'ren' : '');
    if ($infants)  $paxStr .= ', ' . $infants . ' Infant' . ($infants > 1 ? 's' : '');

    // Try to extract TTL from Sabre response
    $ttl = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline')
        ?? data_get($booking->booking_response, 'CreatePassengerNameRecordRS.TravelItineraryAddInfo.AgencyInfo.Ticketing.Date')
        ?? null;

    // Lead passenger name
    $leadName = trim(
        strtoupper(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''))
    ) ?: 'Passenger';
@endphp

<div class="hs-page">
    <div class="hs-card">

        {{-- ── GREEN HEADER ── --}}
        <div class="hs-header">

            {{-- Animated SVG check --}}
            <div class="hs-check-wrap">
                <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg" width="72" height="72">
                    <circle cx="36" cy="36" r="33"
                        class="hs-check-circle"
                        stroke-linecap="round"/>
                    <polyline points="22,37 31,46 50,27"
                        class="hs-check-tick"/>
                </svg>
            </div>

            <div class="hs-bk-num">
                <i class="bx bx-hash"></i>{{ $booking->booking_number }}
            </div>

            <h1 class="hs-title">Booking Placed on Hold!</h1>
            <p class="hs-sub">
                No payment has been charged.<br>
                Your PNR is created — complete ticketing before it expires.
            </p>
        </div>

        {{-- ── BODY ── --}}
        <div class="hs-body">

            {{-- PNR --}}
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

            {{-- Route --}}
            <div class="hs-route">
                <div style="text-align:center;">
                    <div class="hs-route__city">{{ strtoupper($booking->from_airport ?? '—') }}</div>
                    <div style="font-size:.68rem;color:#8492a6;margin-top:2px;">{{ $booking->departure_date?->format('d M Y') ?? '' }}</div>
                </div>
                <div class="hs-route__arrow">
                    @if($isRound)
                        <i class="bx bx-transfer-alt"></i>
                        <span class="hs-route__type">Round Trip</span>
                    @else
                        <i class="bx bx-right-arrow-alt"></i>
                        <span class="hs-route__type">One Way</span>
                    @endif
                </div>
                <div style="text-align:center;">
                    <div class="hs-route__city">{{ strtoupper($booking->to_airport ?? '—') }}</div>
                    @if($isRound)
                    <div style="font-size:.68rem;color:#8492a6;margin-top:2px;">{{ $booking->return_date->format('d M Y') }}</div>
                    @endif
                </div>
            </div>

            {{-- Key info --}}
            <div class="hs-info-grid">
                <div class="hs-info-item">
                    <div class="hs-info-label">Passengers</div>
                    <div class="hs-info-value">{{ $paxStr }}</div>
                </div>
                <div class="hs-info-item">
                    <div class="hs-info-label">Lead Passenger</div>
                    <div class="hs-info-value">{{ $leadName }}</div>
                </div>
                @if(!empty($lead['email']))
                <div class="hs-info-item">
                    <div class="hs-info-label">Contact Email</div>
                    <div class="hs-info-value" style="font-size:.78rem;">{{ $lead['email'] }}</div>
                </div>
                @endif
                @if(!empty($lead['phone']))
                <div class="hs-info-item">
                    <div class="hs-info-label">Contact Phone</div>
                    <div class="hs-info-value" style="font-size:.78rem;">{{ $lead['phone'] }}</div>
                </div>
                @endif
                <div class="hs-info-item">
                    <div class="hs-info-label">Total Fare</div>
                    <div class="hs-info-value" style="color:#cd1b4f;">
                        <span class="dirham">AED</span> {{ number_format((float)$booking->total_amount, 2) }}
                    </div>
                </div>
                <div class="hs-info-item">
                    <div class="hs-info-label">Hold Deposit</div>
                    <div class="hs-info-value" style="color:#10b981;font-weight:800;">FREE</div>
                </div>
                <div class="hs-info-item">
                    <div class="hs-info-label">Held On</div>
                    <div class="hs-info-value">{{ $booking->created_at->format('d M Y, h:i A') }}</div>
                </div>
                @if($ttl)
                <div class="hs-info-item">
                    <div class="hs-info-label">Ticketing Deadline</div>
                    <div class="hs-info-value" style="color:#d97706;">{{ $ttl }}</div>
                </div>
                @endif
            </div>

            {{-- Hold warning --}}
            <div class="hs-hold-notice">
                <i class="bx bx-time-five"></i>
                <div>
                    <div class="hs-hold-notice__title">Complete ticketing before the hold expires</div>
                    <p class="hs-hold-notice__text">
                        The airline sets the exact ticketing time limit on PNR <strong>{{ $booking->sabre_record_locator ?? '—' }}</strong>.
                        Typical hold window is <strong>1–24 hours</strong>. If not ticketed in time, the airline will auto-cancel the PNR.
                        You can also release the hold manually from <strong>My Bookings</strong> if no longer needed.
                    </p>
                </div>
            </div>

            <div class="hs-divider"></div>

            {{-- Next steps --}}
            <div class="hs-steps">
                <div class="hs-step">
                    <div class="hs-step__num">1</div>
                    <div class="hs-step__text"><strong>Check your email</strong> — a confirmation with PNR details has been sent to <strong>{{ $lead['email'] ?? 'your contact email' }}</strong>.</div>
                </div>
                <div class="hs-step">
                    <div class="hs-step__num">2</div>
                    <div class="hs-step__text"><strong>Confirm ticketing</strong> — Go to My Bookings and issue the ticket before the hold expires.</div>
                </div>
                <div class="hs-step">
                    <div class="hs-step__num">3</div>
                    <div class="hs-step__text"><strong>Not proceeding?</strong> — Release the hold from My Bookings so the PNR is cancelled cleanly at the airline end.</div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="hs-actions">
                <a href="{{ route('user.bookings.index') }}" class="hs-btn hs-btn--primary">
                    <i class="bx bx-list-ul"></i> View My Bookings
                </a>
                <a href="{{ route('user.flights.index') }}" class="hs-btn hs-btn--outline">
                    <i class="bx bxs-plane-take-off"></i> New Search
                </a>
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
        const btn  = document.getElementById('hsCopyBtn');
        const icon = document.getElementById('hsCopyIcon');
        btn.classList.add('copied');
        icon.className = 'bx bx-check';
        setTimeout(() => {
            btn.classList.remove('copied');
            icon.className = 'bx bx-copy';
        }, 2000);
    });
}
</script>
@endpush
