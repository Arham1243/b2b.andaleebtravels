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

                    <li class="breadcrumb-item active">
                        Listing
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="hotets-listing">
        <div class="container">
            <div class="row justify-content-center mb-5 pb-2">
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
            <div class="row">
                <div class="col-md-12">
                    <div class="row justify-content-between">
                        <div class="col-md-3">
                            <div class="section-content mb-1">
                                <div class="heading">{{ $hotels->count() }} results</div>
                                @php
                                    // These are the "default" query parameters that are always in the URL
                                    $defaultParams = ['destination', 'check_in', 'check_out', 'room_count'];

                                    // All current query parameters
                                    $currentParams = request()->query();

                                    // Check if there is any query param **outside the default ones**
                                    $userFilters = collect($currentParams)
                                        ->filter(
                                            fn($value, $key) => !in_array($key, $defaultParams) &&
                                                str_starts_with($key, 'room_') === false,
                                        )
                                        ->toArray();

                                    $query = collect(request()->query())
                                        ->filter(
                                            fn($value, $key) => in_array($key, $defaultParams) ||
                                                str_starts_with($key, 'room_'),
                                        )
                                        ->toArray();
                                @endphp

                                @if (count($userFilters) > 0)
                                    <a href="{{ route('frontend.hotels.search', $query) }}"
                                        class="themeBtn mb-3 themeBtn--full">
                                        <i class="bx bx-sm bx-refresh"></i> Reset Filters
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Sort by:</label>
                                @php
                                    use Carbon\Carbon;
                                    $checkIn = Carbon::createFromFormat('M d, Y', request('check_in'));
                                    $checkOut = Carbon::createFromFormat('M d, Y', request('check_out'));

                                    $nights = max(1, $checkIn->diffInDays($checkOut));
                                    $sortOptions = [
                                        '' => 'Select',
                                        'recommended' => 'Recommended',
                                        'price_low_to_high' => 'Price Low to High',
                                        'price_high_to_low' => 'Price High to Low',
                                        'top_rated' => 'Top Rated',
                                    ];

                                    $sortBy = request('sort_by', '');
                                @endphp

                                <select class="custom-select filter_dropdown" name="sort_by" id="sort_by">
                                    @foreach ($sortOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $sortBy === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="hotets-listing__sidebar">
                        <ul class="filter-blocks mt-0">
                            <li class="filter-block open" custom-accordion="">
                                <div class="filter-block__header" custom-accordion-header="">
                                    <div class="title">Board type</div>
                                    <div class="icon"><i class="bx bx-chevron-down"></i></div>
                                </div>
                                <div class="filter-block__body" custom-accordion-body="">
                                    <div class="overflow-hidden">
                                        <div class="body-wrapper">
                                            @php
                                                $selectedBoards = request()->input('board_type', []);
                                                if (!is_array($selectedBoards)) {
                                                    $selectedBoards = explode(',', $selectedBoards);
                                                }
                                                $selectedBoards = array_map('strtolower', $selectedBoards);
                                            @endphp

                                            <ul class="check-filter-list">
                                                @php
                                                    $boards = [
                                                        'Room Only',
                                                        'Bed And Breakfast',
                                                        'Half Board',
                                                        'Full Board',
                                                    ];
                                                @endphp

                                                @foreach ($boards as $index => $board)
                                                    <li class="check-filter">
                                                        <input type="checkbox" name="board_type" class="check-filter__input"
                                                            id="filter{{ $index + 1 }}" value="{{ $board }}"
                                                            {{ in_array(strtolower($board), $selectedBoards) ? 'checked' : '' }}>
                                                        <label class="check-filter__label"
                                                            for="filter{{ $index + 1 }}">{{ $board }}</label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <li class="filter-block open" custom-accordion="">
                                <div class="filter-block__header" custom-accordion-header="">
                                    <div class="title">Total price</div>
                                    <div class="icon"><i class="bx bx-chevron-down"></i></div>
                                </div>
                                <div class="filter-block__body" custom-accordion-body="">
                                    <div class="overflow-hidden">
                                        <div class="body-wrapper">
                                            <div class="price-ranges-wrapper">
                                                @php
                                                    $minPrice = request()->input('min_price', 0);
                                                    $maxPrice = request()->input('max_price', 100000);
                                                @endphp

                                                <div class="price-ranges">
                                                    <div class="price-ranges__input">
                                                        <label for="min">Min (<span class="dirham">D</span>)</label>
                                                        <input id="min" type="number" min="0" name="min_price"
                                                            value="{{ $minPrice }}">
                                                    </div>
                                                    -
                                                    <div class="price-ranges__input">
                                                        <label for="max">Max (<span class="dirham">D</span>)</label>
                                                        <input id="max" type="number" name="max_price"
                                                            value="{{ $maxPrice }}">
                                                    </div>
                                                </div>
                                                <p class="mb-0">
                                                    Range: <strong><span class="dirham">D</span>
                                                        {{ $minPrice }}</strong> -
                                                    <strong><span class="dirham">D</span> {{ $maxPrice }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="filter-block open" custom-accordion="">
                                <div class="filter-block__header" custom-accordion-header="">
                                    <div class="title">Our Customer Rating</div>
                                    <div class="icon"><i class="bx bx-chevron-down"></i></div>
                                </div>
                                <div class="filter-block__body" custom-accordion-body="">
                                    <div class="overflow-hidden">
                                        <div class="body-wrapper">
                                            @php
                                                $ratingMin = request()->input('rating_range_min', 0);
                                                $ratingMax = request()->input('rating_range_max', 10);
                                            @endphp

                                            <input type="text" class="js-range-slider" name="rating_range"
                                                data-skin="round" data-type="double" data-min="0" data-max="10"
                                                data-grid="false" data-from="{{ $ratingMin }}"
                                                data-to="{{ $ratingMax }}">
                                            <input type="hidden" maxlength="4" value="{{ $ratingMin }}"
                                                name="rating_range_min" id="rating_range_min">
                                            <input type="hidden" maxlength="4" value="{{ $ratingMax }}"
                                                name="rating_range_max" id="rating_range_max">
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <li class="filter-block open" custom-accordion="">
                                <div class="filter-block__header" custom-accordion-header="">
                                    <div class="title">Star rating</div>
                                    <div class="icon"><i class="bx bx-chevron-down"></i></div>
                                </div>
                                <div class="filter-block__body" custom-accordion-body="">
                                    <div class="overflow-hidden">
                                        <div class="body-wrapper">
                                            @php
                                                $selectedRatings = request()->input('rating', []);
                                                if (!is_array($selectedRatings)) {
                                                    $selectedRatings = explode(',', $selectedRatings);
                                                }
                                            @endphp

                                            <ul class="check-filter-list">
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <li class="check-filter">
                                                        <input type="checkbox" name="rating" class="check-filter__input"
                                                            id="filter{{ $i }}-star"
                                                            value="{{ $i }}"
                                                            {{ in_array((string) $i, $selectedRatings) ? 'checked' : '' }}>
                                                        <label class="check-filter__label"
                                                            for="filter{{ $i }}-star">
                                                            <div class="stars">
                                                                @for ($s = 1; $s <= $i; $s++)
                                                                    <i class="bx bxs-star" style="color: #f2ac06"></i>
                                                                @endfor
                                                            </div>
                                                        </label>
                                                    </li>
                                                @endfor
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </li>


                            <li class="filter-block open" custom-accordion="">
                                <div class="filter-block__header" custom-accordion-header="">
                                    <div class="title">Property Type</div>
                                    <div class="icon"><i class="bx bx-chevron-down"></i></div>
                                </div>
                                <div class="filter-block__body" custom-accordion-body="">
                                    <div class="overflow-hidden">
                                        <div class="body-wrapper">
                                            @php
                                                $propertyTypes = request()->input('property_type', []);
                                                if (!is_array($propertyTypes)) {
                                                    $propertyTypes = explode(',', $propertyTypes);
                                                }
                                                $propertyTypes = array_map('strtolower', $propertyTypes);
                                            @endphp

                                            <ul class="check-filter-list">
                                                <li class="check-filter">
                                                    <input type="checkbox" name="property_type"
                                                        class="check-filter__input" id="hotel" value="Hotel"
                                                        {{ in_array('hotel', $propertyTypes) ? 'checked' : '' }}>
                                                    <label class="check-filter__label" for="hotel">Hotel</label>
                                                </li>
                                                <li class="check-filter">
                                                    <input type="checkbox" name="property_type"
                                                        class="check-filter__input" id="apartment" value="Apartment"
                                                        {{ in_array('apartment', $propertyTypes) ? 'checked' : '' }}>
                                                    <label class="check-filter__label" for="apartment">Apartment</label>
                                                </li>
                                            </ul>

                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="filter-block open" custom-accordion="">
                                <div class="filter-block__header" custom-accordion-header="">
                                    <div class="title">Hotel Name</div>
                                    <div class="icon"><i class="bx bx-chevron-down"></i></div>
                                </div>
                                <div class="filter-block__body" custom-accordion-body="">
                                    <div class="overflow-hidden">
                                        <div class="body-wrapper px-0">
                                            <input placeholder="Name" type="text" class="custom-input"
                                                name="hotel_name" id="hotel-name" value="{{ request('hotel_name') }}">
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-9">
                    @if ($hotels->isNotEmpty())
                        @foreach ($hotels as $hotel)
                            <div class="event-card">
                                <div class="row g-0">

                                    <div class="col-md-4">
                                        <a href="{{ route('frontend.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                            class="event-card__img">
                                            <img data-src="{{ $hotel['image'] ?? asset('frontend/images/placeholder.png') }}"
                                                class="imgFluid lazyload" alt="{{ $hotel['name'] }}" />
                                        </a>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="event-card__content">
                                            <a href="{{ route('frontend.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                                class="title">
                                                {{ $hotel['name'] }}
                                            </a>

                                            <div class="details">
                                                <div class="icon"><i class="bx bx-map"></i></div>
                                                <div class="content">{{ $hotel['location'] ?? '' }},
                                                    {{ $hotel['province'] ?? '' }}</div>
                                            </div>


                                            @if ($hotel['rating'])
                                                <div class="rating">
                                                    <div class="stars">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <i class="bx bxs-star"
                                                                style="color: {{ $i <= $hotel['rating'] ? '#f2ac06' : '#ccc' }}"></i>
                                                        @endfor
                                                    </div>
                                                    <div class="rating-average">
                                                        <div class="rating-average-blob">
                                                            {{ number_format($hotel['rating'], 1) }}
                                                        </div>
                                                        <div class="info"> {{ $hotel['rating_text'] }}</div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="details">
                                                <div class="icon"><i class="bx bxs-moon"></i></div>
                                                <div class="content">
                                                    {{ $checkIn->format('M d, Y') }} - {{ $nights }} nights at hotel
                                                </div>
                                            </div>



                                            @if ($hotel['boards']->isNotEmpty())
                                                <div class="details">
                                                    <div class="icon"><i class="bx bx-restaurant"></i></div>
                                                    <div class="content">
                                                        {{ $hotel['boards']->implode(' | ') }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="event-card__content event-card__content--price">
                                            @if ($hotel['price'])
                                                <span class="subtitle">Total price from</span>
                                                <div class="price">{{ formatPrice($hotel['price']) }}
                                                </div>
                                            @endif

                                            <a href="{{ route('frontend.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                                class="themeBtn mt-3">
                                                View More
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-results" aria-labelledby="no-results-title">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-8 col-lg-6 text-center">
                                    <div class="mb-3">
                                        <i class='bx bx-search-alt empty-icon' aria-hidden="true"></i>
                                    </div>
                                    <h2 id="no-results-title" class="h4 fw-bold mb-2">
                                        No hotels found
                                    </h2>
                                    <p class="text-muted mb-4">
                                        We couldn't find any properties matching your criteria.
                                        Try adjusting your filters or searching for different dates.
                                    </p>
                                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                                        <a href="{{ route('frontend.hotels.index') }}"
                                            class="themeBtn themeBtn--primary">
                                            Search alternative dates
                                        </a>
                                        <a href="{{ route('frontend.hotels.search', $query) }}" class="themeBtn">
                                            Reset filters
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.0/js/ion.rangeSlider.min.js"></script>

    <script>
        // for rating range slider
        $(document).ready(function() {
            var $range = $(".js-range-slider"),
                $from = $("#rating_range_max"),
                $to = $("#rating_range_min"),
                range,
                min = $range.data('min'),
                max = $range.data('max'),
                from,
                to;

            if ($range.length && $from.length && $to.length) {

                var updateValues = function() {
                    $from.prop("value", from);
                    $to.prop("value", to);
                };

                $range.ionRangeSlider({
                    onFinish: function(data) {
                        if (data.from !== from || data.to !== to) {
                            from = data.from;
                            to = data.to;
                            updateValues();
                            updateURLParams();
                        }
                    }
                });


                range = $range.data("ionRangeSlider");

                var updateURLParams = function() {
                    const url = new URL(window.location.href);

                    url.searchParams.set('rating_range_min', from);
                    url.searchParams.set('rating_range_max', to);

                    window.location.href = url.toString();
                };

                $from.on("input", function() {
                    let newFrom = +$(this).prop("value");

                    if (newFrom < min) newFrom = min;
                    if (newFrom > to) newFrom = to;

                    if (newFrom !== from) {
                        from = newFrom;
                        updateValues();
                        updateRange();
                        updateURLParams();
                    }
                });

                $to.on("input", function() {
                    let newTo = +$(this).prop("value");

                    if (newTo > max) newTo = max;
                    if (newTo < from) newTo = from;

                    if (newTo !== to) {
                        to = newTo;
                        updateValues();
                        updateRange();
                        updateURLParams();
                    }
                });

                var updateRange = function() {
                    range.update({
                        from: from,
                        to: to
                    });
                };

            } else {
                console.log("Range slider elements not found.");
            }
        });


        // for checkboxes
        const checkboxes = document.querySelectorAll('.check-filter__input');
        checkboxes?.forEach(input => {
            input.addEventListener('change', () => {
                const url = new URL(window.location.href);

                // Get currently selected checkboxes
                const selected = Array.from(document.querySelectorAll(
                    `.check-filter__input[name="${input.name}"]:checked`
                )).map(el => el.value);

                if (selected.length > 0) {
                    // Join selected values with comma and set in URL
                    url.searchParams.set(input.name, selected.join(','));
                } else {
                    // Remove param if nothing selected
                    url.searchParams.delete(input.name);
                }

                // Reload page with updated params
                window.location.href = url.toString();
            });
        });


        // for max/min price
        document.addEventListener("DOMContentLoaded", function() {
            const minInput = document.querySelector('input[name="min_price"]');
            const maxInput = document.querySelector('input[name="max_price"]');

            if (minInput && maxInput) {
                const maxLimit = maxInput.hasAttribute('max') ? parseInt(maxInput.getAttribute('max'), 10) :
                    Infinity;


                const enforcePriceRules = () => {
                    let minVal = parseInt(minInput.value) || 0;
                    let maxVal = parseInt(maxInput.value) || 0;

                    if (minVal < 0) minVal = 0;
                    if (maxVal > maxLimit) maxVal = maxLimit;
                    if (minVal >= maxVal) minVal = maxVal - 1;
                    if (minVal < 0) minVal = 0;
                    if (maxVal <= minVal) maxVal = minVal + 1;

                    minInput.value = minVal;
                    maxInput.value = maxVal;
                };

                const updateURLAndReload = () => {
                    enforcePriceRules();

                    const url = new URL(window.location.href);
                    url.searchParams.set('min_price', minInput.value);
                    url.searchParams.set('max_price', maxInput.value);
                    window.location.href = url.toString();
                };

                minInput.addEventListener('input', enforcePriceRules);
                maxInput.addEventListener('input', enforcePriceRules);

                minInput.addEventListener('blur', updateURLAndReload);
                maxInput.addEventListener('blur', updateURLAndReload);
            }
        });

        // for hotel name
        const hotelInput = document.getElementById('hotel-name');

        if (hotelInput) {
            hotelInput.addEventListener('blur', () => {
                const hotelValue = hotelInput.value.trim();
                const url = new URL(window.location.href);


                if (hotelValue && hotelValue !== '') {
                    url.searchParams.set(hotelInput.getAttribute('name'), hotelValue);
                } else {
                    url.searchParams.delete(hotelInput.getAttribute('name'));
                }

                window.location.href = url.toString();
            });
        }

        // sory by
        document.addEventListener("DOMContentLoaded", function() {
            const sortSelect = document.getElementById("sort_by");


            if (sortSelect) {
                sortSelect.addEventListener("change", function() {
                    const url = new URL(window.location.href);
                    const selectedValue = sortSelect.value;


                    if (selectedValue) {
                        url.searchParams.set("sort_by", selectedValue);
                    } else {
                        url.searchParams.delete("sort_by");
                    }


                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endpush
@push('css')
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.0/css/ion.rangeSlider.min.css">
    <style>
        .irs--round .irs-bar {
            background-color: #cd1b4f;
        }

        .irs--round .irs-handle {
            background-color: #cd1b4f;
            border-color: #cd1b4f;
            box-shadow: 0px 0px 0px 5px #cd1b4f40;
        }

        .irs--round .irs-handle.state_hover,
        .irs--round .irs-handle:hover {
            background-color: #cd1b4f;
        }

        .irs--round .irs-handle {
            width: 21px;
            height: 21px;
            top: 50%;
            cursor: grab;
        }

        .irs--round .irs-from,
        .irs--round .irs-to,
        .irs--round .irs-single {
            font-weight: 600;
            background-color: transparent;
            color: #666666;
        }

        .irs--round .irs-from:before,
        .irs--round .irs-to:before,
        .irs--round .irs-single:before,
        .irs--round .irs-min,
        .irs--round .irs-max {
            display: none;
        }
    </style>
@endpush
