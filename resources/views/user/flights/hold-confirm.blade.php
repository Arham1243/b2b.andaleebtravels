@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
<style>
/* ── Hold Confirm Page ────────────────────────────────── */
.hcnf-page { padding: 28px 0 48px; }
.hcnf-crumb {
    display: flex; align-items: center; gap: 6px;
    font-size: .82rem; color: var(--c-slate); margin-bottom: 20px;
}
.hcnf-crumb a { color: var(--c-slate); text-decoration: none; }
.hcnf-crumb a:hover { color: var(--c-primary); }
.hcnf-crumb i { font-size: .9rem; }

/* PNR banner */
.hcnf-pnr-banner {
    background: #fffbeb; border: 1px solid #fcd34d;
    border-radius: 10px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px;
}
.hcnf-pnr-banner i { color: #d97706; font-size: 1.3rem; flex-shrink: 0; }
.hcnf-pnr-banner__text { font-size: .85rem; color: #92400e; line-height: 1.4; }
.hcnf-pnr-banner__pnr { font-size: 1rem; font-weight: 700; color: #78350f; letter-spacing: .05em; }

/* Card */
.hcnf-card {
    background: #fff; border: 1px solid var(--c-border);
    border-radius: 12px; margin-bottom: 16px; overflow: hidden;
}
.hcnf-card__head {
    padding: 14px 18px; border-bottom: 1px solid var(--c-border);
    display: flex; align-items: center; gap: 10px;
    font-weight: 600; font-size: .9rem; color: var(--c-dark);
}
.hcnf-card__head i { color: var(--c-primary); font-size: 1.1rem; }
.hcnf-card__body { padding: 16px 18px; }

/* Route visual */
.hcnf-route {
    display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
}
.hcnf-route__iata { font-size: 1.4rem; font-weight: 700; color: var(--c-dark); }
.hcnf-route__line {
    flex: 1; display: flex; align-items: center; gap: 6px; color: var(--c-slate); font-size: .75rem;
}
.hcnf-route__dash { flex: 1; height: 1px; background: var(--c-border); }
.hcnf-route__plane { font-size: 1rem; color: var(--c-primary); }

.hcnf-meta { display: flex; flex-wrap: wrap; gap: 14px; font-size: .82rem; color: var(--c-slate); }
.hcnf-meta span { display: flex; align-items: center; gap: 5px; }
.hcnf-meta i { font-size: .95rem; color: var(--c-primary-light, #818cf8); }

/* Passengers list */
.hcnf-pax-list { display: flex; flex-direction: column; gap: 8px; }
.hcnf-pax-row {
    display: flex; align-items: center; gap: 10px; padding: 9px 12px;
    background: var(--c-light-bg, #f8fafc); border-radius: 8px; font-size: .84rem;
}
.hcnf-pax-row i { color: var(--c-primary); font-size: 1rem; }
.hcnf-pax-row__name { font-weight: 600; color: var(--c-dark); flex: 1; }
.hcnf-pax-row__type { font-size: .75rem; color: var(--c-slate); background: #e2e8f0; border-radius: 20px; padding: 2px 8px; }

/* Fare summary */
.hcnf-fare { display: flex; flex-direction: column; gap: 7px; }
.hcnf-fare__line { display: flex; justify-content: space-between; font-size: .84rem; color: var(--c-slate); }
.hcnf-fare__line + .hcnf-fare__divider { border: none; border-top: 1px dashed var(--c-border); margin: 4px 0; }
.hcnf-fare__total {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 10px; border-top: 2px solid var(--c-border);
    font-weight: 700; font-size: 1rem; color: var(--c-dark);
}
.hcnf-fare__total .dirham { font-size: 1.1rem; color: var(--c-primary); margin-right: 2px; }

/* Wallet toggle */
.hcnf-wallet {
    background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px;
}
.hcnf-wallet__row { display: flex; align-items: center; gap: 10px; }
.hcnf-wallet__icon { color: #16a34a; font-size: 1.2rem; }
.hcnf-wallet__info { flex: 1; }
.hcnf-wallet__label { font-size: .84rem; font-weight: 600; color: #15803d; }
.hcnf-wallet__balance { font-size: .78rem; color: #4ade80; }
.hcnf-wallet__switch { cursor: pointer; }
.hcnf-wallet-breakdown { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #86efac; display: none; }
.hcnf-wallet-breakdown p { margin: 0; font-size: .8rem; color: #166534; display: flex; justify-content: space-between; }

/* Payment options */
.hcnf-payment-options { display: flex; flex-direction: column; gap: 8px; }
.hcnf-payment-option input { display: none; }
.hcnf-payment-option__body {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px;
    border: 2px solid var(--c-border); border-radius: 10px; cursor: pointer;
    transition: border-color .15s, background .15s;
}
.hcnf-payment-option input:checked ~ .hcnf-payment-option__body {
    border-color: var(--c-primary); background: #f5f3ff;
}
.hcnf-payment-option__icon { font-size: 1.4rem; color: var(--c-primary); width: 30px; text-align: center; }
.hcnf-payment-option__name { font-size: .88rem; font-weight: 600; color: var(--c-dark); }
.hcnf-payment-option__desc { font-size: .76rem; color: var(--c-slate); }
.hcnf-payment-option__check { margin-left: auto; color: var(--c-primary); font-size: 1.1rem; display: none; }
.hcnf-payment-option input:checked ~ .hcnf-payment-option__body .hcnf-payment-option__check { display: block; }

/* Pay button */
.hcnf-pay-btn {
    width: 100%; padding: 13px 0; border-radius: 10px; border: none;
    background: var(--c-primary); color: #fff; font-size: .95rem; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background .15s; margin-top: 4px;
}
.hcnf-pay-btn:hover { background: var(--c-primary-dark, #4f46e5); }
.hcnf-secure { text-align: center; font-size: .76rem; color: var(--c-slate); margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 5px; }

/* Right summary */
.hcnf-summary {
    position: sticky; top: 80px;
    background: #fff; border: 1px solid var(--c-border); border-radius: 12px; overflow: hidden;
}
.hcnf-summary__head {
    padding: 13px 16px; background: var(--c-primary); color: #fff;
    font-weight: 600; font-size: .88rem; display: flex; align-items: center; gap: 8px;
}
.hcnf-summary__body { padding: 14px 16px; }
.hcnf-summary__line { display: flex; justify-content: space-between; font-size: .83rem; color: var(--c-slate); margin-bottom: 6px; }
.hcnf-summary__line--strong { font-weight: 600; color: var(--c-dark); }
.hcnf-summary__divider { border: none; border-top: 1px dashed var(--c-border); margin: 8px 0; }
.hcnf-summary__total {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0 0; border-top: 2px solid var(--c-border); font-weight: 700; font-size: 1rem; color: var(--c-dark);
}
.hcnf-summary__wallet-applied { background: #f0fdf4; border-radius: 8px; padding: 8px 10px; margin-top: 8px; font-size: .8rem; color: #166534; display: none; }
.hcnf-summary__wallet-applied p { margin: 0; display: flex; justify-content: space-between; }
</style>
@endsection

@section('content')
@php
    $pnr         = $booking->sabre_record_locator ?? '—';
    $from        = strtoupper($booking->from_airport ?? '');
    $to          = strtoupper($booking->to_airport ?? '');
    $isRound     = !empty($booking->return_date);
    $legs        = $booking->itinerary_data['legs'] ?? [];
    $passengers  = $booking->passengers_data['passengers'] ?? [];
    $lead        = $booking->passengers_data['lead'] ?? [];
    $totalPax    = max(1, $booking->adults + $booking->children + $booking->infants);

    $paxStr = $booking->adults . ' Adult' . ($booking->adults > 1 ? 's' : '');
    if ($booking->children) $paxStr .= ', ' . $booking->children . ' Child' . ($booking->children > 1 ? 'ren' : '');
    if ($booking->infants)  $paxStr .= ', ' . $booking->infants . ' Infant' . ($booking->infants > 1 ? 's' : '');

    $totalAmount = (float) $booking->total_amount;
    $taxAmount   = $booking->itinerary_data['tax'] ?? 0;
    $baseAmount  = $totalAmount - (float) $taxAmount;

    $ttl = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline') ?? null;
@endphp

<div class="hcnf-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav class="hcnf-crumb">
            <a href="{{ route('user.bookings.flights') }}">My Bookings</a>
            <i class="bx bx-chevron-right"></i>
            <a href="{{ route('user.bookings.flights.detail', $booking->id) }}">Booking #{{ $booking->booking_number }}</a>
            <i class="bx bx-chevron-right"></i>
            <span>Confirm & Pay</span>
        </nav>

        {{-- Hold warning --}}
        <div class="hcnf-pnr-banner">
            <i class="bx bx-time"></i>
            <div class="hcnf-pnr-banner__text">
                This flight is currently on hold with PNR
                <span class="hcnf-pnr-banner__pnr">{{ $pnr }}</span>.
                Completing payment will issue your ticket immediately.
                @if ($ttl)
                    Hold expires: <strong>{{ \Carbon\Carbon::parse($ttl)->format('d M Y, h:i A') }}</strong>.
                @endif
            </div>
        </div>

        <form id="holdConfirmForm"
              action="{{ route('user.flights.hold.confirm.pay', $booking->id) }}"
              method="POST">
            @csrf

            <div class="row g-3">

                {{-- LEFT COLUMN --}}
                <div class="col-lg-8">

                    {{-- Flight summary --}}
                    <div class="hcnf-card">
                        <div class="hcnf-card__head">
                            <i class="bx bx-plane-alt"></i> Flight Summary
                        </div>
                        <div class="hcnf-card__body">
                            <div class="hcnf-route">
                                <div class="hcnf-route__iata">{{ $from }}</div>
                                <div class="hcnf-route__line">
                                    <div class="hcnf-route__dash"></div>
                                    <i class="bx bx-plane hcnf-route__plane"></i>
                                    <div class="hcnf-route__dash"></div>
                                </div>
                                <div class="hcnf-route__iata">{{ $to }}</div>
                                @if ($isRound)
                                    <div class="hcnf-route__line">
                                        <div class="hcnf-route__dash"></div>
                                        <i class="bx bx-plane hcnf-route__plane" style="transform:rotate(180deg)"></i>
                                        <div class="hcnf-route__dash"></div>
                                    </div>
                                    <div class="hcnf-route__iata">{{ $from }}</div>
                                @endif
                            </div>
                            <div class="hcnf-meta">
                                <span><i class="bx bxs-calendar"></i>
                                    {{ \Carbon\Carbon::parse($booking->departure_date)->format('d M Y') }}
                                    @if ($isRound)
                                        — {{ \Carbon\Carbon::parse($booking->return_date)->format('d M Y') }}
                                    @endif
                                </span>
                                <span><i class="bx bxs-group"></i> {{ $paxStr }}</span>
                                <span><i class="bx bx-barcode"></i> PNR: <strong>{{ $pnr }}</strong></span>
                            </div>

                            {{-- Per-leg airline info --}}
                            @foreach ($legs as $leg)
                                @php
                                    $segs      = $leg['segments'] ?? [];
                                    $first     = $segs[0] ?? [];
                                    $last      = end($segs);
                                    $airline   = $first['airline_name'] ?? ($first['carrier'] ?? '—');
                                    $flightNum = ($first['carrier'] ?? '') . ($first['flight_number'] ?? '');
                                    $depTime   = \Carbon\Carbon::parse($first['departure_time'] ?? null);
                                    $arrTime   = \Carbon\Carbon::parse($last['arrival_time'] ?? null);
                                    $stops     = max(0, count($segs) - 1);
                                @endphp
                                <div class="hcnf-pax-row mt-2">
                                    <i class="bx bx-plane-alt"></i>
                                    <span class="hcnf-pax-row__name">
                                        {{ $airline }} &middot; {{ $flightNum }}
                                        &mdash; {{ strtoupper($first['origin'] ?? '') }} → {{ strtoupper($last['destination'] ?? '') }}
                                    </span>
                                    <span class="hcnf-pax-row__type">
                                        {{ $depTime->format('H:i') }} → {{ $arrTime->format('H:i') }}
                                        &middot; {{ $stops === 0 ? 'Direct' : $stops . ' stop' . ($stops > 1 ? 's' : '') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Passengers --}}
                    <div class="hcnf-card">
                        <div class="hcnf-card__head">
                            <i class="bx bxs-user"></i> Passengers
                        </div>
                        <div class="hcnf-card__body">
                            <div class="hcnf-pax-list">
                                @foreach ($passengers as $pax)
                                    @php
                                        $typeLabel = match($pax['type'] ?? 'ADT') {
                                            'C06' => 'Child', 'INF' => 'Infant', default => 'Adult'
                                        };
                                    @endphp
                                    <div class="hcnf-pax-row">
                                        <i class="bx bxs-user-circle"></i>
                                        <span class="hcnf-pax-row__name">
                                            {{ $pax['title'] ?? '' }} {{ $pax['first_name'] ?? '' }} {{ $pax['last_name'] ?? '' }}
                                        </span>
                                        <span class="hcnf-pax-row__type">{{ $typeLabel }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Fare breakdown --}}
                    <div class="hcnf-card">
                        <div class="hcnf-card__head">
                            <i class="bx bx-receipt"></i> Fare Summary
                        </div>
                        <div class="hcnf-card__body">
                            <div class="hcnf-fare">
                                <div class="hcnf-fare__line">
                                    <span>Base Fare ({{ $paxStr }})</span>
                                    <span><span class="dirham">D</span> {{ number_format($baseAmount, 2) }}</span>
                                </div>
                                @if ($taxAmount > 0)
                                    <div class="hcnf-fare__line">
                                        <span>Taxes &amp; Fees</span>
                                        <span><span class="dirham">D</span> {{ number_format((float) $taxAmount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="hcnf-fare__total">
                                    <span>Total Amount</span>
                                    <span><span class="dirham">D</span> {{ number_format($totalAmount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment --}}
                    <div class="hcnf-card">
                        <div class="hcnf-card__head">
                            <i class="bx bx-credit-card"></i> Payment Method
                        </div>
                        <div class="hcnf-card__body">

                            @if ($walletBalance > 0)
                                <div class="hcnf-wallet" id="wallet-section">
                                    <div class="hcnf-wallet__row">
                                        <i class="bx bxs-wallet hcnf-wallet__icon"></i>
                                        <div class="hcnf-wallet__info">
                                            <div class="hcnf-wallet__label">Use Wallet Balance</div>
                                            <div class="hcnf-wallet__balance">
                                                Available: <strong><span class="dirham">D</span> {{ number_format($walletBalance, 2) }}</strong>
                                            </div>
                                        </div>
                                        <label class="hcnf-wallet__switch">
                                            <input type="checkbox" id="use-wallet" name="use_wallet" value="1" style="display:none;">
                                            <div class="hc-wallet-toggle__switch" style="cursor:pointer;">
                                                <span class="hc-wallet-toggle__slider"></span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="hcnf-wallet-breakdown" id="wallet-breakdown">
                                        <p><span>Wallet deduction</span> <span><span class="dirham">D</span> <span id="wallet-deduct">0.00</span></span></p>
                                        <p><span>Remaining to pay</span> <span><span class="dirham">D</span> <span id="remaining-show">0.00</span></span></p>
                                    </div>
                                    <input type="hidden" name="wallet_amount" id="wallet-amount-input" value="0">
                                </div>
                            @endif

                            <div id="gateway-section">
                                <p class="mb-2" style="font-size:.82rem; color:var(--c-slate);" id="gateway-label">Select a payment method</p>
                                <div class="hcnf-payment-options">
                                    <label class="hcnf-payment-option">
                                        <input type="radio" name="payment_method" value="payby" checked>
                                        <div class="hcnf-payment-option__body">
                                            <div class="hcnf-payment-option__icon"><i class="bx bx-card"></i></div>
                                            <div>
                                                <div class="hcnf-payment-option__name">PayBy</div>
                                                <div class="hcnf-payment-option__desc">Credit / Debit card secure checkout</div>
                                            </div>
                                            <div class="hcnf-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                        </div>
                                    </label>
                                    <label class="hcnf-payment-option">
                                        <input type="radio" name="payment_method" value="tabby">
                                        <div class="hcnf-payment-option__body">
                                            <div class="hcnf-payment-option__icon"><i class="bx bx-calendar-check"></i></div>
                                            <div>
                                                <div class="hcnf-payment-option__name">Tabby</div>
                                                <div class="hcnf-payment-option__desc">4 interest-free installments</div>
                                            </div>
                                            <div class="hcnf-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                        </div>
                                    </label>
                                    <label class="hcnf-payment-option">
                                        <input type="radio" name="payment_method" value="tamara">
                                        <div class="hcnf-payment-option__body">
                                            <div class="hcnf-payment-option__icon"><i class="bx bx-wallet-alt"></i></div>
                                            <div>
                                                <div class="hcnf-payment-option__name">Tamara</div>
                                                <div class="hcnf-payment-option__desc">Split payment with Tamara</div>
                                            </div>
                                            <div class="hcnf-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="hcnf-pay-btn mt-3" id="pay-btn">
                                <i class="bx bx-lock-alt"></i>
                                <span id="pay-btn-text">Pay <span class="dirham" style="font-size:1rem;">D</span> {{ number_format($totalAmount, 2) }} & Issue Ticket</span>
                            </button>
                            <div class="hcnf-secure">
                                <i class="bx bx-check-shield"></i> 256-bit SSL secure transaction
                            </div>
                        </div>
                    </div>

                </div>

                {{-- RIGHT COLUMN – sticky summary --}}
                <div class="col-lg-4">
                    <div class="hcnf-summary">
                        <div class="hcnf-summary__head">
                            <i class="bx bx-receipt"></i> Order Summary
                        </div>
                        <div class="hcnf-summary__body">
                            <div class="hcnf-summary__line">
                                <span>Route</span>
                                <span>{{ $from }} → {{ $to }}</span>
                            </div>
                            <div class="hcnf-summary__line">
                                <span>PNR</span>
                                <span><strong>{{ $pnr }}</strong></span>
                            </div>
                            <div class="hcnf-summary__line">
                                <span>Passengers</span>
                                <span>{{ $paxStr }}</span>
                            </div>
                            <hr class="hcnf-summary__divider">
                            <div class="hcnf-summary__line">
                                <span>Base Fare</span>
                                <span><span class="dirham">D</span> {{ number_format($baseAmount, 2) }}</span>
                            </div>
                            @if ($taxAmount > 0)
                                <div class="hcnf-summary__line">
                                    <span>Taxes</span>
                                    <span><span class="dirham">D</span> {{ number_format((float)$taxAmount, 2) }}</span>
                                </div>
                            @endif

                            <div class="hcnf-summary__wallet-applied" id="summary-wallet-applied">
                                <p><span><i class="bx bxs-wallet"></i> Wallet Applied</span> <span>- <span class="dirham">D</span> <span id="sum-wallet">0.00</span></span></p>
                            </div>

                            <div class="hcnf-summary__total">
                                <span>Amount Due</span>
                                <span><span class="dirham">D</span> <span id="sum-net-total">{{ number_format($totalAmount, 2) }}</span></span>
                            </div>

                            <div class="mt-3 p-2" style="background:#f8fafc; border-radius:8px; font-size:.76rem; color:var(--c-slate); line-height:1.5;">
                                <i class="bx bx-info-circle" style="color:var(--c-primary);"></i>
                                After successful payment, your ticket will be issued immediately and the booking status will change to <strong>Confirmed</strong>.
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /row --}}
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const total         = @json($totalAmount);
    const walletBalance = @json($walletBalance);
    const fmt = v => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const walletCheckbox    = document.getElementById('use-wallet');
    const walletAmountInput = document.getElementById('wallet-amount-input');
    const walletBreakdown   = document.getElementById('wallet-breakdown');
    const walletDeductEl    = document.getElementById('wallet-deduct');
    const remainingShowEl   = document.getElementById('remaining-show');
    const gatewaySection    = document.getElementById('gateway-section');
    const gatewayLabel      = document.getElementById('gateway-label');
    const sumWalletEl       = document.getElementById('sum-wallet');
    const sumNetTotalEl     = document.getElementById('sum-net-total');
    const sumWalletApplied  = document.getElementById('summary-wallet-applied');
    const payBtnText        = document.getElementById('pay-btn-text');

    function recalc() {
        if (!walletCheckbox) return;
        const useWallet  = walletCheckbox.checked;
        const deduction  = useWallet ? Math.min(walletBalance, total) : 0;
        const remaining  = total - deduction;

        walletAmountInput && (walletAmountInput.value = deduction.toFixed(2));
        if (walletDeductEl)   walletDeductEl.textContent   = fmt(deduction);
        if (remainingShowEl)  remainingShowEl.textContent  = fmt(remaining);
        if (walletBreakdown)  walletBreakdown.style.display = useWallet ? 'block' : 'none';
        if (sumWalletEl)      sumWalletEl.textContent      = fmt(deduction);
        if (sumNetTotalEl)    sumNetTotalEl.textContent    = fmt(remaining);
        if (sumWalletApplied) sumWalletApplied.style.display = (useWallet && deduction > 0) ? 'block' : 'none';

        // Hide gateway if wallet covers full amount
        const fullyPaid = remaining <= 0;
        if (gatewaySection) gatewaySection.style.display = fullyPaid ? 'none' : 'block';
        if (gatewayLabel)   gatewayLabel.textContent = fullyPaid
            ? ''
            : (useWallet ? 'Select a method for the remaining amount' : 'Select payment method');

        if (payBtnText) {
            payBtnText.innerHTML = fullyPaid
                ? 'Confirm with Wallet & Issue Ticket'
                : `Pay <span class="dirham" style="font-size:1rem;">D</span> ${fmt(remaining)} & Issue Ticket`;
        }
    }

    if (walletCheckbox) {
        walletCheckbox.addEventListener('change', recalc);

        // Make the toggle div also work as a click target
        const switchDiv = walletCheckbox.closest('label')?.querySelector('.hc-wallet-toggle__switch');
        if (switchDiv) {
            switchDiv.addEventListener('click', function () {
                walletCheckbox.checked = !walletCheckbox.checked;
                recalc();
            });
        }
    }

    recalc();
});
</script>
@endpush
