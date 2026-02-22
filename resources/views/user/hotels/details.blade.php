@extends('user.layouts.main')
@section('content')
    @php
        $checkIn = request()->get('check_in');
        $checkOut = request()->get('check_out');
        $startDate = $checkIn ? \Carbon\Carbon::parse(urldecode($checkIn)) : now();
        $endDate = $checkOut ? \Carbon\Carbon::parse(urldecode($checkOut)) : now()->addDay();
        $nights = max(1, $startDate->diffInDays($endDate));

        $roomsRequest = $rooms_request ?? [];
        $roomCount = count($roomsRequest) ?: 1;
        $adults = collect($roomsRequest)->sum('Adults') ?: 1;
        $children = collect($roomsRequest)->flatMap(fn($room) => $room['ChildAges'] ?? [])->count();
    @endphp

    <div class="hd-page">
        <div class="container">
            {{-- BREADCRUMB --}}
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.hotels.index') }}">Hotels</a>
                <i class="bx bx-chevron-right"></i>
                <a href="{!! route('user.hotels.search') . '?' . http_build_query(request()->query()) !!}">Search Results</a>
                <i class="bx bx-chevron-right"></i>
                <span>{{ $hotel['name'] }}</span>
            </nav>

            {{-- SEARCH BAR --}}
            <div class="hd-search-bar mb-4">
                @include('user.vue.main', [
                    'appId' => 'hotels-search',
                    'appComponent' => 'hotels-search',
                    'appJs' => 'hotels-search',
                ])
            </div>

            {{-- HOTEL HEADER --}}
            <div class="hd-header">
                <div class="hd-header__info">
                    <h1 class="hd-header__name">{{ $hotel['name'] }}</h1>
                    <div class="hd-header__location">
                        <i class="bx bx-map"></i> {{ $hotel['address'] }}
                    </div>
                    @if ($hotel['rating'])
                        <div class="hd-header__rating">
                            <div class="hd-header__stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bx bxs-star"
                                        style="color: {{ $i <= $hotel['rating'] ? '#f2ac06' : '#ddd' }}"></i>
                                @endfor
                            </div>
                            <span class="hd-header__rating-badge">{{ number_format($hotel['rating'], 1) }}</span>
                            <span class="hd-header__rating-text">{{ $hotel['rating_text'] }}</span>
                        </div>
                    @endif
                </div>
                <div class="hd-header__price-box">
                    <span class="hd-header__price-label">Price from</span>
                    <div class="hd-header__price">{{ formatPrice($hotel['price']) }}</div>
                </div>
            </div>

            {{-- IMAGE GALLERY (Slick) --}}
            <div class="hd-gallery">
                <div class="hd-gallery__main" id="hd-slider-main">
                    @foreach ($hotel['images'] as $img)
                        <div class="hd-gallery__slide">
                            <img src="{{ $img['Url'] ?? asset('user/assets/images/placeholder.png') }}" alt="{{ $hotel['name'] }}" />
                        </div>
                    @endforeach
                    @if (count($hotel['images']) === 0)
                        <div class="hd-gallery__slide">
                            <img src="{{ asset('user/assets/images/placeholder.png') }}" alt="{{ $hotel['name'] }}" />
                        </div>
                    @endif
                </div>
                <div class="hd-gallery__thumbs" id="hd-slider-nav">
                    @foreach ($hotel['images'] as $img)
                        <div class="hd-gallery__thumb">
                            <img src="{{ $img['Url'] ?? asset('user/assets/images/placeholder.png') }}" alt="Thumb" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- QUICK INFO STRIP --}}
            <div class="hd-quick-info">
                <div class="hd-quick-info__item">
                    <i class="bx bxs-moon"></i>
                    <span>{{ $startDate->format('d M') }} - {{ $endDate->format('d M Y') }} &middot; {{ $nights }}
                        night{{ $nights > 1 ? 's' : '' }}</span>
                </div>
                <div class="hd-quick-info__item">
                    <i class="bx bxs-group"></i>
                    <span>{{ $adults }}
                        Adult{{ $adults > 1 ? 's' : '' }}{{ $children > 0 ? ', ' . $children . ' Child' . ($children > 1 ? 'ren' : '') : '' }},
                        {{ $roomCount }} Room{{ $roomCount > 1 ? 's' : '' }}</span>
                </div>
            </div>

            {{-- TABS --}}
            <div class="hd-tabs">
                <div class="hd-tabs__nav">
                    <button class="hd-tabs__btn active" data-tab="overview">Overview</button>
                    <button class="hd-tabs__btn" data-tab="rooms">Rooms</button>
                    <button class="hd-tabs__btn" data-tab="info">Information</button>
                </div>

                {{-- OVERVIEW TAB --}}
                <div class="hd-tabs__panel active" id="tab-overview">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="hd-content-box">
                                <h3 class="hd-content-box__title">About this hotel</h3>
                                <div class="hd-content-box__text">
                                    {!! $hotel['description'] !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="hd-map">
                                <iframe
                                    src="https://maps.google.com/maps?q={{ urlencode($hotel['address']) }}&output=embed"
                                    width="100%" height="350" frameborder="0" style="border:0; border-radius: 0.75rem;"
                                    allowfullscreen=""></iframe>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ROOMS TAB --}}
                <div class="hd-tabs__panel" id="tab-rooms">
                    <div class="row g-3">
                        @foreach ($api_availability[0]['Rooms'] as $roomIndex => $room)
                            @foreach (collect($room['Boards'])->unique('Code') as $boardIndex => $board)
                                @php
                                    $finalPrice = yalagoFinalPrice($board, $hotelCommissionPercentage);
                                    $finalPriceFormatted = formatPrice($finalPrice);
                                    $isRefundable = empty($board['NonRefundable']);
                                    $boardTitle = $board['Description'] ?? '';
                                @endphp
                                <div class="col-12 col-lg-4">
                                    <div class="hd-room-card" data-room-code="{{ $room['Code'] }}"
                                        data-board-code="{{ $board['Code'] }}" data-price="{{ $finalPrice }}"
                                        data-board-title="{{ $boardTitle }}"
                                        data-room-name="{{ $room['Description'] }}">

                                        <div class="hd-room-card__header">
                                            <h4 class="hd-room-card__name">{{ $room['Description'] }}</h4>
                                        </div>

                                        <div class="hd-room-card__tags">
                                            <span class="hd-room-card__tag">
                                                <i class="bx bx-home"></i> {{ $boardTitle }}
                                            </span>
                                            @if ($board['NonRefundable'])
                                                <span class="hd-room-card__tag hd-room-card__tag--red">
                                                    <i class="bx bx-x-circle"></i> Non-Refundable
                                                </span>
                                            @else
                                                <span class="hd-room-card__tag hd-room-card__tag--green">
                                                    <i class="bx bx-check-shield"></i> Refundable
                                                </span>
                                            @endif
                                        </div>

                                        <div class="hd-room-card__policies">
                                            @foreach ($board['CancellationPolicy']['CancellationCharges'] ?? [] as $policy)
                                                @php
                                                    $expiry = \Carbon\Carbon::parse($policy['ExpiryDateUTC'])->format(
                                                        'd M Y',
                                                    );
                                                    $amount = $policy['Charge']['Amount'] ?? 0;
                                                    $isFree = $amount == 0;
                                                @endphp
                                                <div
                                                    class="hd-room-card__policy {{ $isFree ? 'hd-room-card__policy--free' : 'hd-room-card__policy--fee' }}">
                                                    <div class="hd-room-card__policy-left">
                                                        <i
                                                            class="bx {{ $isFree ? 'bxs-check-circle' : 'bxs-info-circle' }}"></i>
                                                        <span>{{ $isFree ? 'Free cancellation until' : 'Cancellation after' }}
                                                            <strong>{{ $expiry }}</strong></span>
                                                    </div>
                                                    @if ($isFree)
                                                        <span class="hd-room-card__policy-badge">FREE</span>
                                                    @else
                                                        <span
                                                            class="hd-room-card__policy-price">{{ formatPrice($amount) }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="hd-room-card__footer">
                                            <div class="hd-room-card__price-info">
                                                <span class="hd-room-card__price-label">Per room</span>
                                                <span class="hd-room-card__price">{{ $finalPriceFormatted }}</span>
                                            </div>
                                            <div class="hd-room-card__qty">
                                                <button onclick="decrementRoom(this)" class="hd-qty-btn" type="button">
                                                    <i class="bx bx-minus"></i>
                                                </button>
                                                <input type="number" class="hd-qty-input room-qty-input" value="0"
                                                    readonly min="0" max="{{ $roomCount }}">
                                                <button onclick="incrementRoom(this)" class="hd-qty-btn" type="button">
                                                    <i class="bx bx-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                {{-- INFO TAB --}}
                <div class="hd-tabs__panel" id="tab-info">
                    <div class="hd-info-list">
                        @foreach ($info_items as $index => $item)
                            <div class="hd-info-item">
                                <div class="hd-info-item__num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="hd-info-item__text">{!! $item['Description'] !!}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CONTINUE BAR --}}
    <div class="hd-continue-bar">
        <div class="container">
            <div class="hd-continue-bar__inner">
                <div class="hd-continue-bar__price">
                    <span class="hd-continue-bar__label">Total</span>
                    <span class="hd-continue-bar__amount"><span class="dirham">D</span> <span
                            id="total-room-price">0.00</span></span>
                </div>
                <a id="continueBtn" href="{!! route('user.hotels.checkout', $hotel['id']) . '?' . http_build_query(request()->query()) !!}" class="hd-continue-bar__btn">
                    Continue
                </a>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Tabs
        document.querySelectorAll('.hd-tabs__btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.hd-tabs__btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.hd-tabs__panel').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });

        // Room selection logic
        const priceEl = document.getElementById('total-room-price');
        const roomCards = document.querySelectorAll('.hd-room-card');
        const continueBtn = document.getElementById('continueBtn');
        const maxRooms = {{ $roomCount }};
        const baseUrl = "{!! route('user.hotels.checkout', $hotel['id']) . '?' . http_build_query(request()->query()) !!}";
        const showExtras = @json($show_extras);

        const formatPrice = (value) =>
            Number(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        function getTotalRoomsSelected() {
            let total = 0;
            roomCards.forEach(card => {
                total += parseInt(card.querySelector('.room-qty-input').value) || 0;
            });
            return total;
        }

        function updateTotalPrice() {
            let total = 0;
            roomCards.forEach(card => {
                const qty = parseInt(card.querySelector('.room-qty-input').value) || 0;
                total += qty * parseFloat(card.dataset.price);
            });
            priceEl.textContent = formatPrice(total);
        }

        function updateContinueUrl() {
            const params = new URLSearchParams({
                show_extras: showExtras ? true : false
            });
            let roomIndex = 1;
            roomCards.forEach(card => {
                const qty = parseInt(card.querySelector('.room-qty-input').value) || 0;
                if (qty > 0) {
                    for (let i = 0; i < qty; i++) {
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
            const qty = parseInt(card.querySelector('.room-qty-input').value) || 0;
            card.classList.toggle('hd-room-card--selected', qty > 0);
        }

        function incrementRoom(button) {
            const card = button.closest('.hd-room-card');
            const input = card.querySelector('.room-qty-input');
            const currentTotal = getTotalRoomsSelected();
            const currentValue = parseInt(input.value) || 0;

            if (currentTotal >= maxRooms) {
                showToast("error", `You can only select ${maxRooms} room(s) in total.`);
                return;
            }
            if (currentValue < parseInt(input.max)) {
                input.value = currentValue + 1;
                updateRoomCardState(card);
                updateTotalPrice();
                updateContinueUrl();
            }
        }

        function decrementRoom(button) {
            const card = button.closest('.hd-room-card');
            const input = card.querySelector('.room-qty-input');
            const currentValue = parseInt(input.value) || 0;

            if (currentValue > parseInt(input.min)) {
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
                showToast("error",`Please select exactly ${maxRooms} room(s) before continuing.`);
                document.querySelector('.hd-tabs__btn[data-tab="rooms"]')?.click();
                setTimeout(() => {
                    document.querySelector('.hd-tabs')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            }
        });
    </script>
    <script src="{{ asset('user/assets/js/slick.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#hd-slider-main').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: true,
                fade: true,
                asNavFor: '#hd-slider-nav',
                prevArrow: '<button type="button" class="hd-slick-arrow hd-slick-arrow--prev"><i class="bx bx-chevron-left"></i></button>',
                nextArrow: '<button type="button" class="hd-slick-arrow hd-slick-arrow--next"><i class="bx bx-chevron-right"></i></button>',
            });

            $('#hd-slider-nav').slick({
                slidesToShow: 5,
                slidesToScroll: 1,
                asNavFor: '#hd-slider-main',
                dots: false,
                arrows: false,
                centerMode: false,
                focusOnSelect: true,
                responsive: [
                    { breakpoint: 768, settings: { slidesToShow: 4 } },
                    { breakpoint: 480, settings: { slidesToShow: 3 } }
                ]
            });
        });
    </script>
@endpush

@push('css')
    <link rel="stylesheet" href="{{ asset('user/assets/css/slick.css') }}" />
    <link rel="stylesheet" href="{{ asset('user/assets/css/slick-theme.css') }}" />
@endpush
