@extends('user.layouts.main')

@section('css')
<style>
.hs-page {
    min-height: calc(100vh - 120px);
    background: #f0fdf4;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
}

.hs-card {
    width: 100%;
    max-width: 480px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(16,185,129,.12), 0 2px 12px rgba(0,0,0,.06);
    overflow: hidden;
}

/* ── Header ── */
.hs-header {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-bottom: 1px solid #a7f3d0;
    padding: 24px 24px 20px;
    text-align: center;
}

.hs-check-wrap { width: 52px; height: 52px; margin: 0 auto 12px; }

.hs-check-circle {
    fill: none; stroke: #10b981; stroke-width: 3;
    stroke-dasharray: 170; stroke-dashoffset: 170;
    animation: hs-circle .5s ease forwards .1s;
}
.hs-check-tick {
    fill: none; stroke: #10b981; stroke-width: 3.5;
    stroke-linecap: round; stroke-linejoin: round;
    stroke-dasharray: 50; stroke-dashoffset: 50;
    animation: hs-tick .35s ease forwards .6s;
}
@keyframes hs-circle { to { stroke-dashoffset: 0; } }
@keyframes hs-tick   { to { stroke-dashoffset: 0; } }

.hs-title {
    font-size: 1.18rem;
    font-weight: 800;
    color: #064e3b;
    margin: 0 0 4px;
}

.hs-sub {
    font-size: .76rem;
    color: #065f46;
    margin: 0;
}

/* ── Body ── */
.hs-body {
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    animation: hs-in .4s ease both .75s;
}
@keyframes hs-in { from { opacity:0; transform:translateY(7px); } to { opacity:1; transform:none; } }

/* Booking # pill */
.hs-bk-num {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f0fdf4;
    border: 1px solid #a7f3d0;
    border-radius: 20px;
    padding: 3px 11px;
    font-size: .68rem;
    font-weight: 700;
    color: #065f46;
    align-self: center;
}

/* PNR box */
.hs-pnr {
    background: #f8faff;
    border: 1.5px solid #e0e7ff;
    border-radius: 10px;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.hs-pnr__label {
    font-size: .58rem; font-weight: 700;
    letter-spacing: .12em; text-transform: uppercase;
    color: #8492a6; margin-bottom: 3px;
}
.hs-pnr__value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.65rem; font-weight: 800;
    color: #cd1b4f; letter-spacing: .08em; line-height: 1;
}
.hs-pnr__copy {
    width: 30px; height: 30px;
    border: 1px solid #e0e7ff; border-radius: 7px;
    background: #fff; color: #8492a6;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0;
    transition: all .15s; font-size: .9rem;
}
.hs-pnr__copy:hover  { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.hs-pnr__copy.copied { background: #10b981; color: #fff; border-color: #10b981; }

/* Route row */
.hs-route {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: #f8faff;
    border: 1px solid #e4e9f0;
    border-radius: 10px;
    padding: 11px 14px;
}
.hs-route__city {
    font-size: 1.45rem; font-weight: 800;
    color: #1a2540; letter-spacing: .04em;
}
.hs-route__mid {
    display: flex; flex-direction: column;
    align-items: center; gap: 1px;
}
.hs-route__mid i { font-size: 1rem; color: #10b981; }
.hs-route__type {
    font-size: .56rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: #10b981;
}
.hs-route__date { font-size: .62rem; color: #8492a6; margin-top: 1px; }

/* Info chips row */
.hs-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.hs-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f5f7fa;
    border: 1px solid #e4e9f0;
    border-radius: 20px;
    padding: 4px 10px;
    font-size: .71rem;
    font-weight: 600;
    color: #1a2540;
}
.hs-chip i { color: #8492a6; font-size: .8rem; }
.hs-chip--green { background: #dcfce7; border-color: #a7f3d0; color: #15803d; }
.hs-chip--red   { background: #fdf2f5; border-color: #fbcfe8; color: #cd1b4f; }

/* Amber notice */
.hs-notice {
    display: flex; align-items: center; gap: 8px;
    background: #fffbeb; border: 1px solid #fcd34d;
    border-radius: 8px; padding: 9px 12px;
    font-size: .73rem; color: #78350f; line-height: 1.45;
}
.hs-notice i { color: #d97706; font-size: 1rem; flex-shrink: 0; }

/* Action buttons */
.hs-actions { display: flex; gap: 8px; }
.hs-btn {
    flex: 1;
    display: inline-flex; align-items: center;
    justify-content: center; gap: 6px;
    padding: 10px 14px; border-radius: 9px;
    font-size: .8rem; font-weight: 700;
    text-decoration: none; transition: all .15s;
    border: none; cursor: pointer;
}
.hs-btn--primary { background: #cd1b4f; color: #fff; }
.hs-btn--primary:hover { background: #b01542; color: #fff; }
.hs-btn--outline { background: #fff; color: #4a5568; border: 1.5px solid #e4e9f0; }
.hs-btn--outline:hover { background: #f5f7fa; color: #1a2540; }
</style>
@endsection

@section('content')
@php
    $lead      = $booking->passengers_data['lead'] ?? [];
    $adults    = (int) $booking->adults;
    $children  = (int) $booking->children;
    $infants   = (int) $booking->infants;
    $isRound   = !empty($booking->return_date);
    $paxStr    = $adults . ' Adult' . ($adults > 1 ? 's' : '');
    if ($children) $paxStr .= ', ' . $children . ' Child' . ($children > 1 ? 'ren' : '');
    if ($infants)  $paxStr .= ', ' . $infants . ' Infant' . ($infants > 1 ? 's' : '');
    $leadName  = strtoupper(trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''))) ?: null;
    $ttl       = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline') ?? null;
@endphp

<div class="hs-page">
  <div class="hs-card">

    {{-- Header --}}
    <div class="hs-header">
      <div class="hs-check-wrap">
        <svg viewBox="0 0 72 72" fill="none" width="52" height="52">
          <circle cx="36" cy="36" r="33" class="hs-check-circle" stroke-linecap="round"/>
          <polyline points="22,37 31,46 50,27" class="hs-check-tick"/>
        </svg>
      </div>
      <h1 class="hs-title">Booking Placed on Hold!</h1>
      <p class="hs-sub">No payment charged &nbsp;·&nbsp; PNR created on Sabre</p>
    </div>

    {{-- Body --}}
    <div class="hs-body">

      {{-- Booking # --}}
      <div class="hs-bk-num"><i class="bx bx-hash"></i>{{ $booking->booking_number }}</div>

      {{-- PNR --}}
      @if($booking->sabre_record_locator)
      <div class="hs-pnr">
        <div>
          <div class="hs-pnr__label">PNR / Record Locator</div>
          <div class="hs-pnr__value" id="hsPnr">{{ $booking->sabre_record_locator }}</div>
        </div>
        <button class="hs-pnr__copy" id="hsCopyBtn" onclick="copyPnr()" title="Copy">
          <i class="bx bx-copy" id="hsCopyIcon"></i>
        </button>
      </div>
      @endif

      {{-- Route --}}
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

      {{-- Info chips --}}
      <div class="hs-chips">
        <span class="hs-chip"><i class="bx bx-user"></i> {{ $paxStr }}</span>
        @if($leadName)
          <span class="hs-chip"><i class="bx bx-id-card"></i> {{ $leadName }}</span>
        @endif
        <span class="hs-chip hs-chip--red"><span class="dirham" style="font-size:.72rem;">AED</span> {{ number_format((float)$booking->total_amount, 2) }}</span>
        <span class="hs-chip hs-chip--green"><i class="bx bx-lock-open"></i> Hold Deposit FREE</span>
        <span class="hs-chip"><i class="bx bx-calendar"></i> {{ $booking->created_at->format('d M Y, h:i A') }}</span>
        @if(!empty($lead['email']))
          <span class="hs-chip"><i class="bx bx-envelope"></i> {{ $lead['email'] }}</span>
        @endif
      </div>

      {{-- Amber notice --}}
      <div class="hs-notice">
        <i class="bx bx-time-five"></i>
        <span>
          @if($ttl)
            Ticketing deadline: <strong>{{ $ttl }}</strong>.
          @else
            Ticketing window is typically <strong>1–24 hours</strong>  - set by the airline on the PNR.
          @endif
          Release from My Bookings if no longer needed.
        </span>
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
    const pnr  = document.getElementById('hsPnr')?.innerText?.trim();
    const btn  = document.getElementById('hsCopyBtn');
    const icon = document.getElementById('hsCopyIcon');
    if (!pnr) return;
    navigator.clipboard.writeText(pnr).then(() => {
        btn.classList.add('copied');
        icon.className = 'bx bx-check';
        setTimeout(() => { btn.classList.remove('copied'); icon.className = 'bx bx-copy'; }, 2000);
    });
}
</script>
@endpush
