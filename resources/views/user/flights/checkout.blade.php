@extends('user.layouts.main')

@section('content')
    @php
        $itinerary = $itinerary ?? [];
        $legs = $itinerary['legs'] ?? [];
        $adults = (int) ($searchParams['adults'] ?? 1);
        $children = (int) ($searchParams['children'] ?? 0);
        $infants = (int) ($searchParams['infants'] ?? 0);
        $passengerTotal = max(1, $adults + $children + $infants);
        $paxCount = $passengerTotal;

        $totalAmount = (float) ($itinerary['totalPrice'] ?? ($totalAmount ?? 0));
        $baseAmount = (float) ($itinerary['basePrice'] ?? $totalAmount);
        $taxAmount = (float) ($itinerary['taxes'] ?? 0);
        if ($baseAmount + $taxAmount < 0.01) {
            $baseAmount = $totalAmount;
        }

        $walletBalance = (float) ($walletBalance ?? 0);
        $currency = $currency ?? 'AED';
        $from = strtoupper($searchParams['from'] ?? '');
        $to = strtoupper($searchParams['to'] ?? '');
        $isRound = !empty($searchParams['return_date']);

        $paxStr = $adults . ' Adult' . ($adults > 1 ? 's' : '');
        if ($children) {
            $paxStr .= ', ' . $children . ' Child' . ($children > 1 ? 'ren' : '');
        }
        if ($infants) {
            $paxStr .= ', ' . $infants . ' Infant' . ($infants > 1 ? 's' : '');
        }

        $searchBackUrl = route('user.flights.search') . '?' . http_build_query(array_filter($searchParams ?? []));

        $depLabel = '';
        $retLabel = '';
        if (!empty($searchParams['departure_date'])) {
            try {
                $depLabel = \Carbon\Carbon::parse($searchParams['departure_date'])->format('d M Y');
            } catch (\Throwable $e) {
                $depLabel = (string) $searchParams['departure_date'];
            }
        }
        if (!empty($searchParams['return_date'])) {
            try {
                $retLabel = \Carbon\Carbon::parse($searchParams['return_date'])->format('d M Y');
            } catch (\Throwable $e) {
                $retLabel = (string) $searchParams['return_date'];
            }
        }
    @endphp

    <div class="hp">
        <div class="container hp-shell">

            <nav class="hp-crumb" aria-label="breadcrumb">
                <a href="{{ route('user.flights.index') }}"><i class="bx bx-plane-take-off"></i> Flights</a>
                <i class="bx bx-chevron-right hp-crumb__sep"></i>
                <a href="{{ $searchBackUrl }}">Search Results</a>
                <i class="bx bx-chevron-right hp-crumb__sep"></i>
                <span>Book &amp; Pay</span>
            </nav>

            <div class="hcf-checkout-notice">
                <i class="bx bx-info-circle"></i>
                <div>
                    Review flight and passenger details, choose payment (or wallet), then complete checkout.
                    You will receive confirmation by email after the ticket is issued.
                </div>
            </div>

            <form id="flightCheckoutForm" action="{{ route('user.flights.payment.process') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="itinerary_id" value="{{ $itineraryId }}">

                <div class="row g-4">

                    <div class="col-lg-8">

                        {{-- Flight summary --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bxs-plane hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__eyebrow">Flight Summary</div>
                                    <div class="hp-card__title">
                                        {{ $from }}
                                        @if (count($legs) > 1) ⇄ @else → @endif
                                        {{ $to }}
                                    </div>
                                </div>
                                <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                                    @if (!empty($itinerary['non_refundable']))
                                        <span class="hp-badge hp-badge--nr">Non-Refundable</span>
                                    @else
                                        <span class="hp-badge hp-badge--ref">Refundable</span>
                                    @endif
                                </div>
                            </div>
                            <div class="hp-flight">
                                @include('user.flights.partials.hp-itinerary-legs', ['legs' => $legs, 'isRound' => $isRound])
                            </div>
                        </div>

                        {{-- Lead passenger --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bx-user hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__eyebrow">Contact</div>
                                    <div class="hp-card__title">Lead Passenger</div>
                                </div>
                            </div>
                            <div class="hp-pax-fields">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="lead[title]" required>
                                            <option value="Mr">Mr.</option>
                                            <option value="Mrs">Mrs.</option>
                                            <option value="Ms">Ms.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="lead[first_name]" required autocomplete="given-name">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="lead[last_name]" required autocomplete="family-name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="hp-label">Email <span class="hp-req">*</span></label>
                                        <input type="email" class="hp-input" name="lead[email]" required autocomplete="email">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="hp-label">Phone <span class="hp-req">*</span></label>
                                        <input type="tel" class="hp-input" name="lead[phone]" required autocomplete="tel">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="hp-label">Address</label>
                                        <input type="text" class="hp-input" name="lead[address]" autocomplete="street-address">
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php $pIndex = 0; @endphp

                        @for ($i = 0; $i < $adults; $i++)
                            <div class="hp-card mb-3">
                                <div class="hp-card__head">
                                    <i class="bx bx-user hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger Information</div>
                                        <div class="hp-card__title">Adult {{ $i + 1 }} <span class="hp-card__age">12+ years</span></div>
                                    </div>
                                </div>
                                <div class="hp-pax-fields">
                                    <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="ADT">
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <label class="hp-label">Title <span class="hp-req">*</span></label>
                                            <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                                <option value="Mr">Mr.</option>
                                                <option value="Mrs">Mrs.</option>
                                                <option value="Ms">Ms.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                            <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                            <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        @for ($i = 0; $i < $children; $i++)
                            <div class="hp-card mb-3">
                                <div class="hp-card__head">
                                    <i class="bx bx-child hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger Information</div>
                                        <div class="hp-card__title">Child {{ $i + 1 }} <span class="hp-card__age">2–11 years</span></div>
                                    </div>
                                </div>
                                <div class="hp-pax-fields">
                                    <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="C06">
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <label class="hp-label">Title <span class="hp-req">*</span></label>
                                            <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                                <option value="Mstr">Mstr.</option>
                                                <option value="Miss">Miss</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                            <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                            <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        @for ($i = 0; $i < $infants; $i++)
                            <div class="hp-card mb-3">
                                <div class="hp-card__head">
                                    <i class="bx bx-baby-carriage hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger Information</div>
                                        <div class="hp-card__title">Infant {{ $i + 1 }} <span class="hp-card__age">Under 2 years</span></div>
                                    </div>
                                </div>
                                <div class="hp-pax-fields">
                                    <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="INF">
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <label class="hp-label">Title <span class="hp-req">*</span></label>
                                            <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                                <option value="Mstr">Mstr.</option>
                                                <option value="Miss">Miss</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                            <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                            <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        {{-- Payment --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bx-credit-card hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__title" style="margin-top:0;">Payment Method</div>
                                </div>
                            </div>

                            @if ($walletBalance > 0)
                                <div class="hcf-wallet-toggle" id="wallet-toggle-section">
                                    <label class="hcf-wallet-toggle__label">
                                        <input type="checkbox" id="use-wallet" name="use_wallet" value="1">
                                        <div class="hcf-wallet-toggle__body">
                                            <div class="hcf-wallet-toggle__left">
                                                <div class="hcf-pay-icon"><i class="bx bxs-wallet"></i></div>
                                                <div class="hcf-pay-info">
                                                    <div class="hcf-pay-name">Use Wallet Balance</div>
                                                    <div class="hcf-pay-desc">Available:
                                                        <strong><span class="dirham">{{ $currency }}</span> {{ number_format($walletBalance, 2) }}</strong>
                                                    </div>
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
                                            <span><span class="dirham">{{ $currency }}</span> <span id="wallet-deduct-amount">0.00</span></span>
                                        </div>
                                        <div class="hcf-wallet-applied__row hcf-wallet-applied__row--rem">
                                            <span>Remaining to pay</span>
                                            <span><span class="dirham">{{ $currency }}</span> <span id="remaining-amount">0.00</span></span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="wallet_amount" id="wallet-amount-input" value="0">
                                </div>
                            @endif

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

                    </div>

                    <div class="col-lg-4">
                        <div class="hp-summary" id="hp-summary-sticky">
                            <div class="hp-summary__head">
                                <i class="bx bx-receipt"></i>
                                Fare Summary
                            </div>

                            <div class="hp-summary__body">
                                @php $adultBase = $adults > 0 ? round($baseAmount / $paxCount, 2) : 0; @endphp
                                @if ($adults > 0)
                                    <div class="hp-sum-row">
                                        <span>Adult × {{ $adults }}</span>
                                        <span><span class="dirham">{{ $currency }}</span> {{ number_format($adultBase * $adults, 2) }}</span>
                                    </div>
                                @endif
                                @if ($children > 0)
                                    @php $childBase = round($baseAmount / $paxCount, 2); @endphp
                                    <div class="hp-sum-row">
                                        <span>Child × {{ $children }}</span>
                                        <span><span class="dirham">{{ $currency }}</span> {{ number_format($childBase * $children, 2) }}</span>
                                    </div>
                                @endif
                                @if ($infants > 0)
                                    @php $infantBase = round($baseAmount / $paxCount * 0.1, 2); @endphp
                                    <div class="hp-sum-row">
                                        <span>Infant × {{ $infants }}</span>
                                        <span><span class="dirham">{{ $currency }}</span> {{ number_format($infantBase * $infants, 2) }}</span>
                                    </div>
                                @endif
                                @if ($taxAmount > 0)
                                    <div class="hp-sum-row">
                                        <span>Taxes &amp; Fees</span>
                                        <span><span class="dirham">{{ $currency }}</span> {{ number_format($taxAmount, 2) }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="hcf-sum-wallet" id="summary-wallet-section" style="display:none;">
                                <div class="hp-sum-row">
                                    <span>Subtotal</span>
                                    <span><span class="dirham">{{ $currency }}</span> {{ number_format($totalAmount, 2) }}</span>
                                </div>
                                <div class="hp-sum-row hcf-sum-wallet__deduct">
                                    <span><i class="bx bxs-wallet" style="vertical-align:middle;margin-right:3px;"></i> Wallet Applied</span>
                                    <span class="hcf-sum-wallet__amt">– <span class="dirham">{{ $currency }}</span> <span id="summary-wallet-amount">0.00</span></span>
                                </div>
                            </div>

                            <div class="hp-summary__total">
                                <span id="summary-total-label">Total Due</span>
                                <span class="hp-summary__total-amount">
                                    <span class="dirham">{{ $currency }}</span><span id="summary-net-total">{{ number_format($totalAmount, 2) }}</span>
                                </span>
                            </div>

                            <div class="hp-summary__meta">
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-user"></i>
                                    <span>{{ $paxStr }}</span>
                                </div>
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-map"></i>
                                    <span>{{ $from }} @if($isRound) ⇄ @else → @endif {{ $to }}</span>
                                </div>
                                <div class="hp-summary__meta-row">
                                    <i class="bx bxs-calendar"></i>
                                    <span>
                                        {{ $depLabel }}
                                        @if ($isRound && $retLabel)
                                            – {{ $retLabel }}
                                        @endif
                                    </span>
                                </div>
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-check-circle"></i>
                                    <span>PNR is created after successful payment</span>
                                </div>
                            </div>

                            <div class="hp-summary__footer">
                                <button type="submit" form="flightCheckoutForm" class="hp-btn-pay" id="pay-btn">
                                    <i class="bx bx-lock-alt"></i>
                                    <span id="pay-btn-text">Pay <span class="dirham">{{ $currency }}</span> {{ number_format($totalAmount, 2) }} &amp; Book Flight</span>
                                </button>
                                <div class="hp-summary__secure">
                                    <i class="bx bxs-lock-alt"></i> 256-bit SSL secure transaction
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>
@endsection

@push('css')
    <style>
        @include('user.flights.partials.hold-confirm-styles')

        .hcf-checkout-notice {
            display:flex; align-items:center; gap:.75rem;
            background:#eff6ff; border:1px solid #93c5fd; border-radius:10px;
            padding:.75rem 1rem; margin-bottom:1.25rem;
            font-size:.82rem; color:#1e3a5f;
        }
        .hcf-checkout-notice i { font-size:1.2rem; color:#2563eb; flex-shrink:0; }

        .hp-pax-fields { padding:.85rem 1.1rem 1rem; }
        .hp-label {
            font-size:.68rem; font-weight:700; color:var(--c-slate);
            display:block; margin-bottom:.28rem;
            text-transform:uppercase; letter-spacing:.07em;
        }
        .hp-req { color:var(--c-brand); }
        .hp-input {
            width:100%; padding:.55rem .75rem;
            border:1.5px solid var(--c-line); border-radius:8px;
            font:inherit; font-size:.86rem; color:var(--c-ink); background:#fff;
            transition:border-color .14s, box-shadow .14s; outline:none;
        }
        .hp-input:focus { border-color:var(--c-brand); box-shadow:0 0 0 3px rgba(205,27,79,.1); }
        .hp-input::placeholder { color:var(--c-muted); }
        .hp-select {
            width:100%; padding:.55rem .75rem;
            border:1.5px solid var(--c-line); border-radius:8px;
            font:inherit; font-size:.86rem; color:var(--c-ink); background:#fff;
            transition:border-color .14s; outline:none; appearance:auto;
        }
        .hp-select:focus { border-color:var(--c-brand); box-shadow:0 0 0 3px rgba(205,27,79,.1); }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const total = @json($totalAmount);
            const walletBalance = @json($walletBalance);
            const currency = @json($currency);
            const fmt = v => Number(v).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const els = {
                useWallet: document.getElementById('use-wallet'),
                walletAmountInput: document.getElementById('wallet-amount-input'),
                walletAppliedInfo: document.getElementById('wallet-applied-info'),
                walletDeductAmount: document.getElementById('wallet-deduct-amount'),
                remainingAmount: document.getElementById('remaining-amount'),
                summaryWalletSection: document.getElementById('summary-wallet-section'),
                summaryWalletAmount: document.getElementById('summary-wallet-amount'),
                summaryNetTotal: document.getElementById('summary-net-total'),
                summaryTotalLabel: document.getElementById('summary-total-label'),
                remainingSection: document.getElementById('remaining-payment-section'),
                remainingTitle: document.getElementById('remaining-payment-title'),
                payBtn: document.getElementById('pay-btn'),
                payBtnText: document.getElementById('pay-btn-text'),
            };

            function recalc() {
                const useWallet = els.useWallet && els.useWallet.checked;
                const deduction = useWallet ? Math.min(walletBalance, total) : 0;
                const remaining = total - deduction;
                const walletAll = deduction >= total;

                if (els.walletAmountInput) els.walletAmountInput.value = deduction.toFixed(2);
                if (els.walletAppliedInfo) els.walletAppliedInfo.style.display = useWallet ? 'block' : 'none';
                if (els.walletDeductAmount) els.walletDeductAmount.textContent = fmt(deduction);
                if (els.remainingAmount) els.remainingAmount.textContent = fmt(remaining);

                if (els.summaryWalletSection) els.summaryWalletSection.style.display = useWallet ? 'block' : 'none';
                if (els.summaryWalletAmount) els.summaryWalletAmount.textContent = fmt(deduction);
                if (els.summaryNetTotal) els.summaryNetTotal.textContent = fmt(remaining > 0 ? remaining : 0);
                if (els.summaryTotalLabel) els.summaryTotalLabel.textContent = useWallet ? 'Amount Due' : 'Total Due';

                if (els.remainingSection) {
                    if (walletAll && useWallet) {
                        els.remainingSection.style.display = 'none';
                        document.querySelectorAll('input[name="payment_method"]').forEach(r => {
                            r.required = false;
                            r.checked = false;
                        });
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
                        ? 'Pay Remaining ' + currency + ' ' + fmt(remaining) + ' via'
                        : 'Select Payment Method';
                }

                if (els.payBtnText) {
                    if (walletAll && useWallet) {
                        els.payBtnText.innerHTML = 'Pay with Wallet &amp; Book Flight';
                    } else {
                        els.payBtnText.innerHTML = 'Pay <span class="dirham">' + currency + '</span> ' + fmt(remaining) +
                            ' &amp; Book Flight';
                    }
                }
            }

            if (els.useWallet) {
                els.useWallet.addEventListener('change', recalc);
            }

            recalc();

            const form = document.getElementById('flightCheckoutForm');
            if (form) {
                form.addEventListener('submit', function() {
                    if (els.payBtn) {
                        els.payBtn.disabled = true;
                        els.payBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing…';
                    }
                });
            }
        });
    </script>
@endpush
