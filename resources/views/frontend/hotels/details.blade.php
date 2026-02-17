@extends('frontend.layouts.main')
@section('content')
    <div class="py-2">
        <div class="container">
            <nav class="breadcrumb-nav">
                <ul class="breadcrumb-list">

                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.index') }}" class="breadcrumb-link">Home</a>
                        <i class='bx bx-chevron-right breadcrumb-separator'></i>
                    </li>

                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.hotels.index') }}" class="breadcrumb-link">Hotels</a>
                        <i class='bx bx-chevron-right breadcrumb-separator'></i>
                    </li>

                    <li class="breadcrumb-item">
                        <a href="{!! route('frontend.hotels.search') . '?' . http_build_query(request()->query()) !!}" class="breadcrumb-link">Listing</a>
                        <i class='bx bx-chevron-right breadcrumb-separator'></i>
                    </li>

                    <li class="breadcrumb-item active">
                        {{ $hotel['name'] }}
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center mt-2 mb-4">
            <div class="col-md-10">
                <div class="main-page-search">
                    @include('frontend.vue.main', [
                        'appId' => 'hotels-search',
                        'appComponent' => 'hotels-search',
                        'appJs' => 'hotels-search',
                    ])
                </div>
            </div>
        </div>

        <div class="hotel-detail">
            <div class="hotel-info">
                <div class="row">
                    <div class="col-md-8">
                        <div class="hotels-lg-img-wrapper">
                            <div class="hotels-lg-img-list">
                                @foreach ($hotel['images'] as $img)
                                    <div class="hotels-lg-img-item">
                                        <img data-src="{{ $img['Url'] ?? asset('frontend/images/placeholder.png') }}"
                                            class="imgFluid lazyload" alt="Image" />
                                    </div>
                                @endforeach
                            </div>

                            <div class="action-btns">
                                <div class="event-slider-actions">
                                    <button type="button" class="event-slider-actions__arrow event-slider-prev">
                                        <i class="bx bx-chevron-left"></i>
                                    </button>
                                    <div class="event-slider-actions__progress"></div>
                                    <button type="button" class="event-slider-actions__arrow event-slider-next">
                                        <i class="bx bx-chevron-right"></i>
                                    </button>
                                </div>
                                <button class="full-screen"><i class='bx bx-fullscreen'></i>Full screen</button>
                            </div>
                        </div>

                        <div class="hotels-sm-img-list hotels-sm-img-list-slider">
                            @foreach ($hotel['images'] as $img)
                                <div class="hotels-sm-img-item">
                                    <img data-src="{{ $img['Url'] ?? asset('frontend/images/placeholder.png') }}"
                                        class="imgFluid lazyload" alt="Image" />
                                </div>
                            @endforeach
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="event-card event-card--details">
                            <div class="event-card__content">
                                <div class="title">{{ $hotel['name'] }}</div>
                                <div class="details">
                                    <div class="icon"><i class="bx bx-map"></i></div>
                                    <div class="content">{{ $hotel['address'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="event-card event-card--details">
                            <div class="event-card__content">
                                <span class="subtitle">Price from</span>
                                <div class="price">{{ formatPrice($hotel['price']) }}
                                </div>
                                <span class="subtitle d-block mb-2">(Per person)</span>

                                {{-- Dates and nights --}}
                                @php
                                    $checkIn = request()->get('check_in');
                                    $checkOut = request()->get('check_out');

                                    if ($checkIn && $checkOut) {
                                        $startDate = \Carbon\Carbon::parse(urldecode($checkIn));
                                        $endDate = \Carbon\Carbon::parse(urldecode($checkOut));
                                        $nights = $startDate->diffInDays($endDate);
                                        $nights = $nights ?: 1; // at least 1 night if same-day
                                    }
                                @endphp

                                <div class="details">
                                    <div class="icon"><i class="bx bxs-moon"></i></div>
                                    <div class="content">
                                        {{ $startDate->format('d M Y') ?? '' }} - {{ $endDate->format('d M Y') ?? '' }} |
                                        {{ $nights ?? 0 }} nights at hotel
                                    </div>
                                </div>

                                {{-- Room details --}}
                                @php
                                    $roomsRequest = $rooms_request ?? [];

                                    $roomCount = count($roomsRequest) ?: 1;
                                    $adults = collect($roomsRequest)->sum('Adults') ?: 1;

                                    // Count all children by summing the length of ChildAges arrays
                                    $children = collect($roomsRequest)
                                        ->flatMap(fn($room) => $room['ChildAges'] ?? [])
                                        ->count();
                                @endphp

                                <div class="details">
                                    <div class="icon"><i class='bx bxs-group'></i></div>
                                    <div class="content">
                                        {{ $adults }} Adults, {{ $children }}
                                        Child{{ $children > 1 ? 'ren' : '' }}, {{ $roomCount }}
                                        Room{{ $roomCount > 1 ? 's' : '' }}
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="event-card">
                            <div class="event-card__content">
                                <div class="hotel-detail__reviews m-0 p-0">
                                    <div class="review-header mb-0">
                                        <div class="rating">{{ number_format($hotel['rating'], 1) }}</div>
                                        <div class="details">
                                            <div class="client-name">{{ $hotel['rating_text'] }}</div>
                                            <div class="checkin-time">Based on customer reviews</div>
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bx bxs-star"
                                                    style="color: {{ $i <= $hotel['rating'] ? '#f2ac06' : '#ccc' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="container">
        <div class="hotel-detail__tabs">
            <ul class="nav nav-pills details-tabs" id="pills-tab" role="tablist">
                <li role="presentation">
                    <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home"
                        type="button" role="tab" aria-controls="pills-home" aria-selected="true">Overview</button>
                </li>
                <li role="presentation">
                    <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile"
                        type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Rooms</button>
                </li>
                <li role="presentation">
                    <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-contact" type="button" role="tab" aria-controls="pills-contact"
                        aria-selected="false">Information
                        Items</button>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"
                    tabindex="0">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="hotel-detail-box text-document">
                                <h3 class="heading mt-0">Hotel Information</h3>
                                {!! $hotel['description'] !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="hotel-map">
                                <iframe src="https://maps.google.com/maps?q={{ $hotel['address'] }}&output=embed"
                                    width="100%" height="490" frameborder="0" style="border:0;"
                                    allowfullscreen=""></iframe>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab"
                    tabindex="0">

                    <div class="row g-3 g-lg-4">
                        @foreach ($api_availability[0]['Rooms'] as $roomIndex => $room)
                            @foreach (collect($room['Boards'])->unique('Code') as $boardIndex => $board)
                                @php
                                    $finalPrice = yalagoFinalPrice($board, $hotelCommissionPercentage);
                                    $finalPriceFormatted = formatPrice($finalPrice);

                                    // Refundable / Non-refundable
                                    $isRefundable = empty($board['NonRefundable']) ? true : false;
                                    $boardTitle = $board['Description'] ?? '';
                                @endphp


                                <div class="col-12 col-lg-4">
                                    <div class="room-card" data-room-code="{{ $room['Code'] }}"
                                        data-board-code="{{ $board['Code'] }}" data-price="{{ $finalPrice }}"
                                        data-board-title="{{ $boardTitle }}"
                                        data-room-name="{{ $room['Description'] }}">

                                        <div class="room-card__box">
                                            <!-- Header -->
                                            <div class="room-card__header">
                                                <h3 class="room-card__title">
                                                    {{ $room['Description'] }}
                                                </h3>
                                            </div>

                                            <!-- Tags -->
                                            <div class="room-card__tags">
                                                <span class="room-card__tag">
                                                    <i class='bx bx-home'></i> {{ $boardTitle }}
                                                </span>

                                                @if ($board['NonRefundable'])
                                                    <span class="room-card__tag room-card__tag--red">
                                                        <i class='bx bx-x-circle'></i> Non-Refundable
                                                    </span>
                                                @else
                                                    <span class="room-card__tag room-card__tag--green">
                                                        <i class='bx bx-check-shield'></i> Refundable
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Policy -->
                                            <div class="room-card__policy">
                                                @foreach ($board['CancellationPolicy']['CancellationCharges'] ?? [] as $policy)
                                                    @php
                                                        $expiry = \Carbon\Carbon::parse(
                                                            $policy['ExpiryDateUTC'],
                                                        )->format('d M Y');
                                                        $amount = $policy['Charge']['Amount'] ?? 0;
                                                        $isFree = $amount == 0;
                                                    @endphp

                                                    @if ($isFree)
                                                        <div class="room-card__policy-row room-card__policy-row--free">
                                                            <div class="room-card__policy-content">
                                                                <i class="bx bxs-check-circle room-card__icon"></i>
                                                                <span>
                                                                    Free cancellation until
                                                                    <strong>{{ $expiry }}</strong>
                                                                </span>
                                                            </div>
                                                            <span
                                                                class="room-card__badge room-card__badge--free">FREE</span>
                                                        </div>
                                                    @else
                                                        <div class="room-card__policy-row room-card__policy-row--fee">
                                                            <div class="room-card__policy-content">
                                                                <i class="bx bxs-info-circle room-card__icon"></i>
                                                                <span>
                                                                    Cancellation after <strong>{{ $expiry }}</strong>
                                                                </span>
                                                            </div>
                                                            <span class="room-card__price-text">
                                                                {{ formatPrice($amount) }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <!-- Footer -->
                                            <div class="room-card__footer">
                                                <div class="room-card__price-info">
                                                    <span class="room-card__label">Price per room</span>
                                                    <span class="room-card__total">
                                                        {{ $finalPriceFormatted }}
                                                    </span>
                                                </div>

                                                <div class="qty-control">
                                                    <button onclick="decrementRoom(this)" class="qty-btn" type="button">
                                                        <i class="bx bx-minus"></i>
                                                    </button>
                                                    <input type="number" class="counter-input qty-input room-qty-input"
                                                        value="0" readonly min="0"
                                                        max="{{ $roomCount }}">
                                                    <button onclick="incrementRoom(this)" class="qty-btn" type="button">
                                                        <i class="bx bx-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>



                </div>
                <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"
                    tabindex="0">
                    <div class="hotel-detail-box editorial-section">
                        <div class="notice-wrapper">
                            @foreach ($info_items as $index => $item)
                                <div class="notice-item">
                                    <div class="notice-number">
                                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="notice-text">
                                        <p>{!! $item['Description'] !!}</p>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="continue-bar">
        <div class="container">
            <div class="continue-bar-padding">
                <div class="row align-items-center justify-content-center">
                    <div class="col-12 col-md-6">
                        <div class="details-wrapper">
                            <div class="details">
                                <div class="total">Total</div>
                                <div><span class="dirham">D</span><span class="total-price"
                                        id="total-room-price">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="details-btn-wrapper">
                            <a id="continueBtn" href="{!! route('frontend.hotels.checkout', $hotel['id']) . '?' . http_build_query(request()->query()) !!}" class="btn-primary-custom">
                                Continue
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        const priceEl = document.getElementById('total-room-price');
        const roomCards = document.querySelectorAll('.room-card');
        const continueBtn = document.getElementById('continueBtn');
        const maxRooms = {{ $roomCount }};
        const baseUrl = "{!! route('frontend.hotels.checkout', $hotel['id']) . '?' . http_build_query(request()->query()) !!}";
        const showExtras = @json($show_extras);

        const formatPrice = (value) =>
            Number(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        function getTotalRoomsSelected() {
            let total = 0;
            roomCards.forEach(card => {
                const input = card.querySelector('.room-qty-input');
                total += parseInt(input.value) || 0;
            });
            return total;
        }

        function updateTotalPrice() {
            let total = 0;
            roomCards.forEach(card => {
                const quantity = parseInt(card.querySelector('.room-qty-input').value) || 0;
                const price = parseFloat(card.dataset.price);
                total += quantity * price;
            });
            priceEl.textContent = formatPrice(total);
        }

        function updateContinueUrl() {
            const params = new URLSearchParams({
                show_extras: showExtras ? true : false
            });

            let roomIndex = 1;

            roomCards.forEach(card => {
                const quantity = parseInt(card.querySelector('.room-qty-input').value) || 0;

                if (quantity > 0) {
                    for (let i = 0; i < quantity; i++) {
                        params.append(`room_${roomIndex}_code`, card.dataset.roomCode);
                        params.append(`room_${roomIndex}_board_code`, card.dataset.boardCode);
                        params.append(`room_${roomIndex}_board_title`, card.dataset.boardTitle);
                        params.append(`room_${roomIndex}_price`, card.dataset.price);
                        params.append(`room_${roomIndex}_name`, card.dataset.roomName);
                        roomIndex++;
                    }
                }
            });

            params.append('selected_rooms', getTotalRoomsSelected());

            const separator = baseUrl.includes('?') ? '&' : '?';
            continueBtn.href = baseUrl + separator + params.toString();
        }

        function updateRoomCardState(card) {
            const input = card.querySelector('.room-qty-input');
            const quantity = parseInt(input.value) || 0;

            if (quantity > 0) {
                card.classList.add('room-card--selected');
            } else {
                card.classList.remove('room-card--selected');
            }
        }

        function incrementRoom(button) {
            const card = button.closest('.room-card');
            const input = card.querySelector('.room-qty-input');
            const currentTotal = getTotalRoomsSelected();
            const currentValue = parseInt(input.value) || 0;
            const max = parseInt(input.max);

            if (currentTotal >= maxRooms) {
                showMessage(`You can only select ${maxRooms} room(s) in total.`, "error");
                return;
            }

            if (currentValue < max) {
                input.value = currentValue + 1;
                updateRoomCardState(card);
                updateTotalPrice();
                updateContinueUrl();
            }
        }

        function decrementRoom(button) {
            const card = button.closest('.room-card');
            const input = card.querySelector('.room-qty-input');
            const currentValue = parseInt(input.value) || 0;
            const min = parseInt(input.min);

            if (currentValue > min) {
                input.value = currentValue - 1;
                updateRoomCardState(card);
                updateTotalPrice();
                updateContinueUrl();
            }
        }

        continueBtn.addEventListener('click', (e) => {
            const totalCount = getTotalRoomsSelected();

            if (totalCount !== maxRooms) {
                e.preventDefault();
                showMessage(
                    `Please select exactly ${maxRooms} room(s) before continuing.`,
                    "error"
                );

                const roomsTab = document.getElementById('pills-profile-tab');
                roomsTab?.click();

                setTimeout(() => {
                    document
                        .querySelector('.hotel-detail__tabs')
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                }, 100);
            }
        });
    </script>
@endpush
