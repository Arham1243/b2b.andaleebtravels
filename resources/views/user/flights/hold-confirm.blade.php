@extends('user.layouts.main')

@section('content')
@php
    $itinerary  = $booking->itinerary_data ?? [];
    $legs       = $itinerary['legs'] ?? [];
    $passengers = $booking->passengers_data['passengers'] ?? [];
    $lead       = $booking->passengers_data['lead'] ?? [];

    $adults   = (int) $booking->adults;
    $children = (int) $booking->children;
    $infants  = (int) $booking->infants;
    $paxCount = max(1, $adults + $children + $infants);

    $totalAmount = (float) $booking->total_amount;
    $baseAmount  = (float) ($itinerary['basePrice'] ?? $totalAmount);
    $taxAmount   = (float) ($itinerary['taxes'] ?? 0);
    if ($baseAmount + $taxAmount < 0.01) { $baseAmount = $totalAmount; }

    $from   = strtoupper($booking->from_airport ?? '');
    $to     = strtoupper($booking->to_airport   ?? '');
    $isRound = !empty($booking->return_date);

    $paxStr = $adults . ' Adult' . ($adults > 1 ? 's' : '');
    if ($children) $paxStr .= ', ' . $children . ' Child' . ($children > 1 ? 'ren' : '');
    if ($infants)  $paxStr .= ', ' . $infants  . ' Infant' . ($infants > 1 ? 's' : '');

    function hcf_fmt(?int $mins): string {
        if (!$mins || $mins < 1) return ' - ';
        $h = intdiv($mins, 60); $r = $mins % 60;
        if ($h && !$r) return "{$h}h";
        if (!$h) return "{$r}m";
        return "{$h}h {$r}m";
    }
    function hcf_logo(?string $c): string {
        return 'https://pics.avs.io/80/80/' . strtoupper(trim($c ?: 'XX')) . '.png';
    }

    $ttl = data_get($booking->booking_response, 'CreatePassengerNameRecordRS.ItineraryRef.ticketingDeadline') ?? null;
@endphp

<div class="hp">
    <div class="container hp-shell">

        {{-- breadcrumb --}}
        <nav class="hp-crumb" aria-label="breadcrumb">
            <a href="{{ route('user.bookings.flights') }}"><i class="bx bx-bookmarks"></i> My Bookings</a>
            <i class="bx bx-chevron-right hp-crumb__sep"></i>
            <a href="{{ route('user.bookings.flights.detail', $booking->id) }}">#{{ $booking->booking_number }}</a>
            <i class="bx bx-chevron-right hp-crumb__sep"></i>
            <span>Confirm &amp; Pay</span>
        </nav>

        {{-- PNR hold notice --}}
        <div class="hcf-hold-notice">
            <i class="bx bx-time-five"></i>
            <div>
                <strong>PNR {{ $booking->sabre_record_locator }} is on hold.</strong>
                Complete payment below to issue the ticket.
                @if($ttl) &nbsp;·&nbsp; Hold expires: <strong>{{ \Carbon\Carbon::parse($ttl)->format('d M Y, h:i A') }}</strong> @endif
            </div>
        </div>

        <form id="confirmPayForm"
              action="{{ route('user.flights.hold.confirm.pay', $booking->id) }}"
              method="POST" novalidate>
            @csrf

            <div class="row g-4">

                {{-- ═══════════════════════════════════════════
                     LEFT col-8
                     ═══════════════════════════════════════════ --}}
                <div class="col-lg-8">

                    {{-- ── FLIGHT DETAILS (read-only, same as hold.blade) ── --}}
                    <div class="hp-card mb-3">
                        <div class="hp-card__head">
                            <i class="bx bxs-plane hp-card__head-icon"></i>
                            <div>
                                <div class="hp-card__eyebrow">Flight Summary</div>
                                <div class="hp-card__title">
                                    {{ $from }}
                                    @if($isRound) ⇄ @else → @endif
                                    {{ $to }}
                                </div>
                            </div>
                            <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                                <span class="hp-pnr-tag"><i class="bx bx-barcode"></i> {{ $booking->sabre_record_locator }}</span>
                                @if(!empty($itinerary['non_refundable']))
                                    <span class="hp-badge hp-badge--nr">Non-Refundable</span>
                                @else
                                    <span class="hp-badge hp-badge--ref">Refundable</span>
                                @endif
                            </div>
                        </div>

                        <div class="hp-flight">
                            @foreach ($legs as $li => $leg)
                                @php
                                    $segs  = $leg['segments'] ?? [];
                                    $s0    = $segs[0] ?? [];
                                    $sLast = !empty($segs) ? $segs[array_key_last($segs)] : [];
                                    $conn  = max(0, count($segs) - 1);
                                    $tech  = collect($segs)->sum(fn($s) => (int)($s['stop_count'] ?? 0));
                                    $stops = $conn + $tech;
                                    $dur   = hcf_fmt(isset($leg['elapsedTime']) ? (int)$leg['elapsedTime'] : null);
                                    $nextDay = (bool)($sLast['next_day_hint'] ?? false);
                                    $midApts = [];
                                    for ($mi = 0; $mi < count($segs) - 1; $mi++) {
                                        $midApts[] = $segs[$mi]['to'] ?? '';
                                    }
                                @endphp
                                <div class="hp-leg {{ $li > 0 ? 'hp-leg--ret' : '' }}">
                                    <div class="hp-leg__tag">
                                        <i class="bx {{ $li === 0 ? 'bxs-plane-take-off' : 'bxs-plane-land' }}"></i>
                                        <span>{{ $li === 0 ? ($isRound ? 'Outbound' : 'Flight') : 'Return' }}</span>
                                    </div>
                                    <div class="hp-leg__row">
                                        <div class="hp-leg__airline">
                                            <div class="hp-leg__logo-wrap">
                                                <img class="hp-leg__logo"
                                                     src="{{ hcf_logo($s0['carrier'] ?? '') }}"
                                                     loading="lazy" alt="{{ $s0['carrier_display'] ?? '' }}"
                                                     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($s0['carrier'] ?? 'FL') }}&background=cd1b4f&color=fff&size=80'">
                                            </div>
                                            <div>
                                                <div class="hp-leg__aname">{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}</div>
                                                <div class="hp-leg__aflight">{{ strtoupper((string)($s0['carrier'] ?? '')) }}{{ $s0['flight_number'] ?? '' }}</div>
                                            </div>
                                        </div>
                                        <div class="hp-leg__pt">
                                            <div class="hp-leg__time">{{ $s0['departure_clock'] ?? ' - ' }}</div>
                                            <div class="hp-leg__dt">{{ $s0['departure_weekday'] ?? '' }}, {{ $s0['departure_label'] ?? '' }}</div>
                                            <div class="hp-leg__city">{{ $s0['from'] ?? '' }}@if(!empty($s0['departure_terminal'])), T{{ $s0['departure_terminal'] }}@endif</div>
                                        </div>
                                        <div class="hp-leg__bridge">
                                            <div class="hp-leg__bridge-dur">{{ $dur }}</div>
                                            <div class="hp-leg__bridge-track">
                                                <span class="hp-leg__bridge-dot"></span>
                                                @foreach($midApts as $ma)
                                                    <span class="hp-leg__bridge-via">{{ $ma }}</span>
                                                @endforeach
                                                <span class="hp-leg__bridge-line"></span>
                                                <span class="hp-leg__bridge-dot"></span>
                                            </div>
                                            @if($stops === 0)
                                                <div class="hp-leg__bridge-stop hp-leg__bridge-stop--ok">Non-stop</div>
                                            @else
                                                <div class="hp-leg__bridge-stop hp-leg__bridge-stop--via">{{ $stops === 1 ? '1 Stop' : $stops.' Stops' }}</div>
                                            @endif
                                        </div>
                                        <div class="hp-leg__pt hp-leg__pt--arr">
                                            <div class="hp-leg__time">
                                                {{ $sLast['arrival_clock'] ?? ' - ' }}
                                                @if($nextDay)<span class="hp-nextday">+1</span>@endif
                                            </div>
                                            <div class="hp-leg__dt">{{ $sLast['arrival_weekday'] ?? '' }}, {{ $sLast['arrival_label'] ?? '' }}</div>
                                            <div class="hp-leg__city">{{ $sLast['to'] ?? '' }}@if(!empty($sLast['arrival_terminal'])), T{{ $sLast['arrival_terminal'] }}@endif</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── PASSENGER INFO (pre-filled, read-only) ── --}}
                    @foreach ($passengers as $pi => $pax)
                        @php
                            $typeLabel = match($pax['type'] ?? 'ADT') {
                                'ADT'  => ['label' => 'Adult',  'age' => '12+ years',      'icon' => 'bx-user'],
                                'C06'  => ['label' => 'Child',  'age' => '2–11 years',     'icon' => 'bx-user'],
                                'C11'  => ['label' => 'Child',  'age' => '2–11 years',     'icon' => 'bx-user'],
                                'INF'  => ['label' => 'Infant', 'age' => 'Under 2 years',  'icon' => 'bx-baby-carriage'],
                                default => ['label' => ucfirst($pax['type'] ?? 'Passenger'), 'age' => '', 'icon' => 'bx-user'],
                            };
                            $num = $pi + 1;
                        @endphp
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx {{ $typeLabel['icon'] }} hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__eyebrow">Passenger {{ $num }}</div>
                                    <div class="hp-card__title">{{ $typeLabel['label'] }} {{ $num }} <span class="hp-card__age">{{ $typeLabel['age'] }}</span></div>
                                </div>
                                <span class="hcf-locked ms-auto"><i class="bx bx-check-circle"></i> Pre-filled</span>
                            </div>
                            <div class="hcf-pax-readonly">
                                <div class="hcf-pax-readonly__grid">
                                    <div class="hcf-pax-field">
                                        <span class="hcf-pax-field__label">Full Name</span>
                                        <span class="hcf-pax-field__val">{{ strtoupper(trim(($pax['title'] ?? '') . ' ' . ($pax['first_name'] ?? '') . ' ' . ($pax['last_name'] ?? ''))) }}</span>
                                    </div>
                                    @if(!empty($pax['dob']))
                                    <div class="hcf-pax-field">
                                        <span class="hcf-pax-field__label">Date of Birth</span>
                                        <span class="hcf-pax-field__val">{{ $pax['dob'] }}</span>
                                    </div>
                                    @endif
                                    @if(!empty($pax['nationality']))
                                    <div class="hcf-pax-field">
                                        <span class="hcf-pax-field__label">Nationality</span>
                                        <span class="hcf-pax-field__val">{{ strtoupper($pax['nationality']) }}</span>
                                    </div>
                                    @endif
                                    @if(!empty($pax['passport_no']))
                                    <div class="hcf-pax-field">
                                        <span class="hcf-pax-field__label">Passport No.</span>
                                        <span class="hcf-pax-field__val" style="font-family:var(--mono);">{{ $pax['passport_no'] }}</span>
                                    </div>
                                    @endif
                                    @if(!empty($pax['passport_exp']))
                                    <div class="hcf-pax-field">
                                        <span class="hcf-pax-field__label">Passport Expiry</span>
                                        <span class="hcf-pax-field__val">{{ $pax['passport_exp'] }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- ── CONTACT DETAILS (read-only) ── --}}
                    <div class="hp-card mb-3">
                        <div class="hp-card__head">
                            <i class="bx bx-phone hp-card__head-icon"></i>
                            <div>
                                <div class="hp-card__title" style="margin-top:0;">Contact Details</div>
                            </div>
                            <span class="hcf-locked ms-auto"><i class="bx bx-check-circle"></i> Pre-filled</span>
                        </div>
                        <div class="hcf-pax-readonly">
                            <div class="hcf-pax-readonly__grid">
                                @if(!empty($lead['phone']))
                                <div class="hcf-pax-field">
                                    <span class="hcf-pax-field__label"><i class="bx bx-phone"></i> Phone</span>
                                    <span class="hcf-pax-field__val">{{ $lead['phone'] }}</span>
                                </div>
                                @endif
                                @if(!empty($lead['email']))
                                <div class="hcf-pax-field">
                                    <span class="hcf-pax-field__label"><i class="bx bx-envelope"></i> Email</span>
                                    <span class="hcf-pax-field__val">{{ $lead['email'] }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ── PAYMENT METHOD ── --}}
                    <div class="hp-card mb-3">
                        <div class="hp-card__head">
                            <i class="bx bx-credit-card hp-card__head-icon"></i>
                            <div>
                                <div class="hp-card__title" style="margin-top:0;">Payment Method</div>
                            </div>
                        </div>

                        {{-- Wallet toggle --}}
                        @if ($walletBalance > 0)
                        <div class="hcf-wallet-toggle" id="wallet-toggle-section">
                            <label class="hcf-wallet-toggle__label">
                                <input type="checkbox" id="use-wallet" name="use_wallet" value="1">
                                <div class="hcf-wallet-toggle__body">
                                    <div class="hcf-wallet-toggle__left">
                                        <div class="hcf-pay-icon"><i class="bx bxs-wallet"></i></div>
                                        <div class="hcf-pay-info">
                                            <div class="hcf-pay-name">Use Wallet Balance</div>
                                            <div class="hcf-pay-desc">Available: <strong><span class="dirham">AED</span> {{ number_format($walletBalance, 2) }}</strong></div>
                                        </div>
                                    </div>
                                    <div class="hcf-wallet-toggle__switch">
                                        <span class="hcf-wallet-toggle__slider"></span>
                                    </div>
                                </div>
                            </label>
                            <div class="hcf-wallet-applied" id="wallet-applied-info" style="display:none;">
                                <div class="hcf-wallet-applied__row">
                                    <span>Wallet deduction</span>
                                    <span><span class="dirham">AED</span> <span id="wallet-deduct-amount">0.00</span></span>
                                </div>
                                <div class="hcf-wallet-applied__row hcf-wallet-applied__row--rem">
                                    <span>Remaining to pay</span>
                                    <span><span class="dirham">AED</span> <span id="remaining-amount">0.00</span></span>
                                </div>
                            </div>
                            <input type="hidden" name="wallet_amount" id="wallet-amount-input" value="0">
                        </div>
                        @endif

                        {{-- Gateway options --}}
                        <div class="hcf-payment-remaining" id="remaining-payment-section">
                            <div class="hcf-payment-remaining__title" id="remaining-payment-title">Select Payment Method</div>
                            <div class="hcf-payment-options">
                                <label class="hcf-payment-option">
                                    <input type="radio" name="payment_method" value="payby" checked>
                                    <div class="hcf-payment-option__body">
                                        <div class="hcf-pay-icon"><i class="bx bxs-credit-card"></i></div>
                                        <div class="hcf-pay-info">
                                            <div class="hcf-pay-name">Credit / Debit Card</div>
                                            <div class="hcf-pay-desc">Redirected to secure PayBy gateway</div>
                                        </div>
                                        <div class="hcf-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                    </div>
                                </label>
                                <label class="hcf-payment-option">
                                    <input type="radio" name="payment_method" value="tabby">
                                    <div class="hcf-payment-option__body">
                                        <div class="hcf-pay-icon"><i class="bx bx-calendar-check"></i></div>
                                        <div class="hcf-pay-info">
                                            <div class="hcf-pay-name">Tabby – Buy Now Pay Later</div>
                                            <div class="hcf-pay-desc">4 interest-free installments</div>
                                        </div>
                                        <div class="hcf-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                    </div>
                                </label>
                                <label class="hcf-payment-option">
                                    <input type="radio" name="payment_method" value="tamara">
                                    <div class="hcf-payment-option__body">
                                        <div class="hcf-pay-icon"><i class="bx bx-wallet-alt"></i></div>
                                        <div class="hcf-pay-info">
                                            <div class="hcf-pay-name">Tamara – Buy Now Pay Later</div>
                                            <div class="hcf-pay-desc">Split into installments with Tamara</div>
                                        </div>
                                        <div class="hcf-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                    </div>
                                </label>
                            </div>
                        </div>

                    </div>

                </div>{{-- /.col-lg-8 --}}

                {{-- ═══════════════════════════════════════════
                     RIGHT col-4 – sticky fare summary
                     ═══════════════════════════════════════════ --}}
                <div class="col-lg-4">
                    <div class="hp-summary" id="hp-summary-sticky">

                        <div class="hp-summary__head">
                            <i class="bx bx-receipt"></i>
                            Fare Summary
                        </div>

                        <div class="hp-summary__body">
                            @php $adultBase = $adults > 0 ? round($baseAmount / $paxCount, 2) : 0; @endphp
                            @if($adults > 0)
                                <div class="hp-sum-row">
                                    <span>Adult × {{ $adults }}</span>
                                    <span><span class="dirham">AED</span> {{ number_format($adultBase * $adults, 2) }}</span>
                                </div>
                            @endif
                            @if($children > 0)
                                @php $childBase = round($baseAmount / $paxCount, 2); @endphp
                                <div class="hp-sum-row">
                                    <span>Child × {{ $children }}</span>
                                    <span><span class="dirham">AED</span> {{ number_format($childBase * $children, 2) }}</span>
                                </div>
                            @endif
                            @if($infants > 0)
                                @php $infantBase = round($baseAmount / $paxCount * 0.1, 2); @endphp
                                <div class="hp-sum-row">
                                    <span>Infant × {{ $infants }}</span>
                                    <span><span class="dirham">AED</span> {{ number_format($infantBase * $infants, 2) }}</span>
                                </div>
                            @endif
                            @if($taxAmount > 0)
                                <div class="hp-sum-row">
                                    <span>Taxes &amp; Fees</span>
                                    <span><span class="dirham">AED</span> {{ number_format($taxAmount, 2) }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Wallet applied section --}}
                        <div class="hcf-sum-wallet" id="summary-wallet-section" style="display:none;">
                            <div class="hp-sum-row">
                                <span>Subtotal</span>
                                <span><span class="dirham">AED</span> {{ number_format($totalAmount, 2) }}</span>
                            </div>
                            <div class="hp-sum-row hcf-sum-wallet__deduct">
                                <span><i class="bx bxs-wallet" style="vertical-align:middle;margin-right:3px;"></i> Wallet Applied</span>
                                <span class="hcf-sum-wallet__amt">– <span class="dirham">AED</span> <span id="summary-wallet-amount">0.00</span></span>
                            </div>
                        </div>

                        <div class="hp-summary__total">
                            <span id="summary-total-label">Total Due</span>
                            <span class="hp-summary__total-amount">
                                <span class="dirham">AED</span><span id="summary-net-total">{{ number_format($totalAmount, 2) }}</span>
                            </span>
                        </div>

                        <div class="hp-summary__meta">
                            <div class="hp-summary__meta-row">
                                <i class="bx bx-user"></i>
                                <span>{{ $paxStr }}</span>
                            </div>
                            <div class="hp-summary__meta-row">
                                <i class="bx bx-map"></i>
                                <span>{{ $from }} → {{ $to }}</span>
                            </div>
                            <div class="hp-summary__meta-row">
                                <i class="bx bxs-calendar"></i>
                                <span>
                                    {{ $booking->departure_date?->format('d M Y') ?? '' }}
                                    @if($isRound)  -  {{ $booking->return_date?->format('d M Y') }} @endif
                                </span>
                            </div>
                            <div class="hp-summary__meta-row">
                                <i class="bx bx-barcode"></i>
                                <span>PNR: <strong>{{ $booking->sabre_record_locator }}</strong></span>
                            </div>
                        </div>

                        <div class="hp-summary__footer">
                            <button type="submit" form="confirmPayForm" class="hp-btn-pay" id="pay-btn">
                                <i class="bx bx-lock-alt"></i>
                                <span id="pay-btn-text">Pay <span class="dirham">AED</span> {{ number_format($totalAmount, 2) }} &amp; Issue Ticket</span>
                            </button>
                            <div class="hp-summary__secure">
                                <i class="bx bxs-lock-alt"></i> 256-bit SSL secure transaction
                            </div>
                        </div>

                    </div>
                </div>

            </div>{{-- /.row --}}
        </form>

    </div>
</div>
@endsection

@push('css')
<style>
/* ═══════════════════════════════════════════════════════════
   HOLD CONFIRM PAGE  –  inherits hp-* from hold.blade.php
   ═══════════════════════════════════════════════════════════ */
.hp {
    --c-brand:       #cd1b4f;
    --c-brand2:      #a8173f;
    --c-brand-soft:  #fdeef3;
    --c-ink:         #1a2540;
    --c-slate:       #4a5568;
    --c-muted:       #8492a6;
    --c-line:        #dde3ef;
    --c-bg:          #f3f5fb;
    --c-white:       #ffffff;
    --c-green:       #0f9d58;
    --c-green-soft:  #e8f9f1;
    --c-amber:       #d97706;
    --c-amber-soft:  #fef3c7;
    --c-shadow:      0 2px 8px rgba(26,37,64,.07);
    --c-shadow-md:   0 6px 22px rgba(26,37,64,.11);
    --mono: "JetBrains Mono", ui-monospace, Menlo, monospace;
    --sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    font-family: var(--sans);
    color: var(--c-ink);
    background: var(--c-bg);
    padding-bottom: 3rem;
}
.hp * { box-sizing: border-box; }
.hp a { text-decoration: none; }
.hp-shell { padding-top: 1.25rem; }

/* ── Breadcrumb ── */
.hp-crumb { display:flex; align-items:center; gap:.35rem; font-size:.8rem; margin-bottom:1.25rem; flex-wrap:wrap; }
.hp-crumb a { color:var(--c-brand); font-weight:600; display:inline-flex; align-items:center; gap:.22rem; padding:.2rem .45rem; border-radius:6px; transition:background .12s; }
.hp-crumb a:hover { background:var(--c-brand-soft); }
.hp-crumb span { color:var(--c-ink); font-weight:700; padding:.2rem .45rem; }
.hp-crumb__sep { color:var(--c-muted); font-size:.85rem; }

/* ── Hold notice banner ── */
.hcf-hold-notice {
    display:flex; align-items:center; gap:.75rem;
    background:#fffbeb; border:1px solid #fcd34d; border-radius:10px;
    padding:.75rem 1rem; margin-bottom:1.25rem;
    font-size:.82rem; color:#78350f;
}
.hcf-hold-notice i { font-size:1.2rem; color:var(--c-amber); flex-shrink:0; }

/* ── Card shell ── */
.hp-card { background:var(--c-white); border:1px solid var(--c-line); border-radius:14px; box-shadow:var(--c-shadow); overflow:hidden; }
.hp-card__head { display:flex; align-items:center; gap:.75rem; padding:.8rem 1.1rem; border-bottom:1px solid var(--c-line); background:linear-gradient(135deg,rgba(205,27,79,.035) 0%,transparent 70%); }
.hp-card__head-icon { font-size:1.35rem; color:var(--c-brand); flex-shrink:0; }
.hp-card__eyebrow { font-size:.58rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--c-muted); line-height:1; }
.hp-card__title { font-size:.95rem; font-weight:700; color:var(--c-ink); margin-top:.04rem; }
.hp-card__age { font-size:.7rem; font-weight:500; color:var(--c-muted); background:var(--c-bg); padding:.06rem .38rem; border-radius:4px; margin-left:.3rem; vertical-align:middle; }

.hp-badge { font-size:.6rem; font-weight:700; padding:.18rem .5rem; border-radius:4px; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
.hp-badge--ref { background:var(--c-green-soft); color:var(--c-green); }
.hp-badge--nr  { background:#fff0f3; color:#c0143c; }

/* PNR tag in flight card head */
.hp-pnr-tag {
    display:inline-flex; align-items:center; gap:.28rem;
    font-family:var(--mono); font-size:.7rem; font-weight:700; color:var(--c-brand);
    background:var(--c-brand-soft); border:1px solid rgba(205,27,79,.2);
    padding:.18rem .55rem; border-radius:6px;
}

/* ── Flight leg visual (exact copy from hold.blade) ── */
.hp-flight { padding:0; }
.hp-leg { padding:.9rem 1.1rem; }
.hp-leg--ret { border-top:1px dashed var(--c-line); }
.hp-leg__tag { display:inline-flex; align-items:center; gap:.28rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--c-brand); margin-bottom:.6rem; background:var(--c-brand-soft); padding:.2rem .6rem; border-radius:20px; }
.hp-leg__row { display:grid; grid-template-columns:160px 1fr auto 1fr; align-items:center; gap:.5rem 1.1rem; }
.hp-leg__airline { display:flex; gap:.6rem; align-items:center; min-width:0; }
.hp-leg__logo-wrap { width:48px; height:48px; flex-shrink:0; border-radius:11px; border:1.5px solid var(--c-line); background:#fff; box-shadow:0 1px 6px rgba(26,37,64,.07); display:flex; align-items:center; justify-content:center; padding:3px; overflow:hidden; }
.hp-leg__logo { width:100%; height:100%; object-fit:contain; display:block; }
.hp-leg__aname { font-size:.83rem; font-weight:700; color:var(--c-ink); line-height:1.25; }
.hp-leg__aflight { font-family:var(--mono); font-size:.67rem; color:var(--c-muted); margin-top:.06rem; }
.hp-leg__pt { min-width:0; }
.hp-leg__pt--arr { text-align:right; }
.hp-leg__time { font-family:var(--mono); font-size:1.28rem; font-weight:700; color:var(--c-ink); line-height:1; display:inline-flex; align-items:center; gap:.25rem; }
.hp-leg__dt { font-size:.68rem; color:var(--c-muted); margin-top:.16rem; white-space:nowrap; }
.hp-leg__city { font-size:.73rem; color:var(--c-slate); font-weight:500; margin-top:.05rem; }
.hp-nextday { font-size:.55rem; font-weight:700; background:var(--c-amber-soft); color:var(--c-amber); padding:.03rem .28rem; border-radius:4px; font-family:var(--sans); }
.hp-leg__bridge { display:flex; flex-direction:column; align-items:center; gap:.22rem; min-width:120px; }
.hp-leg__bridge-dur { font-size:.7rem; font-weight:600; color:var(--c-slate); font-family:var(--mono); }
.hp-leg__bridge-track { width:100%; display:flex; align-items:center; gap:.2rem; }
.hp-leg__bridge-dot { width:6px; height:6px; border-radius:50%; background:var(--c-brand); flex-shrink:0; }
.hp-leg__bridge-line { flex:1; height:1px; background:var(--c-muted); opacity:.35; }
.hp-leg__bridge-via { font-family:var(--mono); font-size:.58rem; font-weight:700; color:#fff; background:var(--c-amber); padding:.08rem .32rem; border-radius:4px; flex-shrink:0; }
.hp-leg__bridge-stop { font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; padding:.1rem .42rem; border-radius:4px; }
.hp-leg__bridge-stop--ok  { background:var(--c-green-soft); color:var(--c-green); }
.hp-leg__bridge-stop--via { background:var(--c-amber-soft); color:var(--c-amber); }

/* ── Passenger read-only ── */
.hcf-locked {
    display:inline-flex; align-items:center; gap:.28rem;
    font-size:.65rem; font-weight:700; color:var(--c-green);
    background:var(--c-green-soft); padding:.18rem .55rem; border-radius:20px;
}
.hcf-pax-readonly { padding:.85rem 1.1rem; }
.hcf-pax-readonly__grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:.55rem .85rem; }
.hcf-pax-field { display:flex; flex-direction:column; gap:.18rem; }
.hcf-pax-field__label { font-size:.6rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:var(--c-muted); display:flex; align-items:center; gap:.28rem; }
.hcf-pax-field__label i { font-size:.8rem; }
.hcf-pax-field__val { font-size:.84rem; font-weight:600; color:var(--c-ink); }

/* ── Wallet toggle ── */
.hcf-wallet-toggle { border-bottom:1px solid var(--c-line); }
.hcf-wallet-toggle__label { display:block; cursor:pointer; padding:.8rem 1.1rem; }
.hcf-wallet-toggle__label input[type=checkbox] { display:none; }
.hcf-wallet-toggle__body { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
.hcf-wallet-toggle__left { display:flex; align-items:center; gap:.75rem; }
.hcf-wallet-toggle__switch {
    width:40px; height:22px; background:#e4e9f0; border-radius:11px; flex-shrink:0;
    position:relative; transition:background .2s;
}
.hcf-wallet-toggle__slider {
    position:absolute; top:3px; left:3px;
    width:16px; height:16px; border-radius:50%; background:#fff;
    transition:transform .2s; box-shadow:0 1px 4px rgba(0,0,0,.2);
}
.hcf-wallet-toggle__label input:checked ~ .hcf-wallet-toggle__body .hcf-wallet-toggle__switch { background:var(--c-green); }
.hcf-wallet-toggle__label input:checked ~ .hcf-wallet-toggle__body .hcf-wallet-toggle__slider { transform:translateX(18px); }
.hcf-wallet-applied { padding:.6rem 1.1rem .8rem; background:#f0fdf4; border-bottom:1px solid #bbf7d0; display:flex; flex-direction:column; gap:.35rem; }
.hcf-wallet-applied__row { display:flex; justify-content:space-between; font-size:.78rem; color:var(--c-slate); }
.hcf-wallet-applied__row--rem { font-weight:700; color:var(--c-ink); }

/* ── Payment options ── */
.hcf-payment-remaining { padding:.8rem 1.1rem; }
.hcf-payment-remaining__title { font-size:.72rem; font-weight:700; color:var(--c-muted); letter-spacing:.06em; text-transform:uppercase; margin-bottom:.6rem; }
.hcf-payment-options { display:flex; flex-direction:column; gap:.55rem; }
.hcf-payment-option input[type=radio] { display:none; }
.hcf-payment-option__body {
    display:flex; align-items:center; gap:.8rem;
    padding:.75rem 1rem; border:1.5px solid var(--c-line); border-radius:11px;
    cursor:pointer; transition:border-color .15s, background .15s;
}
.hcf-payment-option input:checked ~ .hcf-payment-option__body {
    border-color:var(--c-brand); background:var(--c-brand-soft);
}
.hcf-pay-icon { font-size:1.5rem; color:var(--c-brand); width:32px; text-align:center; flex-shrink:0; }
.hcf-pay-info { flex:1; }
.hcf-pay-name { font-size:.85rem; font-weight:700; color:var(--c-ink); }
.hcf-pay-desc { font-size:.72rem; color:var(--c-muted); margin-top:.1rem; }
.hcf-payment-option__check { margin-left:auto; color:var(--c-brand); font-size:1.1rem; display:none; }
.hcf-payment-option input:checked ~ .hcf-payment-option__body .hcf-payment-option__check { display:block; }

/* ── Right summary (same as hold.blade) ── */
.hp-summary { background:var(--c-white); border:1px solid var(--c-line); border-radius:14px; box-shadow:var(--c-shadow); overflow:hidden; position:sticky; top:90px; }
.hp-summary__head { padding:.85rem 1.1rem; background:linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%); color:#fff; font-size:.92rem; font-weight:700; display:flex; align-items:center; gap:.45rem; }
.hp-summary__head i { font-size:1.1rem; }
.hp-summary__body { padding:.75rem 1.1rem; border-bottom:1px solid var(--c-line); display:flex; flex-direction:column; gap:.4rem; }
.hp-sum-row { display:flex; justify-content:space-between; align-items:center; font-size:.8rem; color:var(--c-slate); }
.hp-sum-row span:last-child { font-family:var(--mono); font-weight:600; color:var(--c-ink); }
.hcf-sum-wallet { padding:.6rem 1.1rem; background:#f0fdf4; border-bottom:1px solid #bbf7d0; display:flex; flex-direction:column; gap:.35rem; }
.hcf-sum-wallet__deduct .hcf-sum-wallet__amt { color:var(--c-green); font-weight:700; font-family:var(--mono); }
.hp-summary__total { display:flex; justify-content:space-between; align-items:center; padding:.8rem 1.1rem; font-size:.86rem; font-weight:700; color:var(--c-ink); border-bottom:1px solid var(--c-line); }
.hp-summary__total-amount { font-family:var(--mono); font-size:1.22rem; font-weight:700; color:var(--c-brand); display:flex; align-items:baseline; gap:.05rem; }
.hp-summary__meta { padding:.6rem 1.1rem; border-bottom:1px solid var(--c-line); display:flex; flex-direction:column; gap:.28rem; }
.hp-summary__meta-row { display:flex; align-items:center; gap:.4rem; font-size:.74rem; color:var(--c-slate); }
.hp-summary__meta-row i { color:var(--c-brand); font-size:.85rem; flex-shrink:0; }
.hp-summary__footer { padding:.85rem 1.1rem; display:flex; flex-direction:column; gap:.5rem; }

/* ── Pay button (brand primary instead of amber) ── */
.hp-btn-pay {
    width:100%; display:flex; align-items:center; justify-content:center; gap:.35rem;
    background:linear-gradient(180deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color:#fff !important; font:inherit; font-size:.88rem; font-weight:700;
    padding:.75rem 1rem; border:none; border-radius:9px; cursor:pointer;
    box-shadow:0 5px 16px rgba(205,27,79,.3);
    transition:transform .13s, box-shadow .13s;
}
.hp-btn-pay:hover { transform:translateY(-1px); box-shadow:0 9px 24px rgba(205,27,79,.4); }
.hp-btn-pay:disabled { opacity:.6; cursor:not-allowed; transform:none; }

.hp-summary__secure { font-size:.68rem; color:var(--c-muted); text-align:center; display:flex; align-items:center; justify-content:center; gap:.28rem; }
.hp-summary__secure i { color:var(--c-green); }

.dirham { font-family:"UAEDirham","Segoe UI",sans-serif; font-size:.8em; font-weight:400; color:inherit; margin-right:.04rem; vertical-align:baseline; }

@media (max-width: 991px) {
    .hp-leg__row { grid-template-columns:1fr 1fr; gap:.5rem; }
    .hp-leg__airline { grid-column:1 / -1; }
    .hp-leg__bridge { grid-column:1 / -1; flex-direction:row; justify-content:center; gap:.5rem; }
    .hp-leg__bridge-track { width:160px; }
    .hp-leg__pt--arr { text-align:left; }
    .hp-summary { position:static; }
}
</style>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const total         = @json($totalAmount);
    const walletBalance = @json($walletBalance);
    const fmt = v => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const els = {
        useWallet:            document.getElementById('use-wallet'),
        walletAmountInput:    document.getElementById('wallet-amount-input'),
        walletAppliedInfo:    document.getElementById('wallet-applied-info'),
        walletDeductAmount:   document.getElementById('wallet-deduct-amount'),
        remainingAmount:      document.getElementById('remaining-amount'),
        summaryWalletSection: document.getElementById('summary-wallet-section'),
        summaryWalletAmount:  document.getElementById('summary-wallet-amount'),
        summaryNetTotal:      document.getElementById('summary-net-total'),
        summaryTotalLabel:    document.getElementById('summary-total-label'),
        remainingSection:     document.getElementById('remaining-payment-section'),
        remainingTitle:       document.getElementById('remaining-payment-title'),
        payBtn:               document.getElementById('pay-btn'),
        payBtnText:           document.getElementById('pay-btn-text'),
    };

    function recalc() {
        const useWallet    = els.useWallet && els.useWallet.checked;
        const deduction    = useWallet ? Math.min(walletBalance, total) : 0;
        const remaining    = total - deduction;
        const walletAll    = deduction >= total;

        if (els.walletAmountInput)    els.walletAmountInput.value       = deduction.toFixed(2);
        if (els.walletAppliedInfo)    els.walletAppliedInfo.style.display = useWallet ? 'block' : 'none';
        if (els.walletDeductAmount)   els.walletDeductAmount.textContent  = fmt(deduction);
        if (els.remainingAmount)      els.remainingAmount.textContent     = fmt(remaining);

        if (els.summaryWalletSection) els.summaryWalletSection.style.display = useWallet ? 'block' : 'none';
        if (els.summaryWalletAmount)  els.summaryWalletAmount.textContent    = fmt(deduction);
        if (els.summaryNetTotal)      els.summaryNetTotal.textContent        = fmt(remaining > 0 ? remaining : 0);
        if (els.summaryTotalLabel)    els.summaryTotalLabel.textContent      = useWallet ? 'Amount Due' : 'Total Due';

        if (els.remainingSection) {
            if (walletAll && useWallet) {
                els.remainingSection.style.display = 'none';
                document.querySelectorAll('input[name="payment_method"]').forEach(r => { r.required = false; r.checked = false; });
            } else {
                els.remainingSection.style.display = 'block';
                document.querySelectorAll('input[name="payment_method"]').forEach(r => r.required = true);
                if (!document.querySelector('input[name="payment_method"]:checked')) {
                    const first = document.querySelector('input[name="payment_method"][value="payby"]');
                    if (first) first.checked = true;
                }
            }
        }
        if (els.remainingTitle) {
            els.remainingTitle.textContent = useWallet && remaining > 0
                ? 'Pay Remaining AED ' + fmt(remaining) + ' via'
                : 'Select Payment Method';
        }

        if (els.payBtnText) {
            if (walletAll && useWallet) {
                els.payBtnText.innerHTML = 'Pay with Wallet &amp; Issue Ticket';
            } else {
                els.payBtnText.innerHTML = 'Pay <span class="dirham">AED</span> ' + fmt(remaining) + ' &amp; Issue Ticket';
            }
        }
    }

    if (els.useWallet) {
        els.useWallet.addEventListener('change', recalc);
    }

    recalc();

    /* Disable button on submit */
    document.getElementById('confirmPayForm').addEventListener('submit', function () {
        if (els.payBtn) {
            els.payBtn.disabled = true;
            els.payBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing…';
        }
    });
});
</script>
@endpush
