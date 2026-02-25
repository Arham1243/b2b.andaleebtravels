@extends('user.layouts.main')
@section('content')
    @php
        $nights = max(1, \Carbon\Carbon::parse($check_in)->diffInDays(\Carbon\Carbon::parse($check_out)));
        $adultCount = collect($rooms_request)->sum('Adults');
        $childrenCount = collect($rooms_request)->sum(fn($r) => count($r['ChildAges']));
        $groupedRooms = collect($selected_rooms)->groupBy(fn($room) => $room['room_name'] . '|' . $room['board_title']);
        $walletBalance = Auth::user()->main_balance ?? 0;
    @endphp

    {{-- Price Update Modal --}}
    @if ($price_changed)
        <div class="hc-modal-overlay active" id="price-update-modal">
            <div class="hc-modal">
                <div class="hc-modal__icon"><i class="bx bx-info-circle"></i></div>
                <h3 class="hc-modal__title">Price Updated</h3>
                <p class="hc-modal__text">The total price has changed to <strong>{{ formatPrice($total_price) }}</strong>. Would you like to continue?</p>
                <div class="hc-modal__actions">
                    <a href="{{ route('user.hotels.index') }}" class="hc-modal__btn hc-modal__btn--ghost">Cancel</a>
                    <button onclick="document.getElementById('price-update-modal').classList.remove('active')" class="hc-modal__btn">Continue</button>
                </div>
            </div>
        </div>
    @endif

    <div class="hc-page">
        <div class="container">
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.hotels.index') }}">Hotels</a>
                <i class="bx bx-chevron-right"></i>
                <span>Checkout</span>
            </nav>

            <form id="checkoutForm" action="{{ route('user.hotels.payment.process') }}" method="POST">
                @csrf
                <input type="hidden" name="hotel_id" value="{{ $hotel['yalago_id'] }}">
                <input type="hidden" name="check_in" value="{{ $check_in }}">
                <input type="hidden" name="check_out" value="{{ $check_out }}">
                @foreach ($selected_rooms as $index => $room)
                    <input type="hidden" name="selected_rooms[{{ $index }}][room_code]" value="{{ $room['room_code'] }}">
                    <input type="hidden" name="selected_rooms[{{ $index }}][board_code]" value="{{ $room['board_code'] }}">
                    <input type="hidden" name="selected_rooms[{{ $index }}][board_title]" value="{{ $room['board_title'] }}">
                    <input type="hidden" name="selected_rooms[{{ $index }}][price]" value="{{ $room['price'] }}">
                    <input type="hidden" name="selected_rooms[{{ $index }}][room_name]" value="{{ $room['room_name'] }}">
                @endforeach
                @foreach ($rooms_request as $index => $room)
                    <input type="hidden" name="rooms[{{ $index }}][adults]" value="{{ $room['Adults'] }}">
                    <input type="hidden" name="rooms[{{ $index }}][child_ages]" value="{{ implode(',', $room['ChildAges']) }}">
                @endforeach

                <div class="row">
                    {{-- LEFT COLUMN --}}
                    <div class="col-lg-8">

                        {{-- STEP 1: EXTRAS (Maldives) --}}
                        <div id="extras-section" class="{{ $show_extras ? '' : 'd-none' }}">
                            @if ($show_extras && $yalago_extras->isNotEmpty())
                                <div id="selected-extras-hidden-fields"></div>

                                @php $extrasByRoom = $yalago_extras->groupBy('room_index'); @endphp

                                @foreach ($selected_rooms as $roomIndex => $selectedRoom)
                                    @php
                                        $roomExtras = $extrasByRoom->get($roomIndex + 1, collect());
                                        $groupedExtras = $roomExtras->groupBy(fn($item) => $item['extra']['Title']);
                                    @endphp

                                    @if ($groupedExtras->isNotEmpty())
                                        <div class="hc-card mb-3">
                                            <div class="hc-card__header">
                                                <i class="bx bxs-bed"></i>
                                                <div>
                                                    <div class="hc-card__title">Room {{ $roomIndex + 1 }}: {{ $selectedRoom['room_name'] }}</div>
                                                    <div class="hc-card__subtitle">{{ $selectedRoom['board_title'] }}</div>
                                                </div>
                                            </div>

                                            @php $counter = 0; @endphp

                                            @foreach ($groupedExtras as $extraTitle => $group)
                                                @php
                                                    $first = $group->first();
                                                    $extra = $first['extra'];
                                                    if (empty($extra['IsMandatory'])) continue;
                                                    $groupName = 'room_' . ($roomIndex + 1) . '_extra_' . $extra['ExtraId'];
                                                @endphp

                                                <div class="hc-extras-group">
                                                    <div class="hc-extras-group__title">
                                                        <i class="bx bx-transfer-alt"></i> {{ $extraTitle }}
                                                        <span class="hc-extras-group__badge">Required</span>
                                                    </div>
                                                    <div class="row g-3">
                                                        @foreach ($group as $item)
                                                            @foreach ($item['extra']['Options'] as $option)
                                                                @php
                                                                    $counter++;
                                                                    $price = !empty($extra['IsBindingPrice']) ? $option['GrossCost']['Amount'] : $option['NetCost']['Amount'];
                                                                    $uniqueId = 'room_' . ($roomIndex + 1) . '_transfer_' . $counter;
                                                                @endphp
                                                                <div class="col-md-6">
                                                                    <label class="hc-transfer-option" for="{{ $uniqueId }}">
                                                                        <input class="hc-transfer-option__radio" type="radio"
                                                                            id="{{ $uniqueId }}" name="{{ $groupName }}"
                                                                            value="{{ $option['Title'] }}"
                                                                            data-room-index="{{ $roomIndex + 1 }}"
                                                                            data-room-name="{{ $selectedRoom['room_name'] }}"
                                                                            data-extra-title="{{ $extra['Title'] }}"
                                                                            data-price="{{ $price }}"
                                                                            data-option-id="{{ $option['OptionId'] }}"
                                                                            data-extra-id="{{ $extra['ExtraId'] }}"
                                                                            data-extra-type-id="{{ $extra['ExtraTypeId'] }}"
                                                                            {{ $extra['IsMandatory'] ? 'required data-required=true' : '' }}>
                                                                        <div class="hc-transfer-option__body">
                                                                            <div class="hc-transfer-option__check"><i class="bx bxs-check-circle"></i></div>
                                                                            <div class="hc-transfer-option__info">
                                                                                <div class="hc-transfer-option__name">{{ $option['Title'] }}</div>
                                                                                <div class="hc-transfer-option__price">{{ formatPrice($price) }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach

                                {{-- Flight Details --}}
                                <div class="hc-card mb-3">
                                    <div class="hc-card__header">
                                        <i class="bx bxs-plane-alt"></i>
                                        <div class="hc-card__title">Flight Details</div>
                                    </div>
                                    <div class="hc-alert mb-3">
                                        <i class="bx bx-info-circle"></i>
                                        <span>Flight details are required to arrange transfers.</span>
                                    </div>

                                    <div class="hc-flight-section">
                                        <div class="hc-flight-section__label">Outbound Flight</div>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="hc-label">Flight number *</label>
                                                <input type="text" class="hc-input" name="flight_details[outbound][flight_number]">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="hc-label">Arrival hour *</label>
                                                <select class="hc-select" name="flight_details[outbound][arrival_hour]">
                                                    <option value="">hh</option>
                                                    @for ($i = 0; $i < 24; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="hc-label">Arrival min *</label>
                                                <select class="hc-select" name="flight_details[outbound][arrival_minute]">
                                                    <option value="">mm</option>
                                                    @foreach (['00','05','10','15','20','25','30','35','40','45','50','55'] as $m)
                                                        <option value="{{ $m }}">{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hc-flight-section">
                                        <div class="hc-flight-section__label">Inbound Flight</div>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="hc-label">Flight number *</label>
                                                <input type="text" class="hc-input" name="flight_details[inbound][flight_number]">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="hc-label">Departure hour *</label>
                                                <select class="hc-select" name="flight_details[inbound][departure_hour]">
                                                    <option value="">hh</option>
                                                    @for ($i = 0; $i < 24; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="hc-label">Departure min *</label>
                                                <select class="hc-select" name="flight_details[inbound][departure_minute]">
                                                    <option value="">mm</option>
                                                    @foreach (['00','05','10','15','20','25','30','35','40','45','50','55'] as $m)
                                                        <option value="{{ $m }}">{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="hc-extras-continue">
                                    <button type="button" id="extras-continue-btn" class="hc-btn hc-btn--primary hc-btn--full">
                                        Continue to Guest Details <i class="bx bx-right-arrow-alt"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- STEP 2: GUEST INFO + PAYMENT --}}
                        <div id="guest-section" class="{{ $show_extras ? 'd-none' : '' }}">
                            {{-- Lead Guest --}}
                            <div class="hc-card mb-3">
                                <div class="hc-card__header">
                                    <i class="bx bx-user"></i>
                                    <div class="hc-card__title">Lead Guest Information</div>
                                </div>
                                <div class="hc-alert mb-3">
                                    <i class="bx bx-info-circle"></i>
                                    <span>All names must exactly match passports.</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="hc-label">Title</label>
                                        <select class="hc-select" name="booking[lead_guest][title]" required>
                                            <option value="Mr">Mr.</option>
                                            <option value="Mrs">Mrs.</option>
                                            <option value="Ms">Ms.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">First Name *</label>
                                        <input type="text" class="hc-input" name="booking[lead_guest][first_name]" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hc-label">Last Name *</label>
                                        <input type="text" class="hc-input" name="booking[lead_guest][last_name]" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="hc-label">Email *</label>
                                        <input type="email" class="hc-input" name="booking[lead_guest][email]" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="hc-label">Phone *</label>
                                        <input type="tel" class="hc-input" name="booking[lead_guest][phone]" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="hc-label">Address *</label>
                                        <input type="text" class="hc-input" name="booking[lead_guest][address]" required>
                                    </div>
                                </div>
                            </div>

                            {{-- Guest Details --}}
                            @for ($i = 0; $i < $adultCount; $i++)
                                <div class="hc-card mb-3">
                                    <div class="hc-card__header">
                                        <i class="bx bx-user"></i>
                                        <div class="hc-card__title">Guest {{ $i + 1 }}</div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <label class="hc-label">Title</label>
                                            <select class="hc-select" name="booking[guests][{{ $i }}][title]" required>
                                                <option value="Mr">Mr.</option>
                                                <option value="Mrs">Mrs.</option>
                                                <option value="Ms">Ms.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="hc-label">First Name *</label>
                                            <input type="text" class="hc-input" name="booking[guests][{{ $i }}][first_name]" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="hc-label">Last Name *</label>
                                            <input type="text" class="hc-input" name="booking[guests][{{ $i }}][last_name]" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="hc-label">Age *</label>
                                            <input type="number" min="1" class="hc-input" name="booking[guests][{{ $i }}][age]" required>
                                        </div>
                                    </div>
                                </div>
                            @endfor

                            {{-- Payment Methods --}}
                            <div class="hc-card mb-3">
                                <div class="hc-card__header">
                                    <i class="bx bx-credit-card"></i>
                                    <div class="hc-card__title">Payment Method</div>
                                </div>

                                {{-- Wallet Toggle --}}
                                @if ($walletBalance > 0)
                                <div class="hc-wallet-toggle" id="wallet-toggle-section">
                                    <label class="hc-wallet-toggle__label">
                                        <input type="checkbox" id="use-wallet" name="use_wallet" value="1">
                                        <div class="hc-wallet-toggle__body">
                                            <div class="hc-wallet-toggle__left">
                                                <div class="hc-payment-option__icon"><i class="bx bxs-wallet"></i></div>
                                                <div class="hc-payment-option__info">
                                                    <div class="hc-payment-option__name">Use Wallet Balance</div>
                                                    <div class="hc-payment-option__desc">Available: <strong>{!! formatPrice($walletBalance) !!}</strong></div>
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

                                {{-- Remaining Payment Method --}}
                                <div class="hc-payment-remaining" id="remaining-payment-section">
                                    <div class="hc-payment-remaining__title" id="remaining-payment-title">Select Payment Method</div>
                                    <div class="hc-payment-options">
                                        {{-- Card --}}
                                        <label class="hc-payment-option">
                                            <input type="radio" name="payment_method" value="payby" checked required>
                                            <div class="hc-payment-option__body">
                                                <div class="hc-payment-option__icon"><i class="bx bxs-credit-card"></i></div>
                                                <div class="hc-payment-option__info">
                                                    <div class="hc-payment-option__name">Credit / Debit Card</div>
                                                    <div class="hc-payment-option__desc">Redirected to secure payment gateway</div>
                                                </div>
                                                <div class="hc-payment-option__check"><i class="bx bxs-check-circle"></i></div>
                                            </div>
                                        </label>

                                        {{-- Tabby --}}
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
                                    </div>
                                </div>

                                <button type="submit" class="hc-btn hc-btn--primary hc-btn--full mt-3" id="pay-btn">
                                    <i class="bx bx-lock-alt"></i> Pay Now
                                </button>
                                <div class="hc-secure-note">
                                    <i class="bx bx-check-shield"></i> Secure Checkout
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT COLUMN: SUMMARY --}}
                    <div class="col-lg-4">
                        <div class="hc-summary">
                            {{-- Hotel Info --}}
                            <div class="hc-summary__hotel">
                                <img src="{{ $hotel['image'] ?? asset('user/assets/images/placeholder.png') }}" alt="{{ $hotel['name'] }}" class="hc-summary__img">
                                <div class="hc-summary__hotel-info">
                                    <div class="hc-summary__hotel-name">{{ $hotel['name'] }}</div>
                                    @if (!empty($hotel['rating']))
                                        <div class="hc-summary__hotel-rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bx bxs-star" style="color: {{ $i <= $hotel['rating'] ? '#f2ac06' : '#ddd' }}; font-size: 12px;"></i>
                                            @endfor
                                            <span>{{ number_format($hotel['rating'], 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Trip Details --}}
                            <div class="hc-summary__details">
                                <div class="hc-summary__detail">
                                    <i class="bx bxs-calendar"></i>
                                    <span>{{ \Carbon\Carbon::parse($check_in)->format('d M') }} &mdash; {{ \Carbon\Carbon::parse($check_out)->format('d M Y') }} &middot; {{ $nights }} night{{ $nights > 1 ? 's' : '' }}</span>
                                </div>
                                <div class="hc-summary__detail">
                                    <i class="bx bx-map"></i>
                                    <span>{{ $hotel['address'] ?? '' }}</span>
                                </div>
                                <div class="hc-summary__detail">
                                    <i class="bx bxs-group"></i>
                                    <span>{{ $adultCount }} Adult{{ $adultCount > 1 ? 's' : '' }}{{ $childrenCount > 0 ? ', ' . $childrenCount . ' Child' . ($childrenCount > 1 ? 'ren' : '') : '' }}, {{ count($rooms_request) }} Room{{ count($rooms_request) > 1 ? 's' : '' }}</span>
                                </div>
                            </div>

                            {{-- Room Breakdown --}}
                            <div class="hc-summary__rooms">
                                @foreach ($groupedRooms as $group)
                                    @php
                                        $room = $group->first();
                                        $qty = $group->count();
                                        $roomTotal = $room['price'] * $qty;
                                    @endphp
                                    <div class="hc-summary__room-row">
                                        <div>
                                            <div class="hc-summary__room-name">{{ $qty }}x {{ $room['room_name'] }}</div>
                                            <div class="hc-summary__room-board">{{ $room['board_title'] }}</div>
                                        </div>
                                        <div class="hc-summary__room-price">{{ formatPrice($roomTotal) }}</div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Extras Summary --}}
                            @if ($show_extras)
                                <div class="hc-summary__extras">
                                    <div class="hc-summary__extras-title">Transfers / Extras</div>
                                    <div id="selected-extras-list">
                                        <span class="hc-summary__muted">No extras selected</span>
                                    </div>
                                    <div class="hc-summary__line">
                                        <span>Extras Total</span>
                                        <span><span class="dirham">D</span> <span id="extras-total-amount">0.00</span></span>
                                    </div>
                                </div>
                            @endif

                            {{-- Wallet Applied in Summary --}}
                            <div class="hc-summary__wallet" id="summary-wallet-section" style="display: none;">
                                <div class="hc-summary__line">
                                    <span>Subtotal</span>
                                    <span><span class="dirham">D</span> <span id="summary-subtotal">{{ formatPrice($total_price) }}</span></span>
                                </div>
                                <div class="hc-summary__line hc-summary__line--wallet">
                                    <span><i class="bx bxs-wallet" style="font-size: 0.9rem; vertical-align: middle;"></i> Wallet Applied</span>
                                    <span class="hc-summary__wallet-amount">- <span class="dirham">D</span> <span id="summary-wallet-amount">0.00</span></span>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="hc-summary__total">
                                <span id="summary-total-label">Total Price</span>
                                <span class="hc-summary__total-price">
                                    <span class="dirham">D</span> <span id="summary-net-total">{{ formatPrice($total_price) }}</span>
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
            const roomsTotal = @json($total_price);
            const walletBalance = @json($walletBalance);
            const formatPrice = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const els = {
                extrasTotal: document.getElementById('extras-total-amount'),
                summaryNet: document.getElementById('summary-net-total'),
                extrasList: document.getElementById('selected-extras-list'),
                summarySubtotal: document.getElementById('summary-subtotal'),
                summaryWalletAmount: document.getElementById('summary-wallet-amount'),
                summaryWalletSection: document.getElementById('summary-wallet-section'),
                summaryTotalLabel: document.getElementById('summary-total-label'),
                walletDeductAmount: document.getElementById('wallet-deduct-amount'),
                remainingAmount: document.getElementById('remaining-amount'),
                walletAppliedInfo: document.getElementById('wallet-applied-info'),
                walletAmountInput: document.getElementById('wallet-amount-input'),
                remainingPaymentSection: document.getElementById('remaining-payment-section'),
                remainingPaymentTitle: document.getElementById('remaining-payment-title'),
                useWallet: document.getElementById('use-wallet')
            };

            const extrasHidden = document.getElementById('selected-extras-hidden-fields');
            let currentExtrasTotal = 0;

            function getNetTotal() {
                return roomsTotal + currentExtrasTotal;
            }

            function recalcWallet() {
                const netTotal = getNetTotal();
                const useWallet = els.useWallet && els.useWallet.checked;
                const walletDeduction = useWallet ? Math.min(walletBalance, netTotal) : 0;
                const remaining = netTotal - walletDeduction;
                const walletCoversAll = walletDeduction >= netTotal;

                // Update hidden input
                if (els.walletAmountInput) els.walletAmountInput.value = walletDeduction.toFixed(2);

                // Update wallet applied info
                if (els.walletAppliedInfo) els.walletAppliedInfo.style.display = useWallet ? 'block' : 'none';
                if (els.walletDeductAmount) els.walletDeductAmount.textContent = formatPrice(walletDeduction);
                if (els.remainingAmount) els.remainingAmount.textContent = formatPrice(remaining);

                // Update summary sidebar
                if (els.summaryWalletSection) els.summaryWalletSection.style.display = useWallet ? 'block' : 'none';
                if (els.summarySubtotal) els.summarySubtotal.textContent = formatPrice(netTotal);
                if (els.summaryWalletAmount) els.summaryWalletAmount.textContent = formatPrice(walletDeduction);
                if (els.summaryNet) els.summaryNet.textContent = formatPrice(remaining > 0 ? remaining : 0);
                if (els.summaryTotalLabel) els.summaryTotalLabel.textContent = useWallet ? 'Amount to Pay' : 'Total Price';

                // Show/hide remaining payment methods
                if (els.remainingPaymentSection) {
                    if (walletCoversAll && useWallet) {
                        els.remainingPaymentSection.style.display = 'none';
                        // Set payment method to wallet only
                        const radios = document.querySelectorAll('input[name="payment_method"]');
                        radios.forEach(r => { r.required = false; r.checked = false; });
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

                // Update pay button text
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

            function recalcTotals() {
                let extrasTotal = 0, idx = 0;
                const selected = [];
                if (extrasHidden) extrasHidden.innerHTML = '';

                document.querySelectorAll('.hc-transfer-option__radio:checked').forEach(radio => {
                    const price = Number(radio.dataset.price || 0);
                    extrasTotal += price;
                    const data = {
                        roomIndex: radio.dataset.roomIndex, roomName: radio.dataset.roomName,
                        extraTitle: radio.dataset.extraTitle, title: radio.value, price,
                        optionId: radio.dataset.optionId, extraId: radio.dataset.extraId,
                        extraTypeId: radio.dataset.extraTypeId
                    };
                    selected.push(data);
                    if (extrasHidden) {
                        const w = document.createElement('div');
                        w.innerHTML = `<input type="hidden" name="booking[extras][${idx}][room_index]" value="${data.roomIndex}">
                            <input type="hidden" name="booking[extras][${idx}][room_name]" value="${data.roomName}">
                            <input type="hidden" name="booking[extras][${idx}][extra_title]" value="${data.extraTitle}">
                            <input type="hidden" name="booking[extras][${idx}][title]" value="${data.title}">
                            <input type="hidden" name="booking[extras][${idx}][price]" value="${data.price}">
                            <input type="hidden" name="booking[extras][${idx}][option_id]" value="${data.optionId}">
                            <input type="hidden" name="booking[extras][${idx}][extra_id]" value="${data.extraId}">
                            <input type="hidden" name="booking[extras][${idx}][extra_type_id]" value="${data.extraTypeId}">`;
                        extrasHidden.appendChild(w);
                        idx++;
                    }
                });

                currentExtrasTotal = extrasTotal;
                const netTotal = getNetTotal();
                if (els.extrasTotal) els.extrasTotal.textContent = formatPrice(extrasTotal);

                if (els.extrasList) {
                    if (selected.length === 0) {
                        els.extrasList.innerHTML = '<span class="hc-summary__muted">No extras selected</span>';
                    } else {
                        els.extrasList.innerHTML = selected.map(e =>
                            `<div class="hc-summary__extra-item"><div><div class="hc-summary__extra-name">${e.title}</div><div class="hc-summary__extra-sub">Room ${e.roomIndex}</div></div><span>${formatPrice(e.price)}</span></div>`
                        ).join('');
                    }
                }

                recalcWallet();
            }

            // Wallet toggle
            if (els.useWallet) {
                els.useWallet.addEventListener('change', recalcWallet);
            }

            document.querySelectorAll('.hc-transfer-option__radio').forEach(r => r.addEventListener('change', recalcTotals));

            // Extras continue button
            const extrasBtn = document.getElementById('extras-continue-btn');
            const extrasSection = document.getElementById('extras-section');
            const guestSection = document.getElementById('guest-section');

            if (extrasBtn) {
                extrasBtn.addEventListener('click', function() {
                    // Validate required extras
                    const groups = new Set();
                    document.querySelectorAll('.hc-transfer-option__radio[data-required="true"]').forEach(r => groups.add(r.name));
                    for (const g of groups) {
                        if (!document.querySelector(`input[name="${g}"]:checked`)) {
                            showMessage("Please select all required transfers.", "error");
                            return;
                        }
                    }
                    // Validate flight details
                    const fields = ['flight_details[outbound][flight_number]','flight_details[outbound][arrival_hour]','flight_details[outbound][arrival_minute]','flight_details[inbound][flight_number]','flight_details[inbound][departure_hour]','flight_details[inbound][departure_minute]'];
                    for (const name of fields) {
                        const f = document.querySelector(`[name="${name}"]`);
                        if (f && !f.value) { showMessage("Please fill in all flight details.", "error"); f.focus(); return; }
                    }
                    extrasSection.classList.add('d-none');
                    guestSection.classList.remove('d-none');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            // Submit handler
            const form = document.getElementById('checkoutForm');
            const payBtn = document.getElementById('pay-btn');
            if (form && payBtn) {
                form.addEventListener('submit', function() {
                    payBtn.disabled = true;
                    payBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
                });
            }

            recalcTotals();
        });
    </script>
@endpush
