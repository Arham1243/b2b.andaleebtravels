@extends('user.layouts.main')
@section('content')
    @php
        use Carbon\Carbon;
        $checkIn = Carbon::createFromFormat('M d, Y', request('check_in'));
        $checkOut = Carbon::createFromFormat('M d, Y', request('check_out'));
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $defaultParams = ['destination', 'check_in', 'check_out', 'room_count'];
        $currentParams = request()->query();
        $userFilters = collect($currentParams)
            ->filter(fn($value, $key) => !in_array($key, $defaultParams) && str_starts_with($key, 'room_') === false)
            ->toArray();
        $query = collect(request()->query())
            ->filter(fn($value, $key) => in_array($key, $defaultParams) || str_starts_with($key, 'room_'))
            ->toArray();

        $sortOptions = [
            '' => 'Select',
            'recommended' => 'Recommended',
            'price_low_to_high' => 'Price Low to High',
            'price_high_to_low' => 'Price High to Low',
            'top_rated' => 'Top Rated',
        ];
        $sortBy = request('sort_by', '');
    @endphp

    <div class="hl-page">
        <div class="container">
            {{-- SEARCH BAR --}}
            <div class="hl-search-bar mb-5">
                @include('user.vue.main', [
                    'appId' => 'hotels-search',
                    'appComponent' => 'hotels-search',
                    'appJs' => 'hotels-search',
                ])
            </div>

            {{-- RESULTS HEADER --}}
            <div class="hl-results-header">
                <div class="hl-results-header__left">
                    <span class="hl-results-count">{{ $totalHotels }} results found</span>
                    @if (count($userFilters) > 0)
                        <a href="{{ route('user.hotels.search', $query) }}" class="hl-reset-btn">
                            <i class="bx bx-refresh"></i> Reset Filters
                        </a>
                    @endif
                </div>
                <div class="hl-results-header__right">
                    <label class="hl-sort-label">Sort by:</label>
                    <select class="hl-sort-select" name="sort_by" id="sort_by">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" {{ $sortBy === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                {{-- FILTERS SIDEBAR --}}
                <div class="col-lg-3">
                    <div class="hl-sidebar">
                        <div class="hl-sidebar__title">Filters</div>

                        {{-- Board Type --}}
                        <div class="hl-filter-group">
                            <div class="hl-filter-group__header" onclick="this.parentElement.classList.toggle('collapsed')">
                                <span>Board Type</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                @php
                                    $selectedBoards = request()->input('board_type', []);
                                    if (!is_array($selectedBoards)) $selectedBoards = explode(',', $selectedBoards);
                                    $selectedBoards = array_map('strtolower', $selectedBoards);
                                    $boards = ['Room Only', 'Bed And Breakfast', 'Half Board', 'Full Board'];
                                @endphp
                                @foreach ($boards as $index => $board)
                                    <label class="hl-checkbox">
                                        <input type="checkbox" name="board_type" class="check-filter__input"
                                            value="{{ $board }}"
                                            {{ in_array(strtolower($board), $selectedBoards) ? 'checked' : '' }}>
                                        <span class="hl-checkbox__mark"></span>
                                        <span class="hl-checkbox__text">{{ $board }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Price Range --}}
                        <div class="hl-filter-group">
                            <div class="hl-filter-group__header" onclick="this.parentElement.classList.toggle('collapsed')">
                                <span>Price Range</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                @php
                                    $minPrice = request()->input('min_price', 0);
                                    $maxPrice = request()->input('max_price', 100000);
                                @endphp
                                <div class="hl-price-inputs">
                                    <div class="hl-price-input">
                                        <label>Min</label>
                                        <input type="number" min="0" name="min_price" value="{{ $minPrice }}">
                                    </div>
                                    <span class="hl-price-sep">&mdash;</span>
                                    <div class="hl-price-input">
                                        <label>Max</label>
                                        <input type="number" name="max_price" value="{{ $maxPrice }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Customer Rating --}}
                        <div class="hl-filter-group">
                            <div class="hl-filter-group__header" onclick="this.parentElement.classList.toggle('collapsed')">
                                <span>Customer Rating</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                @php
                                    $ratingMin = request()->input('rating_range_min', 0);
                                    $ratingMax = request()->input('rating_range_max', 10);
                                @endphp
                                <input type="text" class="js-range-slider" name="rating_range"
                                    data-skin="round" data-type="double" data-min="0" data-max="10"
                                    data-grid="false" data-from="{{ $ratingMin }}" data-to="{{ $ratingMax }}">
                                <input type="hidden" value="{{ $ratingMin }}" name="rating_range_min" id="rating_range_min">
                                <input type="hidden" value="{{ $ratingMax }}" name="rating_range_max" id="rating_range_max">
                            </div>
                        </div>

                        {{-- Star Rating --}}
                        <div class="hl-filter-group">
                            <div class="hl-filter-group__header" onclick="this.parentElement.classList.toggle('collapsed')">
                                <span>Star Rating</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                @php
                                    $selectedRatings = request()->input('rating', []);
                                    if (!is_array($selectedRatings)) $selectedRatings = explode(',', $selectedRatings);
                                @endphp
                                @for ($i = 5; $i >= 1; $i--)
                                    <label class="hl-checkbox">
                                        <input type="checkbox" name="rating" class="check-filter__input"
                                            value="{{ $i }}"
                                            {{ in_array((string) $i, $selectedRatings) ? 'checked' : '' }}>
                                        <span class="hl-checkbox__mark"></span>
                                        <span class="hl-checkbox__text">
                                            @for ($s = 1; $s <= $i; $s++)
                                                <i class="bx bxs-star" style="color: #f2ac06; font-size: 14px;"></i>
                                            @endfor
                                        </span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        {{-- Property Type --}}
                        <div class="hl-filter-group">
                            <div class="hl-filter-group__header" onclick="this.parentElement.classList.toggle('collapsed')">
                                <span>Property Type</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                @php
                                    $propertyTypes = request()->input('property_type', []);
                                    if (!is_array($propertyTypes)) $propertyTypes = explode(',', $propertyTypes);
                                    $propertyTypes = array_map('strtolower', $propertyTypes);
                                @endphp
                                <label class="hl-checkbox">
                                    <input type="checkbox" name="property_type" class="check-filter__input"
                                        value="Hotel" {{ in_array('hotel', $propertyTypes) ? 'checked' : '' }}>
                                    <span class="hl-checkbox__mark"></span>
                                    <span class="hl-checkbox__text">Hotel</span>
                                </label>
                                <label class="hl-checkbox">
                                    <input type="checkbox" name="property_type" class="check-filter__input"
                                        value="Apartment" {{ in_array('apartment', $propertyTypes) ? 'checked' : '' }}>
                                    <span class="hl-checkbox__mark"></span>
                                    <span class="hl-checkbox__text">Apartment</span>
                                </label>
                            </div>
                        </div>

                        {{-- Hotel Name --}}
                        <div class="hl-filter-group">
                            <div class="hl-filter-group__header" onclick="this.parentElement.classList.toggle('collapsed')">
                                <span>Hotel Name</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                <input type="text" class="hl-text-input" placeholder="Search by name"
                                    name="hotel_name" id="hotel-name" value="{{ request('hotel_name') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- HOTEL CARDS --}}
                <div class="col-lg-9">
                    @if ($hotels->isNotEmpty())
                        @foreach ($hotels as $hotel)
                            <div class="hl-card">
                                <div class="hl-card__img">
                                    <img src="{{ $hotel['image'] ?? asset('user/assets/images/placeholder.png') }}"
                                        alt="{{ $hotel['name'] }}" />
                                </div>
                                <div class="hl-card__body">
                                    <div class="hl-card__info">
                                        <a href="{{ route('user.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                            class="hl-card__name">{{ $hotel['name'] }}</a>

                                        <div class="hl-card__location">
                                            <i class="bx bx-map"></i>
                                            {{ $hotel['location'] ?? '' }}{{ $hotel['province'] ? ', ' . $hotel['province'] : '' }}
                                        </div>

                                        <div class="hl-card__meta">
                                            <span><i class="bx bxs-moon"></i> {{ $nights }} night{{ $nights > 1 ? 's' : '' }}</span>
                                            @if ($hotel['boards']->isNotEmpty())
                                                <span><i class="bx bx-restaurant"></i> {{ $hotel['boards']->implode(' | ') }}</span>
                                            @endif
                                        </div>
                                        
                                        @if ($hotel['rating'])
                                            <div class="hl-card__rating">
                                                <div class="hl-card__stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i class="bx bxs-star" style="color: {{ $i <= $hotel['rating'] ? '#f2ac06' : '#ddd' }}"></i>
                                                    @endfor
                                                </div>
                                                <span class="hl-card__rating-badge">{{ number_format($hotel['rating'], 1) }}</span>
                                                <span class="hl-card__rating-text">{{ $hotel['rating_text'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="hl-card__price-col">
                                        @if ($hotel['price'])
                                            <span class="hl-card__price-label">Total price from</span>
                                            <div class="hl-card__price">{{ formatPrice($hotel['price']) }}</div>
                                        @endif
                                        <a href="{{ route('user.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                            class="hl-card__btn">Select Room</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Pagination --}}
                        <div class="hl-pagination">
                            <div class="hl-pagination__info">
                                Showing {{ min($currentPage * $perPage, $totalHotels) }} of {{ $totalHotels }} hotels
                            </div>
                            @if ($hasMore)
                                @php
                                    $nextPageUrl = url()->current() . '?' . http_build_query(array_merge(request()->query(), ['page' => $currentPage + 1]));
                                @endphp
                                <a href="{{ $nextPageUrl }}" class="hl-pagination__btn">
                                    Show More <i class="bx bx-chevron-down"></i>
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="hl-empty">
                            <i class="bx bx-search-alt"></i>
                            <h3>No hotels found</h3>
                            <p>We couldn't find any properties matching your criteria. Try adjusting your filters or searching for different dates.</p>
                            <div class="hl-empty__actions">
                                <a href="{{ route('user.hotels.index') }}" class="themeBtn">Search alternative dates</a>
                                <a href="{{ route('user.hotels.search', $query) }}" class="themeBtn">Reset filters</a>
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
        $(document).ready(function() {
            // Rating range slider
            var $range = $(".js-range-slider"),
                $from = $("#rating_range_max"),
                $to = $("#rating_range_min"),
                range, min = $range.data('min'), max = $range.data('max'), from, to;

            if ($range.length && $from.length && $to.length) {
                var updateValues = function() { $from.prop("value", from); $to.prop("value", to); };

                $range.ionRangeSlider({
                    onFinish: function(data) {
                        if (data.from !== from || data.to !== to) {
                            from = data.from; to = data.to;
                            updateValues(); updateURLParams();
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
                    if (newFrom !== from) { from = newFrom; updateValues(); range.update({ from: from, to: to }); updateURLParams(); }
                });

                $to.on("input", function() {
                    let newTo = +$(this).prop("value");
                    if (newTo > max) newTo = max;
                    if (newTo < from) newTo = from;
                    if (newTo !== to) { to = newTo; updateValues(); range.update({ from: from, to: to }); updateURLParams(); }
                });
            }

            // Checkboxes
            document.querySelectorAll('.check-filter__input')?.forEach(input => {
                input.addEventListener('change', () => {
                    const url = new URL(window.location.href);
                    const selected = Array.from(document.querySelectorAll(
                        `.check-filter__input[name="${input.name}"]:checked`
                    )).map(el => el.value);
                    selected.length > 0 ? url.searchParams.set(input.name, selected.join(',')) : url.searchParams.delete(input.name);
                    window.location.href = url.toString();
                });
            });

            // Price inputs
            const minInput = document.querySelector('input[name="min_price"]');
            const maxInput = document.querySelector('input[name="max_price"]');
            if (minInput && maxInput) {
                const enforce = () => {
                    let minVal = parseInt(minInput.value) || 0, maxVal = parseInt(maxInput.value) || 0;
                    if (minVal < 0) minVal = 0;
                    if (minVal >= maxVal) minVal = maxVal - 1;
                    if (minVal < 0) minVal = 0;
                    if (maxVal <= minVal) maxVal = minVal + 1;
                    minInput.value = minVal; maxInput.value = maxVal;
                };
                const reload = () => {
                    enforce();
                    const url = new URL(window.location.href);
                    url.searchParams.set('min_price', minInput.value);
                    url.searchParams.set('max_price', maxInput.value);
                    window.location.href = url.toString();
                };
                minInput.addEventListener('input', enforce);
                maxInput.addEventListener('input', enforce);
                minInput.addEventListener('blur', reload);
                maxInput.addEventListener('blur', reload);
            }

            // Hotel name
            const hotelInput = document.getElementById('hotel-name');
            if (hotelInput) {
                hotelInput.addEventListener('blur', () => {
                    const val = hotelInput.value.trim();
                    const url = new URL(window.location.href);
                    val ? url.searchParams.set('hotel_name', val) : url.searchParams.delete('hotel_name');
                    window.location.href = url.toString();
                });
            }

            // Sort
            const sortSelect = document.getElementById("sort_by");
            if (sortSelect) {
                sortSelect.addEventListener("change", function() {
                    const url = new URL(window.location.href);
                    this.value ? url.searchParams.set("sort_by", this.value) : url.searchParams.delete("sort_by");
                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endpush
@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.0/css/ion.rangeSlider.min.css">
    <style>
        .irs--round .irs-bar { background-color: var(--color-primary); }
        .irs--round .irs-handle { background-color: var(--color-primary); border-color: var(--color-primary); box-shadow: 0 0 0 5px #cd1b4f40; width: 21px; height: 21px; top: 50%; cursor: grab; }
        .irs--round .irs-handle.state_hover, .irs--round .irs-handle:hover { background-color: var(--color-primary); }
        .irs--round .irs-from, .irs--round .irs-to, .irs--round .irs-single { font-weight: 600; background-color: transparent; color: #666; }
        .irs--round .irs-from:before, .irs--round .irs-to:before, .irs--round .irs-single:before, .irs--round .irs-min, .irs--round .irs-max { display: none; }
    </style>
@endpush
