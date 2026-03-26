@extends('user.layouts.main')
@section('content')
    @php
        $adults = (int) ($searchParams['adults'] ?? 1);
        $children = (int) ($searchParams['children'] ?? 0);
        $infants = (int) ($searchParams['infants'] ?? 0);
        $passengerTotal = $adults + $children + $infants;
        $walletBalance = $walletBalance ?? 0;
        $routeFrom = strtoupper($searchParams['from'] ?? '');
        $routeTo = strtoupper($searchParams['to'] ?? '');
    @endphp

    <div class="hc-page">
        <div class="container">
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.flights.index') }}">Flights</a>
                <i class="bx bx-chevron-right"></i>
                <span>Checkout</span>
            </nav>

            <form id="flightCheckoutForm" action="{{ route('user.flights.payment.process') }}" method="POST">
                @csrf
                <input type="hidden" name="itinerary_id" value="{{ $itineraryId }}">

                <div class="row">
                    <div class="col-lg-8">
                        <div class="hc-card mb-3">
                            <div class="hc-card__header">
                                <i class="bx bx-plane-alt"></i>
                                <div class="hc-card__title">Flight Summary</div>
                            </div>
                            <div class="hc-summary__details">
                                <div class="hc-summary__detail">
                                    <i class="bx bx-map"></i>
                                    <span>{{ $routeFrom }} ? {{ $routeTo }}</span>
                                </div>
                                <div class="hc-summary__detail">
                                    <i class="bx bxs-calendar"></i>
                                    <span>
                                        {{ $searchParams['departure_date'] ?? '' }}
                                        @if (!empty($searchParams['return_date']))
                                            &mdash; {{ $searchParams['return_date'] }}
                                        @endif
                                    </span>
                                </div>
                                <div class="hc-summary__detail">
                                    <i class="bx bxs-group"></i>
                                    <span>
                                        {{ $adults }} Adult{{ $adults > 1 ? 's' : '' }}
                                        @if ($children > 0)
                                            , {{ $children }} Child{{ $children > 1 ? 'ren' : '' }}
                                        @endif
                                        @if ($infants > 0)
                                            , {{ $infants }} Infant{{ $infants > 1 ? 's' : '' }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="hc-card__body">
                                @foreach ($itinerary['legs'] ?? [] as $legIndex => $leg)
                                    <div class="hl-card hl-card--flight mb-3">
                                        <div class="hl-card__body">
                                            <div class="hl-card__info">
                                                <div class="hl-card__name">Leg {{ $legIndex + 1 }}</div>
                                                <div class="hl-card__location">
                                                    <i class="bx bx-time-five"></i>
                                                    {{ $leg['elapsedTime'] ?? '-' }} min
                                                </div>
                                                <div class="fl-card__segments">
                                                    @foreach ($leg['segments'] as $seg)
                                                        <div class="fl-card__segment">
                                                            <span class="fl-card__segment-code">{{ $seg['from'] }} ? {{ $seg['to'] }}</span>
                                                            <span class="fl-card__segment-time">{{ $seg['departure_time'] }} - {{ $seg['arrival_time'] }}</span>
                                                            <span class="fl-card__segment-flight">{{ $seg['carrier'] }}{{ $seg['flight_number'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="hc-card mb-3">
                            <div class="hc-card__header">
                                <i class="bx bx-user"></i>
                                <div class="hc-card__title">Lead Passenger</div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <label class="hc-label">Title</label>
                                    <select class="hc-select" name="lead[title]" required>
                                        <option value="Mr">Mr.</option>
                                        <option value="Mrs">Mrs.</option>
                                        <option value="Ms">Ms.</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="hc-label">First Name *</label>
                                    <input type="text" class="hc-input" name="lead[first_name]" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="hc-label">Last Name *</label>
                                    <input type="text" class="hc-input" name="lead[last_name]" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hc-label">Email *</label>
                                    <input type="email" class="hc-input" name="lead[email]" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hc-label">Phone *</label>
                                    <input type="tel" class="hc-input" name="lead[phone]" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="hc-label">Address</label>
                                    <input type="text" class="hc-input" name="lead[address]">
                                </div>
                            </div>
                        </div>

                        @php $pIndex = 0; @endphp
                        @for ($i = 0; $i < $adults; $i++)
                            <div class="hc-card mb-3">
                                <div class="hc-card__header">
                                    <i class="bx bx-user"></i>
                                    <div class="hc-card__title">Adult {{ $i + 1 }}</div>
                                </div>
                                <div class="row g-2">
                                    <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="ADT">
                                    <div class="col-md-2">
                                        <label class="hc-label">Title</label>
                                        <select class="hc-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mr">Mr.</option>
                                            <option value="Mrs">Mrs.</option>
                                            <option value="Ms">Ms.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">First Name *</label>
                                        <input type="text" class="hc-input" name="passengers[{{ $pIndex }}][first_name]" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">Last Name *</label>
                                        <input type="text" class="hc-input" name="passengers[{{ $pIndex }}][last_name]" required>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        @for ($i = 0; $i < $children; $i++)
                            <div class="hc-card mb-3">
                                <div class="hc-card__header">
                                    <i class="bx bx-child"></i>
                                    <div class="hc-card__title">Child {{ $i + 1 }}</div>
                                </div>
                                <div class="row g-2">
                                    <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="C06">
                                    <div class="col-md-2">
                                        <label class="hc-label">Title</label>
                                        <select class="hc-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mstr">Mstr.</option>
                                            <option value="Miss">Miss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">First Name *</label>
                                        <input type="text" class="hc-input" name="passengers[{{ $pIndex }}][first_name]" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">Last Name *</label>
                                        <input type="text" class="hc-input" name="passengers[{{ $pIndex }}][last_name]" required>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        @for ($i = 0; $i < $infants; $i++)
                            <div class="hc-card mb-3">
                                <div class="hc-card__header">
                                    <i class="bx bx-baby-carriage"></i>
                                    <div class="hc-card__title">Infant {{ $i + 1 }}</div>
                                </div>
                                <div class="row g-2">
                                    <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="INF">
                                    <div class="col-md-2">
                                        <label class="hc-label">Title</label>
                                        <select class="hc-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mstr">Mstr.</option>
                                            <option value="Miss">Miss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">First Name *</label>
                                        <input type="text" class="hc-input" name="passengers[{{ $pIndex }}][first_name]" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">Last Name *</label>
                                        <input type="text" class="hc-input" name="passengers[{{ $pIndex }}][last_name]" required>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        <div class="hc-card mb-3">
                            <div class="hc-card__header">
                                <i class="bx bx-credit-card"></i>
                                <div class="hc-card__title">Payment Method</div>
                            </div>

                            @if ($walletBalance > 0)
                                <div class="hc-wallet-toggle" id="wallet-toggle-section">
                                    <label class="hc-wallet-toggle__label">
                                        <input type="checkbox" id="use-wallet" name="use_wallet" value="1">
                                        <div class="hc-wallet-toggle__body">
                                            <div class="hc-wallet-toggle__left">
                                                <div class="hc-payment-option__icon"><i class="bx bxs-wallet"></i></div>
                                                <div class="hc-payment-option__info">
                                                    <div class="hc-payment-option__name">Use Wallet Balance</div>
                                                    <div class="hc-payment-option__desc">Available: <strong><span class="dirham">D</span> {{ number_format($walletBalance, 2) }}</strong></div>
                                                </div>
                                            </div>
                                            <div class="hc-wallet-toggle__switch">
                                                <span class="hc-wallet-toggle__slider"></span>
                                            </div>
                                        </div>
                                    </label>
                                    <div class="hc-wallet-applied" id="wallet-applied-info" style="display: none;">
                                        <div class="hc-wallet-applied__row">
                                            <span>Wallet deduction</span>
                                            <span><span class="dirham">D</span> <span id="wallet-deduct-amount">0.00</span></span>
                                        </div>
                                        <div class="hc-wallet-applied__row hc-wallet-applied__row--remaining">
                                            <span>Remaining to pay</span>
                                            <span><span class="dirham">D</span> <span id="remaining-amount">0.00</span></span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="wallet_amount" id="wallet-amount-input" value="0">
                                </div>
                            @endif

                            <div class="hc-payment-remaining" id="remaining-payment-section">
                                <div class="hc-payment-remaining__title" id="remaining-payment-title">Select Payment Method</div>
                                <div class="hc-payment-options">
                                    <label class="hc-payment-option">
                                        <input type="radio" name="payment_method" value="payby" required checked>
                                        <div class="hc-payment-option__body">
                                            <div class="hc-payment-option__icon"><i class="bx bx-card"></i></div>
                                            <div class="hc-payment-option__info">
                                                <div class="hc-payment-option__name">PayBy</div>
                                                <div class="hc-payment-option__desc">Credit / Debit card checkout</div>
                                            </div>
                                            <div class="hc-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                        </div>
                                    </label>

                                    <label class="hc-payment-option">
                                        <input type="radio" name="payment_method" value="tabby" required>
                                        <div class="hc-payment-option__body">
                                            <div class="hc-payment-option__icon"><i class="bx bx-calendar-check"></i></div>
                                            <div class="hc-payment-option__info">
                                                <div class="hc-payment-option__name">Tabby - Buy Now Pay Later</div>
                                                <div class="hc-payment-option__desc">4 interest-free installments</div>
                                            </div>
                                            <div class="hc-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                        </div>
                                    </label>

                                    <label class="hc-payment-option">
                                        <input type="radio" name="payment_method" value="tamara" required>
                                        <div class="hc-payment-option__body">
                                            <div class="hc-payment-option__icon"><i class="bx bx-wallet-alt"></i></div>
                                            <div class="hc-payment-option__info">
                                                <div class="hc-payment-option__name">Tamara - Buy Now Pay Later</div>
                                                <div class="hc-payment-option__desc">Split payment with Tamara installments</div>
                                            </div>
                                            <div class="hc-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="hc-btn hc-btn--primary hc-btn--full mt-3" id="pay-btn">
                            <i class="bx bx-lock-alt"></i> Pay Now
                        </button>
                        <div class="hc-secure-note">
                            <i class="bx bx-check-shield"></i> Secure Checkout
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="hc-summary">
                            <div class="hc-summary__details">
                                <div class="hc-summary__detail">
                                    <i class="bx bx-map"></i>
                                    <span>{{ $routeFrom }} ? {{ $routeTo }}</span>
                                </div>
                                <div class="hc-summary__detail">
                                    <i class="bx bxs-group"></i>
                                    <span>{{ $passengerTotal }} Passenger{{ $passengerTotal > 1 ? 's' : '' }}</span>
                                </div>
                            </div>

                            <div class="hc-summary__wallet" id="summary-wallet-section" style="display:none;">
                                <div class="hc-summary__line">
                                    <span>Subtotal</span>
                                    <span><span class="dirham">D</span> <span id="summary-subtotal">{{ number_format($totalAmount, 2) }}</span></span>
                                </div>
                                <div class="hc-summary__line hc-summary__line--wallet">
                                    <span><i class="bx bxs-wallet" style="font-size: 0.9rem; vertical-align: middle;"></i> Wallet Applied</span>
                                    <span class="hc-summary__wallet-amount">- <span class="dirham">D</span> <span id="summary-wallet-amount">0.00</span></span>
                                </div>
                            </div>

                            <div class="hc-summary__total">
                                <span id="summary-total-label">Total Price</span>
                                <span class="hc-summary__total-price">
                                    <span class="dirham">D</span> <span id="summary-net-total">{{ number_format($totalAmount, 2) }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const totalAmount = @json($totalAmount);
            const walletBalance = @json($walletBalance);
            const formatPrice = (v) => Number(v).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const els = {
                summarySubtotal: document.getElementById('summary-subtotal'),
                summaryWalletAmount: document.getElementById('summary-wallet-amount'),
                summaryWalletSection: document.getElementById('summary-wallet-section'),
                summaryNet: document.getElementById('summary-net-total'),
                summaryTotalLabel: document.getElementById('summary-total-label'),
                walletDeductAmount: document.getElementById('wallet-deduct-amount'),
                remainingAmount: document.getElementById('remaining-amount'),
                walletAppliedInfo: document.getElementById('wallet-applied-info'),
                walletAmountInput: document.getElementById('wallet-amount-input'),
                remainingPaymentSection: document.getElementById('remaining-payment-section'),
                remainingPaymentTitle: document.getElementById('remaining-payment-title'),
                useWallet: document.getElementById('use-wallet')
            };

            function recalcWallet() {
                const useWallet = els.useWallet && els.useWallet.checked;
                const walletDeduction = useWallet ? Math.min(walletBalance, totalAmount) : 0;
                const remaining = totalAmount - walletDeduction;
                const walletCoversAll = walletDeduction >= totalAmount;

                if (els.walletAmountInput) els.walletAmountInput.value = walletDeduction.toFixed(2);

                if (els.walletAppliedInfo) els.walletAppliedInfo.style.display = useWallet ? 'block' : 'none';
                if (els.walletDeductAmount) els.walletDeductAmount.textContent = formatPrice(walletDeduction);
                if (els.remainingAmount) els.remainingAmount.textContent = formatPrice(remaining);

                if (els.summaryWalletSection) els.summaryWalletSection.style.display = useWallet ? 'block' : 'none';
                if (els.summarySubtotal) els.summarySubtotal.textContent = formatPrice(totalAmount);
                if (els.summaryWalletAmount) els.summaryWalletAmount.textContent = formatPrice(walletDeduction);
                if (els.summaryNet) els.summaryNet.textContent = formatPrice(remaining > 0 ? remaining : 0);
                if (els.summaryTotalLabel) els.summaryTotalLabel.textContent = useWallet ? 'Amount to Pay' : 'Total Price';

                if (els.remainingPaymentSection) {
                    if (walletCoversAll && useWallet) {
                        els.remainingPaymentSection.style.display = 'none';
                        const radios = document.querySelectorAll('input[name="payment_method"]');
                        radios.forEach(r => {
                            r.required = false;
                            r.checked = false;
                        });
                    } else {
                        els.remainingPaymentSection.style.display = 'block';
                        const radios = document.querySelectorAll('input[name="payment_method"]');
                        radios.forEach(r => r.required = true);
                        if (!document.querySelector('input[name="payment_method"]:checked')) {
                            const firstRadio = document.querySelector('input[name="payment_method"][value="payby"]');
                            if (firstRadio) firstRadio.checked = true;
                        }
                    }
                    if (els.remainingPaymentTitle) {
                        els.remainingPaymentTitle.textContent = useWallet && remaining > 0
                            ? `Pay Remaining D ${formatPrice(remaining)} via`
                            : 'Select Payment Method';
                    }
                }

                const payBtn = document.getElementById('pay-btn');
                if (payBtn && !payBtn.disabled) {
                    if (walletCoversAll && useWallet) {
                        payBtn.innerHTML = '<i class="bx bx-lock-alt"></i> Pay with Wallet';
                    } else if (useWallet) {
                        payBtn.innerHTML = `<i class="bx bx-lock-alt"></i> Pay D ${formatPrice(remaining)}`;
                    } else {
                        payBtn.innerHTML = '<i class="bx bx-lock-alt"></i> Pay Now';
                    }
                }
            }

            if (els.useWallet) {
                els.useWallet.addEventListener('change', recalcWallet);
            }

            const form = document.getElementById('flightCheckoutForm');
            const payBtn = document.getElementById('pay-btn');
            if (form && payBtn) {
                form.addEventListener('submit', function() {
                    payBtn.disabled = true;
                    payBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
                });
            }

            recalcWallet();
        });
    </script>
@endpush

@push('css')
    <style>
        .fl-card__segments {
            margin-top: 0.6rem;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fl-card__segment {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.78rem;
            color: #666;
        }

        .fl-card__segment-code {
            font-weight: 700;
            color: #333;
        }

        .fl-card__segment-flight {
            font-weight: 600;
            color: var(--color-primary);
        }
    </style>
@endpush
