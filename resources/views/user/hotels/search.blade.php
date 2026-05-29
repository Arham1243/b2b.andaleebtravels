@extends('user.layouts.main')
@section('content')
    @php
        use Carbon\Carbon;
        $checkIn = Carbon::createFromFormat('M d, Y', request('check_in'));
        $checkOut = Carbon::createFromFormat('M d, Y', request('check_out'));
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $defaultParams = ['destination', 'destination_type', 'check_in', 'check_out', 'room_count'];
        $currentParams = request()->query();
        $userFilters = collect($currentParams)
            ->filter(fn($value, $key) => !in_array($key, $defaultParams) && str_starts_with($key, 'room_') === false)
            ->toArray();
        $query = collect(request()->query())
            ->filter(fn($value, $key) => in_array($key, $defaultParams) || str_starts_with($key, 'room_'))
            ->toArray();

        $sortBy = request('sort_by', '');
        $isPriceSort =
            $sortBy === '' ||
            $sortBy === 'price_low_to_high' ||
            $sortBy === 'price_high_to_low';
        $isPriceDesc = $sortBy === 'price_high_to_low';
    @endphp

    {{-- Listing only: sticky search strip (index page uses hotels/index.blade.php) --}}
    <div class="hl-page hl-page--listing">
        <div class="hl-swrap">
            <div class="container">
                <div class="hl-search-bar hl-search-bar--listing">
                    @include('user.vue.main', [
                        'appId' => 'hotels-search',
                        'appComponent' => 'hotels-search',
                        'appJs' => 'hotels-search',
                        'hotelSearchListingMode' => true,
                    ])
                </div>
            </div>
        </div>

        <div class="container hl-shell">
            {{-- Results toolbar (flight-style count + SORT BY pills) --}}
            <div class="hl-rp-bar">
                <div class="hl-rp-bar__left">
                    <span class="hl-rp-bar__count">
                        @if ($hotels->total() > 0)
                            Showing <strong>{{ $hotels->firstItem() }}–{{ $hotels->lastItem() }}</strong> of
                            <strong>{{ $hotels->total() }}</strong> hotels found
                        @else
                            <strong>0</strong> hotels found
                        @endif
                    </span>
                </div>
                <div class="hl-rp-bar__sort">
                    <span class="hl-rp-bar__sortlabel">SORT BY:</span>
                    <div class="hl-rp-sortrow">
                        <button type="button"
                            class="hl-rp-sortbtn {{ $sortBy === 'recommended' ? 'hl-rp-sortbtn--active' : '' }}"
                            data-hl-sort="recommended">Recommended</button>
                        <button type="button"
                            class="hl-rp-sortbtn {{ $isPriceSort ? 'hl-rp-sortbtn--active' : '' }} {{ $isPriceDesc ? 'is-desc' : '' }}"
                            data-hl-sort="price">Price <i class="bx bx-down-arrow-alt hl-rp-sortbtn__dir"></i></button>
                        <button type="button"
                            class="hl-rp-sortbtn {{ $sortBy === 'top_rated' ? 'hl-rp-sortbtn--active' : '' }}"
                            data-hl-sort="top_rated">Top rated</button>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- FILTERS SIDEBAR --}}
                <div class="col-lg-3">
                    <aside class="sf" aria-label="Hotel filters">
                        <div class="sf__head">
                            <span class="sf__title"><i class="bx bx-slider-alt"></i> Filters</span>
                            <a href="{{ route('user.hotels.search', $query) }}" class="sf__reset">
                                <i class="bx bx-refresh"></i> Reset
                            </a>
                        </div>

                        @php
                            $selectedSuppliers = request()->input('supplier', []);
                            if (!is_array($selectedSuppliers)) {
                                $selectedSuppliers = explode(',', $selectedSuppliers);
                            }
                            $selectedSuppliers = array_map('strtolower', $selectedSuppliers);
                            $suppliers = $availableSuppliers ?? ['Yalago', 'TBO', 'TripInDeal'];
                        @endphp
                        @if (count($suppliers) !== 1)
                            <div class="sf__section">
                                <div class="sf__sechead"><i class="bx bx-link"></i> Supplier</div>
                                <div class="sf__stop-row">
                                    @foreach ($suppliers as $supplier)
                                        <label class="sf__stoplbl">
                                            <input type="checkbox" name="supplier"
                                                class="check-filter__input sf__stopchk" value="{{ $supplier }}"
                                                {{ in_array(strtolower($supplier), $selectedSuppliers) ? 'checked' : '' }}>
                                            <span class="sf__stoppill">{{ $supplier }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="sf__section">
                            <div class="sf__sechead"><i class="bx bx-restaurant"></i> Board type</div>
                            @php
                                $selectedBoards = request()->input('board_type', []);
                                if (!is_array($selectedBoards)) {
                                    $selectedBoards = explode(',', $selectedBoards);
                                }
                                $selectedBoards = array_map('strtolower', $selectedBoards);
                                $boards = ['Room Only', 'Bed And Breakfast', 'Half Board', 'Full Board'];
                            @endphp
                            <div class="sf__stop-row">
                                @foreach ($boards as $board)
                                    <label class="sf__stoplbl">
                                        <input type="checkbox" name="board_type"
                                            class="check-filter__input sf__stopchk" value="{{ $board }}"
                                            {{ in_array(strtolower($board), $selectedBoards) ? 'checked' : '' }}>
                                        <span class="sf__stoppill">{{ $board }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="sf__section">
                            <div class="sf__sechead"><i class="bx bx-check-shield"></i> Refund policy</div>
                            @php
                                $selectedRefundTypes = request()->input('refund_type', []);
                                if (!is_array($selectedRefundTypes)) {
                                    $selectedRefundTypes = explode(',', $selectedRefundTypes);
                                }
                                $selectedRefundTypes = array_map('strtolower', $selectedRefundTypes);
                            @endphp
                            <div class="sf__stop-row">
                                <label class="sf__stoplbl">
                                    <input type="checkbox" name="refund_type"
                                        class="check-filter__input sf__stopchk" value="refundable"
                                        {{ in_array('refundable', $selectedRefundTypes) ? 'checked' : '' }}>
                                    <span class="sf__stoppill">Refundable Rooms</span>
                                </label>
                                <label class="sf__stoplbl">
                                    <input type="checkbox" name="refund_type"
                                        class="check-filter__input sf__stopchk" value="non_refundable"
                                        {{ in_array('non_refundable', $selectedRefundTypes) ? 'checked' : '' }}>
                                    <span class="sf__stoppill">Non-Refundable Rooms</span>
                                </label>
                            </div>
                        </div>

                        <div class="sf__section">
                            <div class="sf__sechead"><i class="bx bx-wallet-alt"></i> Price range</div>
                            @php
                                $hlPriceSliderMin = 0;
                                $hlPriceSliderMax = 100000;
                                $hlMinPrice = (int) request()->input('min_price', $hlPriceSliderMin);
                                $hlMaxPrice = (int) request()->input('max_price', $hlPriceSliderMax);
                                $hlMinPrice = max($hlPriceSliderMin, min($hlMinPrice, $hlPriceSliderMax - 1));
                                $hlMaxPrice = max($hlMinPrice + 1, min($hlMaxPrice, $hlPriceSliderMax));
                            @endphp
                            <div class="sf__price-labels">
                                <span>{!! currencySymbol() !!}<strong
                                        id="hl-price-lo-lbl">{{ number_format($hlMinPrice, 0, '.', ',') }}</strong></span>
                                <span>{!! currencySymbol() !!}<strong
                                        id="hl-price-hi-lbl">{{ number_format($hlMaxPrice, 0, '.', ',') }}</strong></span>
                            </div>
                            <div class="sf__dual-wrap">
                                <div class="sf__dual-track">
                                    <div class="sf__dual-fill" id="hl-price-fill"></div>
                                </div>
                                <input type="range" class="sf__dual-input sf__dual-input--lo" id="hl-price-lo"
                                    min="{{ $hlPriceSliderMin }}" max="{{ $hlPriceSliderMax }}"
                                    value="{{ $hlMinPrice }}" step="1">
                                <input type="range" class="sf__dual-input sf__dual-input--hi" id="hl-price-hi"
                                    min="{{ $hlPriceSliderMin }}" max="{{ $hlPriceSliderMax }}"
                                    value="{{ $hlMaxPrice }}" step="1">
                            </div>
                        </div>

                        <div class="sf__section">
                            <div class="sf__sechead"><i class="bx bxs-star"></i> Star rating</div>
                            @php
                                $selectedRatings = request()->input('rating', []);
                                if (!is_array($selectedRatings)) {
                                    $selectedRatings = explode(',', $selectedRatings);
                                }
                            @endphp
                            <div class="sf__stop-row sf__stop-row--stars">
                                @for ($i = 5; $i >= 1; $i--)
                                    <label class="sf__stoplbl">
                                        <input type="checkbox" name="rating" class="check-filter__input sf__stopchk"
                                            value="{{ $i }}"
                                            {{ in_array((string) $i, $selectedRatings) ? 'checked' : '' }}>
                                        <span class="sf__stoppill">
                                            @for ($s = 1; $s <= $i; $s++)
                                                <i class="bx bxs-star" style="color: #f2ac06; font-size: 13px;"></i>
                                            @endfor
                                        </span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <div class="sf__section">
                            <div class="sf__sechead"><i class="bx bx-building-house"></i> Property type</div>
                            @php
                                $propertyTypes = request()->input('property_type', []);
                                if (!is_array($propertyTypes)) {
                                    $propertyTypes = explode(',', $propertyTypes);
                                }
                                $propertyTypes = array_map('strtolower', $propertyTypes);
                            @endphp
                            <div class="sf__stop-row">
                                <label class="sf__stoplbl">
                                    <input type="checkbox" name="property_type"
                                        class="check-filter__input sf__stopchk" value="Hotel"
                                        {{ in_array('hotel', $propertyTypes) ? 'checked' : '' }}>
                                    <span class="sf__stoppill">Hotel</span>
                                </label>
                                <label class="sf__stoplbl">
                                    <input type="checkbox" name="property_type"
                                        class="check-filter__input sf__stopchk" value="Apartment"
                                        {{ in_array('apartment', $propertyTypes) ? 'checked' : '' }}>
                                    <span class="sf__stoppill">Apartment</span>
                                </label>
                            </div>
                        </div>

                        <div class="sf__section">
                            <div class="sf__sechead"><i class="bx bx-search-alt"></i> Hotel name</div>
                            <input type="text" class="sf__text-input" placeholder="Search by name" name="hotel_name"
                                id="hotel-name" value="{{ request('hotel_name') }}">
                        </div>
                    </aside>
                </div>

                {{-- HOTEL CARDS --}}
                <div class="col-lg-9">
                    @if ($hotels->isNotEmpty())
                        @foreach ($hotels as $hotel)
                            @php
                                $tboQuery = $query;
                                if (($hotel['supplier'] ?? '') === 'TBO') {
                                    if (!empty($hotel['tbo_booking_code'])) {
                                        $tboQuery['tbo_booking_code'] = $hotel['tbo_booking_code'];
                                    }
                                    if (!empty($hotel['price'])) {
                                        $tboQuery['tbo_price'] = $hotel['price'];
                                    }
                                if (!empty($hotel['tbo_currency'])) {
                                    $tboQuery['tbo_currency'] = $hotel['tbo_currency'];
                                }
                                if (!empty($hotel['tbo_room_name'])) {
                                    $tboQuery['tbo_room_name'] = $hotel['tbo_room_name'];
                                }
                                if (!empty($hotel['tbo_total_fare_raw'])) {
                                    $tboQuery['tbo_total_fare_raw'] = $hotel['tbo_total_fare_raw'];
                                }
                                if (!empty($hotel['tbo_meal_type'])) {
                                    $tboQuery['tbo_meal_type'] = $hotel['tbo_meal_type'];
                                }
                            }
                        @endphp
                            <div class="hl-card">
                                <div class="hl-card__img">
                                    <img src="{{ $hotel['image'] ?? asset('user/assets/images/placeholder.png') }}"
                                        alt="{{ $hotel['name'] }}" />
                                </div>
                                <div class="hl-card__body">
                                    <div class="hl-card__info">
                                        @if (($hotel['supplier'] ?? '') === 'Yalago' && !empty($hotel['id']))
                                            <a href="{{ route('user.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                                class="hl-card__name js-detail-link">{{ $hotel['name'] }}</a>
                                        @elseif (($hotel['supplier'] ?? '') === 'TBO' && !empty($hotel['provider_id']))
                                            <a href="{{ route('user.hotels.details.tbo', ['code' => $hotel['provider_id']]) . '?' . http_build_query($tboQuery) }}"
                                                class="hl-card__name js-detail-link">{{ $hotel['name'] }}</a>
                                        @elseif (($hotel['supplier'] ?? '') === 'TripInDeal' && !empty($hotel['provider_id']))
                                            <a href="{{ route('user.hotels.details.tripindeal', ['code' => $hotel['provider_id']]) . '?' . http_build_query($query) }}"
                                                class="hl-card__name js-detail-link">{{ $hotel['name'] }}</a>
                                        @else
                                            <span class="hl-card__name">{{ $hotel['name'] }}</span>
                                        @endif

                                        <div class="hl-card__location">
                                            <i class="bx bx-map"></i>
                                            @php
                                                $loc = trim((string) ($hotel['location'] ?? ''));
                                                $prov = trim((string) ($hotel['province'] ?? ''));
                                                $showProv = $prov !== '' && strtolower($prov) !== strtolower($loc);
                                            @endphp
                                            {{ $loc }}{{ $showProv ? ', ' . $prov : '' }}
                                        </div>

                                        <div class="hl-card__meta">
                                            <span><i class="bx bxs-moon"></i> {{ $nights }}
                                                night{{ $nights > 1 ? 's' : '' }}</span>
                                            @if (!empty($hotel['boards']))
                                                <span><i class="bx bx-restaurant"></i>
                                                    {{ implode(' | ', $hotel['boards']) }}</span>
                                            @endif
                                        </div>

                                        @include('user.hotels.partials.refund-info', [
                                            'hotel' => $hotel,
                                            'wrapperClass' => 'hl-card__refund',
                                        ])

                                        @if ($hotel['rating'])
                                            <div class="hl-card__rating">
                                                <div class="hl-card__stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i class="bx bxs-star"
                                                            style="color: {{ $i <= $hotel['rating'] ? '#f2ac06' : '#ddd' }}"></i>
                                                    @endfor
                                                </div>
                                                <span
                                                    class="hl-card__rating-badge">{{ number_format($hotel['rating'], 1) }}</span>
                                                <span class="hl-card__rating-text">{{ $hotel['rating_text'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="hl-card__price-col">
                                        @if (!empty($hotel['supplier']))
                                            <span class="hl-card__supplier"><i class="bx bx-link"></i>
                                                {{ $hotel['supplier'] }}</span>
                                        @endif
                                        @if ($hotel['price'])
                                            <span class="hl-card__price-label">Total price from</span>
                                            <div class="hl-card__price">{{ formatPrice($hotel['price']) }}</div>
                                        @endif
                                        @if (($hotel['supplier'] ?? '') === 'Yalago' && !empty($hotel['id']))
                                            <a href="{{ route('user.hotels.details', ['id' => $hotel['id']]) . '?' . http_build_query($query) }}"
                                                class="hl-card__btn js-detail-link">Select Room</a>
                                        @elseif (($hotel['supplier'] ?? '') === 'TBO' && !empty($hotel['provider_id']))
                                            <a href="{{ route('user.hotels.details.tbo', ['code' => $hotel['provider_id']]) . '?' . http_build_query($tboQuery) }}"
                                                class="hl-card__btn js-detail-link">View Details</a>
                                        @elseif (($hotel['supplier'] ?? '') === 'TripInDeal' && !empty($hotel['provider_id']))
                                            <a href="{{ route('user.hotels.details.tripindeal', ['code' => $hotel['provider_id']]) . '?' . http_build_query($query) }}"
                                                class="hl-card__btn js-detail-link">View Details</a>
                                        @else
                                            <span class="hl-card__btn hl-card__btn--disabled">Details unavailable</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Pagination --}}
                        @if ($hotels->hasPages())
                            <div class="hl-pagination" aria-label="Hotel results pages">
                                {{ $hotels->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    @else
                        <div class="fc-empty-state">
                            <div class="fc-empty-icon">
                                <i class='bx bx-search-alt'></i>
                            </div>
                            <h3>No hotels found</h3>
                            <p>We couldn't find any properties matching your criteria. Try adjusting your filters or
                                searching for different dates.</p>
                            <div class="fc-empty-actions">
                                <a href="{{ route('user.hotels.index') }}" class="fc-btn-outline">Search alternative dates</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const navigateWithLoader = (url, message = 'Updating hotel results...') => {
                if (typeof window.showPageLoader === 'function') {
                    window.__enablePageLoaderOnNav = true;
                    window.showPageLoader(message, 'bx bx-restaurant');
                }
                window.location.href = url;
            };

            const resetPageParam = (url) => {
                url.searchParams.delete('page');
                return url;
            };

            // Checkboxes
            document.querySelectorAll('.check-filter__input')?.forEach(input => {
                input.addEventListener('change', () => {
                    const url = resetPageParam(new URL(window.location.href));
                    const selected = Array.from(document.querySelectorAll(
                        `.check-filter__input[name="${input.name}"]:checked`
                    )).map(el => el.value);
                    selected.length > 0 ? url.searchParams.set(input.name, selected.join(',')) : url
                        .searchParams.delete(input.name);
                    navigateWithLoader(url.toString());
                });
            });

            // Price dual range (flight-style sidebar; reload on release)
            const hlPriceLo = document.getElementById('hl-price-lo');
            const hlPriceHi = document.getElementById('hl-price-hi');
            const hlPriceFill = document.getElementById('hl-price-fill');
            const hlPriceLoLbl = document.getElementById('hl-price-lo-lbl');
            const hlPriceHiLbl = document.getElementById('hl-price-hi-lbl');

            function hlFmtPriceNum(v) {
                return parseFloat(v).toLocaleString(undefined, {
                    maximumFractionDigits: 0
                });
            }

            function hlUpdatePriceFill() {
                if (!hlPriceLo || !hlPriceHi || !hlPriceFill) return;
                const mn = parseFloat(hlPriceLo.min);
                const mx = parseFloat(hlPriceLo.max);
                const lo = parseFloat(hlPriceLo.value);
                const hi = parseFloat(hlPriceHi.value);
                const pct = (v) => (mx === mn ? 0 : ((v - mn) / (mx - mn)) * 100);
                hlPriceFill.style.left = pct(lo) + '%';
                hlPriceFill.style.width = Math.max(0, pct(hi) - pct(lo)) + '%';
                if (hlPriceLoLbl) hlPriceLoLbl.textContent = hlFmtPriceNum(lo);
                if (hlPriceHiLbl) hlPriceHiLbl.textContent = hlFmtPriceNum(hi);
            }

            function hlReloadPriceFilter() {
                if (!hlPriceLo || !hlPriceHi) return;
                const url = resetPageParam(new URL(window.location.href));
                url.searchParams.set('min_price', hlPriceLo.value);
                url.searchParams.set('max_price', hlPriceHi.value);
                navigateWithLoader(url.toString());
            }

            if (hlPriceLo && hlPriceHi) {
                hlPriceLo.addEventListener('input', () => {
                    if (parseFloat(hlPriceLo.value) > parseFloat(hlPriceHi.value) - 1) {
                        hlPriceLo.value = parseFloat(hlPriceHi.value) - 1;
                    }
                    hlUpdatePriceFill();
                });
                hlPriceHi.addEventListener('input', () => {
                    if (parseFloat(hlPriceHi.value) < parseFloat(hlPriceLo.value) + 1) {
                        hlPriceHi.value = parseFloat(hlPriceLo.value) + 1;
                    }
                    hlUpdatePriceFill();
                });
                hlPriceLo.addEventListener('change', hlReloadPriceFilter);
                hlPriceHi.addEventListener('change', hlReloadPriceFilter);
                hlUpdatePriceFill();
            }

            // Hotel name
            const hotelInput = document.getElementById('hotel-name');
            if (hotelInput) {
                hotelInput.addEventListener('blur', () => {
                    const val = hotelInput.value.trim();
                    const url = resetPageParam(new URL(window.location.href));
                    val ? url.searchParams.set('hotel_name', val) : url.searchParams.delete('hotel_name');
                    navigateWithLoader(url.toString());
                });
            }

            // Sort (flight-style pill buttons)
            document.querySelectorAll('.hl-rp-sortbtn[data-hl-sort]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const kind = btn.dataset.hlSort;
                    const url = resetPageParam(new URL(window.location.href));
                    if (kind === 'price') {
                        const cur = url.searchParams.get('sort_by') || '';
                        let next;
                        if (cur === 'recommended' || cur === 'top_rated') {
                            next = 'price_low_to_high';
                        } else if (cur === 'price_high_to_low') {
                            next = 'price_low_to_high';
                        } else {
                            next = 'price_high_to_low';
                        }
                        url.searchParams.set('sort_by', next);
                        navigateWithLoader(url.toString());
                        return;
                    }
                    if (kind === 'recommended') {
                        url.searchParams.set('sort_by', 'recommended');
                        navigateWithLoader(url.toString());
                        return;
                    }
                    if (kind === 'top_rated') {
                        url.searchParams.set('sort_by', 'top_rated');
                        navigateWithLoader(url.toString());
                    }
                });
            });

            // Pagination page links
            document.querySelectorAll('.hl-pagination .page-link')?.forEach(link => {
                link.addEventListener('click', (e) => {
                    const url = link.getAttribute('href');
                    const pageItem = link.closest('.page-item');
                    if (!url || !url.startsWith('http') || pageItem?.classList.contains('disabled') || pageItem?.classList.contains('active')) {
                        return;
                    }
                    e.preventDefault();
                    navigateWithLoader(url, 'Loading hotels...');
                });
            });

            // Details links
            document.querySelectorAll('.js-detail-link')?.forEach(link => {
                link.addEventListener('click', (e) => {
                    const url = link.getAttribute('href');
                    if (!url) return;
                    e.preventDefault();
                    navigateWithLoader(url, 'Loading hotel details...');
                });
            });
        });
    </script>
@endpush
@push('css')
    <style>
        .hl-card__supplier {
            background: #e8f4fd;
            color: #1976d2;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .hl-card__btn--disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        .fc-empty-state {
            background: #ffffff;
            border-radius: 24px;
            border: 1px dashed #cbd5e1;
            padding: 60px 30px;
            text-align: center;
            max-width: 600px;
            margin: 40px auto;
        }
        .fc-empty-icon {
            width: 64px;
            height: 64px;
            background: rgba(205, 27, 79, 0.05);
            color: #cd1b4f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }
        .fc-empty-state h3 {
            color: #1e293b;
            font-weight: 800;
            font-size: 1.15rem;
            margin-bottom: 10px;
        }
        .fc-empty-state p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .fc-btn-outline {
            display: inline-block;
            border: 2px solid #cd1b4f;
            color: #cd1b4f;
            padding: 12px 30px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .fc-btn-outline:hover {
            background: #cd1b4f;
            color: white !important;
            box-shadow: 0 8px 15px rgba(205, 27, 79, 0.2);
        }

        .hl-pagination {
            padding: 24px 0 8px;
        }

        .hl-pagination > nav {
            width: 100%;
            margin-bottom: 0;
        }

        .hl-pagination .flex-sm-fill {
            width: 100%;
        }

        .hl-pagination .small.text-muted {
            margin: 0;
            font-size: 0.82rem;
            color: #888 !important;
            font-weight: 500;
        }

        .hl-pagination .pagination {
            margin-bottom: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 4px;
        }

        .hl-pagination .page-link {
            border-radius: 8px;
            color: var(--color-primary, #cd1b4f);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
            border-color: #e5e7eb;
        }

        .hl-pagination .page-item.active .page-link {
            background: var(--color-primary, #cd1b4f);
            border-color: var(--color-primary, #cd1b4f);
            color: #fff;
        }

        .hl-pagination .page-item.disabled .page-link {
            color: #9ca3af;
        }
    </style>
@endpush
