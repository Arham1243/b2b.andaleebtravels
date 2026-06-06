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

        $fareBreakdown = flightFareBreakdown($itinerary, (float) ($totalAmount ?? 0));
        $totalAmount = (float) ($fareBreakdown['total_amount'] ?? $totalAmount ?? 0);
        $baseAmount = (float) ($fareBreakdown['base_fare'] ?? $totalAmount);
        $taxAmount = (float) ($fareBreakdown['tax_charges'] ?? 0);
        if (! ($fareBreakdown['has_breakdown'] ?? false) && $baseAmount + $taxAmount < 0.01) {
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

        $savedPassengers = $savedPassengers ?? [];
        $countries = $countries ?? [];
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
                <input type="hidden" name="fare_option" value="{{ $selectedFareIndex ?? 0 }}">

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

                                <div class="hp-pax-note">
                                    <i class="bx bx-info-circle"></i>
                                    Traveller's passport should be valid for at least 6 months from the date of travel.
                                </div>

                                @if (!empty($savedPassengers))
                                    @include('user.flights.partials.hp-saved-passenger-search', [
                                        'pIndex' => $pIndex,
                                        'savedPassengers' => $savedPassengers,
                                    ])
                                @endif

                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="ADT">
                                <div class="row g-3 hp-pax-fields">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mr">Mr.</option>
                                            <option value="Mrs">Mrs.</option>
                                            <option value="Ms">Ms.</option>
                                            <option value="Dr">Dr.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]"
                                            placeholder="Enter first name" required autocomplete="given-name">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]"
                                            placeholder="Enter last name" required autocomplete="family-name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                        <span class="hp-hint">Age calculated as per travel date</span>
                                    </div>
                                    @include('user.flights.partials.hp-country-field', [
                                        'name' => 'passengers['.$pIndex.'][nationality]',
                                        'label' => 'Nationality',
                                        'required' => true,
                                    ])
                                    @include('user.flights.partials.hp-country-field', [
                                        'name' => 'passengers['.$pIndex.'][issuing_country]',
                                        'label' => 'Issuing Country',
                                        'required' => true,
                                    ])
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Number</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][passport_no]"
                                            placeholder="Passport number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Expiry</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][passport_exp]">
                                    </div>
                                    <div class="col-12">
                                        <label class="hp-save-check">
                                            <input type="checkbox" name="passengers[{{ $pIndex }}][save_profile]" value="1">
                                            <span>Save this passenger to my profile for future bookings</span>
                                        </label>
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
                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="C06">
                                <div class="row g-3 hp-pax-fields">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mstr">Mstr.</option>
                                            <option value="Miss">Miss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" placeholder="Enter first name" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" placeholder="Enter last name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                    </div>
                                    @include('user.flights.partials.hp-country-field', [
                                        'name' => 'passengers['.$pIndex.'][nationality]',
                                        'label' => 'Nationality',
                                        'required' => true,
                                    ])
                                    @include('user.flights.partials.hp-country-field', [
                                        'name' => 'passengers['.$pIndex.'][issuing_country]',
                                        'label' => 'Issuing Country',
                                        'required' => true,
                                    ])
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Number</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][passport_no]" placeholder="Passport number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Expiry</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][passport_exp]">
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
                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="INF">
                                <div class="row g-3 hp-pax-fields">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mstr">Mstr.</option>
                                            <option value="Miss">Miss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" placeholder="Enter first name" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" placeholder="Enter last name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                    </div>
                                    @include('user.flights.partials.hp-country-field', [
                                        'name' => 'passengers['.$pIndex.'][nationality]',
                                        'label' => 'Nationality',
                                        'required' => true,
                                    ])
                                    @include('user.flights.partials.hp-country-field', [
                                        'name' => 'passengers['.$pIndex.'][issuing_country]',
                                        'label' => 'Issuing Country',
                                        'required' => true,
                                    ])
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        {{-- Contact (airline + e-ticket) - names come from Adult 1 above --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bx-phone hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__title" style="margin-top:0;">Contact Details</div>
                                </div>
                            </div>

                            <p class="hp-contact-note">
                                <i class="bx bx-info-circle"></i>
                                These details will be passed to the Airline for booking.
                            </p>

                            <div class="row g-3 hp-pax-fields">
                                <div class="col-md-6">
                                    <label class="hp-label">Phone <span class="hp-req">*</span></label>
                                    <input type="tel" class="hp-input" name="lead[phone]"
                                        placeholder="+971 50 000 0000" required autocomplete="tel">
                                </div>
                                <div class="col-md-6">
                                    <label class="hp-label">Email <span class="hp-req">*</span></label>
                                    <input type="email" class="hp-input" name="lead[email]"
                                        placeholder="email@example.com" required autocomplete="email">
                                </div>
                            </div>
                        </div>

                        @include('user.flights.partials.fare-rules-box', [
                            'itineraryId' => $itineraryId,
                            'selectedFareIndex' => $selectedFareIndex ?? 0,
                            'itinerary' => $itinerary,
                            'searchParams' => $searchParams ?? [],
                        ])

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

                            <input type="hidden" name="payment_method" id="payment-method-input" value="payby">

                            <div class="hcf-payment-remaining" id="remaining-payment-section">
                                <div class="hcf-payment-remaining__title" id="remaining-payment-title">Select Payment Method</div>
                                <div class="hcf-payment-options">
                                    <label class="hcf-payment-option">
                                        <input type="radio" class="js-payment-method-option" value="payby" checked>
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
                                        <input type="radio" class="js-payment-method-option" value="tabby">
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
                                        <input type="radio" class="js-payment-method-option" value="tamara">
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
                                @include('user.flights.partials.fare-summary-breakdown', [
                                    'itinerary' => $itinerary,
                                    'breakdown' => $fareBreakdown,
                                    'fallbackTotal' => $totalAmount,
                                    'adults' => $adults,
                                    'children' => $children,
                                    'infants' => $infants,
                                ])
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
                                <span id="summary-total-label">Total Amount</span>
                                <span class="hp-summary__total-amount">
                                    <span class="dirham">{{ $currency }}</span><span id="summary-net-total">{{ number_format($totalAmount, 2) }}</span>
                                </span>
                            </div>
                            @if ($fareBreakdown['show_net_fare'] ?? false)
                                <div class="hp-summary__net">
                                    Net Fare:
                                    <span class="dirham">{{ $currency }}</span> {{ number_format($fareBreakdown['net_fare'], 2) }}
                                </div>
                            @endif

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
        .hp-pax-note {
            margin: 0; padding: .5rem 1.1rem;
            font-size: .74rem; color: var(--c-amber);
            background: var(--c-amber-soft);
            border-bottom: 1px solid rgba(217,119,6,.15);
            display: flex; align-items: center; gap: .35rem;
        }
        .hp-pax-note i { font-size: .88rem; flex-shrink: 0; }
        .hp-saved-row { padding: .7rem 1.1rem .2rem; }
        .hp-hint { font-size: .62rem; color: var(--c-muted); margin-top: .18rem; display: block; }
        .hp-save-check {
            display: flex; align-items: center; gap: .5rem;
            font-size: .76rem; color: var(--c-slate); cursor: pointer;
            padding: .45rem .7rem;
            background: var(--c-bg); border: 1px dashed var(--c-line); border-radius: 8px;
        }
        .hp-save-check input[type=checkbox] {
            accent-color: var(--c-brand); width: 15px; height: 15px; flex-shrink: 0;
        }
        .hp-contact-note {
            margin: 0; padding: .45rem 1.1rem;
            font-size: .73rem; color: var(--c-slate);
            background: var(--c-bg); border-bottom: 1px solid var(--c-line);
            display: flex; align-items: center; gap: .35rem;
        }
        .hp-contact-note i { color: var(--c-brand); }
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

        @include('user.flights.partials.hp-pax-autocomplete-styles')
        @include('user.flights.partials.fare-rules-styles')
    </style>
@endpush

@push('js')
    @include('user.flights.partials.fare-rules-scripts')
    @include('user.flights.partials.hp-pax-autocomplete-scripts')
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
                paymentMethodInput: document.getElementById('payment-method-input'),
            };

            function syncPaymentMethodInput() {
                if (!els.paymentMethodInput) return;

                const useWallet = els.useWallet && els.useWallet.checked;
                const deduction = useWallet ? Math.min(walletBalance, total) : 0;
                const walletAll = useWallet && deduction >= (total - 0.001);

                if (walletAll) {
                    els.paymentMethodInput.value = 'wallet';
                    return;
                }

                const checked = document.querySelector('.js-payment-method-option:checked');
                els.paymentMethodInput.value = checked ? checked.value : 'payby';
            }

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
                        document.querySelectorAll('.js-payment-method-option').forEach(r => {
                            r.checked = false;
                        });
                    } else {
                        els.remainingSection.style.display = 'block';
                        if (!document.querySelector('.js-payment-method-option:checked')) {
                            const first = document.querySelector('.js-payment-method-option[value="payby"]');
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

                syncPaymentMethodInput();
            }

            if (els.useWallet) {
                els.useWallet.addEventListener('change', recalc);
            }

            document.querySelectorAll('.js-payment-method-option').forEach(function(radio) {
                radio.addEventListener('change', recalc);
            });

            recalc();

            HpPaxForm.init({
                formSelector: '#flightCheckoutForm',
                savedPassengers: @json($savedPassengers),
                countries: @json($countries),
            });

            const form = document.getElementById('flightCheckoutForm');
            if (form) {
                form.addEventListener('submit', function() {
                    recalc();
                    if (els.payBtn) {
                        els.payBtn.disabled = true;
                        els.payBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing…';
                    }
                });
            }
        });
    </script>
@endpush
