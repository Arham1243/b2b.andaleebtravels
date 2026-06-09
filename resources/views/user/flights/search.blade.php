@extends('user.layouts.main')
@section('content')
    @php
        $query = request()->query();
        $tripType = $tripType ?? ($query['trip_type'] ?? 'one_way');
        $isRoundTrip = $tripType === 'round_trip';
        $results = $results ?? [];
        $itineraryCount = $itineraryCount ?? count($results);
        $currencyCode = strtoupper((string) ($results[0]['currency'] ?? 'BDT'));
        $hasSearch = !empty($query['from']) || !empty($query['to']) || !empty($query['departure_date']);

        function fl_format_hm(?int $mins): string
        {
            if ($mins === null || $mins < 1) return ' - ';
            $h = intdiv((int) $mins, 60);
            $r = (int) $mins % 60;
            if ($h > 0 && $r === 0) return "{$h}h";
            if ($h === 0) return "{$r}m";
            return "{$h}h {$r}m";
        }

        function fl_segment_minutes(array $seg): ?int
        {
            try {
                $d = \Carbon\Carbon::parse($seg['departure_datetime'] ?? null);
                $a = \Carbon\Carbon::parse($seg['arrival_datetime'] ?? null);
                $fromDates = max(0, (int) $d->diffInMinutes($a, false));

                if ($fromDates > 0) {
                    return $fromDates;
                }
            } catch (\Throwable $e) {
                // fall through to stored elapsedTime
            }

            if (isset($seg['elapsedTime']) && is_numeric($seg['elapsedTime']) && (int) $seg['elapsedTime'] > 0) {
                return (int) $seg['elapsedTime'];
            }

            return null;
        }

        function fl_layover_minutes(array $prev, array $next): ?int
        {
            try {
                $a = \Carbon\Carbon::parse($prev['arrival_datetime'] ?? null);
                $d = \Carbon\Carbon::parse($next['departure_datetime'] ?? null);
                return max(0, (int) $a->diffInMinutes($d, false));
            } catch (\Throwable $e) { return null; }
        }

        function fl_carrier_logo(?string $code): string
        {
            $c = strtoupper(trim((string) ($code ?: 'XX')));
            return "https://pics.avs.io/100/100/{$c}.png";
        }

        function fl_city_label(array $seg, bool $isDep = true): string
        {
            $code = trim((string) ($isDep ? ($seg['from'] ?? '') : ($seg['to'] ?? '')));
            $city = trim((string) ($seg[$isDep ? 'departure_city' : 'arrival_city'] ?? ''));

            return resolveFlightCityLabel($city, $code);
        }
    @endphp

    <div class="rp">

        {{-- Search form (untouched Vue mount) --}}
        <div class="rp-swrap">
            <div class="container">
                @include('user.vue.main', [
                    'appId'               => 'flights-search',
                    'appComponent'        => 'flights-search',
                    'appJs'               => 'flights-search',
                    'flightSearchListingMode' => true,
                ])
            </div>
        </div>

        <div class="container rp-shell">

            @if ($itineraryCount > 0)

                @php
                    /* ── Airline map ── */
                    $airlineMap = [];
                    foreach ($results as $_r) {
                        $_seg  = $_r['legs'][0]['segments'][0] ?? [];
                        $_code = strtoupper(trim((string)($_seg['carrier'] ?? '')));
                        $_name = $_seg['carrier_display'] ?? $_code;
                        $_price= (float)($_r['totalPrice'] ?? 0);
                        if ($_code === '') continue;
                        if (!isset($airlineMap[$_code])) {
                            $airlineMap[$_code] = ['code' => $_code, 'name' => $_name, 'min_price' => $_price, 'count' => 0];
                        }
                        $airlineMap[$_code]['count']++;
                        if ($_price < $airlineMap[$_code]['min_price']) {
                            $airlineMap[$_code]['min_price'] = $_price;
                        }
                    }
                    uasort($airlineMap, fn($a,$b) => $a['min_price'] <=> $b['min_price']);

                    /* ── Filter meta ── */
                    $_prices  = array_map(fn($r) => (float)($r['totalPrice'] ?? 0), $results);
                    $sfPrMin  = !empty($_prices) ? (int)floor(min($_prices)) : 0;
                    $sfPrMax  = !empty($_prices) ? (int)ceil(max($_prices))  : 9999;

                    $_durs    = [];
                    foreach ($results as $_r) {
                        foreach ($_r['legs'] ?? [] as $_leg) {
                            $_dur = (int) ($_leg['elapsedTime'] ?? 0);
                            if ($_dur > 0) {
                                $_durs[] = $_dur;
                            }
                        }
                    }
                    $sfDurMax  = !empty($_durs) ? max($_durs) : 1440;
                    $sfDurMin  = !empty($_durs) ? min($_durs) : 0;
                    $sfDurSpan = $sfDurMax - $sfDurMin;

                    $sfStopsAvail = [];
                    foreach ($results as $_r) {
                        $_m = $_r['listing_meta'] ?? [];
                        $_stO = (int) ($_m['st_o'] ?? 0);
                        $_stR = array_key_exists('st_r', $_m) && $_m['st_r'] !== null ? (int) $_m['st_r'] : null;
                        $_st = $_stR !== null ? max($_stO, $_stR) : $_stO;
                        $_tier = $_st >= 2 ? 2 : $_st;
                        $sfStopsAvail[$_tier] = true;
                    }
                    ksort($sfStopsAvail);
                    $sfStopsAvail = array_keys($sfStopsAvail);

                    $sfTimeSlots = [
                        ['key' => 'night', 'icon' => 'bx bxs-moon', 'label' => 'Night', 'range' => '00–06'],
                        ['key' => 'morning', 'icon' => 'bx bx-sun', 'label' => 'Morning', 'range' => '06–12'],
                        ['key' => 'afternoon', 'icon' => 'bx bxs-sun', 'label' => 'Afternoon', 'range' => '12–18'],
                        ['key' => 'evening', 'icon' => 'bx bx-moon', 'label' => 'Evening', 'range' => '18–24'],
                    ];

                    $sfRefundCounts = ['refundable' => 0, 'non_refundable' => 0];
                    foreach ($results as $_r) {
                        if (!empty($_r['non_refundable'])) {
                            $sfRefundCounts['non_refundable']++;
                        } else {
                            $sfRefundCounts['refundable']++;
                        }
                    }
                @endphp

                {{-- Fare count full-width above filters/results --}}
                <div class="row g-2 rp-listing-prelude">
                    <div class="col-12 rp-listing-prelude__count-wrap">
                        <span class="rp-bar__count">
                            Showing <strong>{{ $itineraryCount }}</strong> of <strong>{{ $itineraryCount }}</strong> Fares found
                        </span>
                    </div>
                    <div class="col-12">
                        <aside class="rp-advisory" role="note" aria-label="Agency booking advisory">
                            <div class="rp-advisory__mark" aria-hidden="true">
                                <i class="bx bx-shield-quarter"></i>
                            </div>
                            <div class="rp-advisory__body">
                                <span class="rp-advisory__tag">Agency advisory</span>
                                <p class="rp-advisory__title">Avoid duplicate bookings on the same airline</p>
                                <p class="rp-advisory__text">
                                    If the same traveller is reserved more than once on one carrier, the airline may issue an
                                    Airline Debit Memo (ADM). Any ADM applied by the airline will be recovered from your agency account.
                                </p>
                            </div>
                        </aside>
                    </div>
                </div>

                {{-- ══ Grid: row1 [filter head | slider+sort], row2 [filter body | cards] ══ --}}
                <div class="rp-stack">

                <div class="rp-stack__lead">
                    <div class="sf sf--filter-cap">
                        <div class="sf__head">
                            <span class="sf__title"><i class="bx bx-slider-alt"></i> Filters</span>
                            <button class="sf__reset" id="sf-reset" title="Reset all filters">
                                <i class="bx bx-refresh"></i> Reset
                            </button>
                        </div>
                    </div>

                    <aside class="sf sf--filter-rest" id="sf-sidebar">

                    {{-- Price Range --}}
                    <div class="sf__section">
                        <div class="sf__sechead"><i class="bx bx-wallet-alt"></i> Price Range</div>
                        <div class="sf__price-labels">
                            <span id="sf-plo-lbl">{{ $currencyCode }} {{ number_format($sfPrMin, 0) }}</span>
                            <span id="sf-phi-lbl">{{ $currencyCode }} {{ number_format($sfPrMax, 0) }}</span>
                        </div>
                        <div class="sf__dual-wrap">
                            <div class="sf__dual-track">
                                <div class="sf__dual-fill" id="sf-price-fill"></div>
                            </div>
                            <input type="range" class="sf__dual-input sf__dual-input--lo" id="sf-plo"
                                   min="{{ $sfPrMin }}" max="{{ $sfPrMax }}" value="{{ $sfPrMin }}" step="1">
                            <input type="range" class="sf__dual-input sf__dual-input--hi" id="sf-phi"
                                   min="{{ $sfPrMin }}" max="{{ $sfPrMax }}" value="{{ $sfPrMax }}" step="1">
                        </div>
                    </div>

                    {{-- Stops --}}
                    @if(!empty($sfStopsAvail))
                    <div class="sf__section">
                        <div class="sf__sechead"><i class="bx bx-map-pin"></i> Stops</div>
                        <div class="sf__stop-row">
                            @foreach($sfStopsAvail as $_s)
                            <label class="sf__stoplbl">
                                <input type="checkbox" class="sf__stopchk" value="{{ $_s }}" data-sf="stops">
                                <span class="sf__stoppill">
                                    @if($_s === 0)
                                        <i class="bx bxs-plane"></i> Direct
                                    @elseif($_s === 1)
                                        1 Stop
                                    @else
                                        {{ $_s }}+ Stops
                                    @endif
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Refund policy --}}
                    @if($sfRefundCounts['refundable'] > 0 || $sfRefundCounts['non_refundable'] > 0)
                    <div class="sf__section">
                        <div class="sf__sechead"><i class="bx bx-check-shield"></i> Refund policy</div>
                        <div class="sf__stop-row">
                            @if($sfRefundCounts['refundable'] > 0)
                            <label class="sf__stoplbl">
                                <input type="checkbox" class="sf__stopchk" data-sf="refund" value="1">
                                <span class="sf__stoppill sf__stoppill--ref">
                                    Refundable <span class="sf__pillcnt">({{ $sfRefundCounts['refundable'] }})</span>
                                </span>
                            </label>
                            @endif
                            @if($sfRefundCounts['non_refundable'] > 0)
                            <label class="sf__stoplbl">
                                <input type="checkbox" class="sf__stopchk" data-sf="refund" value="0">
                                <span class="sf__stoppill sf__stoppill--nr">
                                    Non-Refundable <span class="sf__pillcnt">({{ $sfRefundCounts['non_refundable'] }})</span>
                                </span>
                            </label>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Departure Time --}}
                    <div class="sf__section">
                        <div class="sf__sechead">
                            <i class="bx bx-time-five"></i>
                            {{ $isRoundTrip ? 'Outbound Departure' : 'Departure Time' }}
                        </div>
                        <div class="sf__time-grid">
                            @foreach($sfTimeSlots as $_slot)
                            <button class="sf__timebtn" data-sf-dep="{{ $_slot['key'] }}">
                                <i class="{{ $_slot['icon'] }}"></i><span>{{ $_slot['label'] }}</span><small>{{ $_slot['range'] }}</small>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Arrival Time --}}
                    <div class="sf__section">
                        <div class="sf__sechead">
                            <i class="bx bx-landing"></i>
                            {{ $isRoundTrip ? 'Outbound Arrival' : 'Arrival Time' }}
                        </div>
                        <div class="sf__time-grid">
                            @foreach($sfTimeSlots as $_slot)
                            <button class="sf__timebtn" data-sf-arr="{{ $_slot['key'] }}">
                                <i class="{{ $_slot['icon'] }}"></i><span>{{ $_slot['label'] }}</span><small>{{ $_slot['range'] }}</small>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    @if($isRoundTrip)
                    {{-- Return Departure Time --}}
                    <div class="sf__section">
                        <div class="sf__sechead"><i class="bx bx-time-five"></i> Return Departure</div>
                        <div class="sf__time-grid">
                            @foreach($sfTimeSlots as $_slot)
                            <button class="sf__timebtn" data-sf-dep-r="{{ $_slot['key'] }}">
                                <i class="{{ $_slot['icon'] }}"></i><span>{{ $_slot['label'] }}</span><small>{{ $_slot['range'] }}</small>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Return Arrival Time --}}
                    <div class="sf__section">
                        <div class="sf__sechead"><i class="bx bx-landing"></i> Return Arrival</div>
                        <div class="sf__time-grid">
                            @foreach($sfTimeSlots as $_slot)
                            <button class="sf__timebtn" data-sf-arr-r="{{ $_slot['key'] }}">
                                <i class="{{ $_slot['icon'] }}"></i><span>{{ $_slot['label'] }}</span><small>{{ $_slot['range'] }}</small>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Max Duration --}}
                    @if($sfDurSpan >= 15)
                    <div class="sf__section">
                        <div class="sf__sechead">
                            <i class="bx bx-stopwatch"></i> Max Duration
                            <span class="sf__dur-val" id="sf-dur-lbl">
                                {{ intdiv($sfDurMax, 60) }}h {{ $sfDurMax % 60 }}m
                            </span>
                        </div>
                        <input type="range" class="sf__single-range" id="sf-dur"
                               min="{{ $sfDurMin > 0 ? $sfDurMin : 30 }}"
                               max="{{ $sfDurMax }}"
                               value="{{ $sfDurMax }}" step="15">
                    </div>
                    @endif

                    </aside>{{-- /.sf--filter-rest --}}
                </div>{{-- /.rp-stack__lead --}}

                <div class="rp-stack__body">
                <div class="rp-stack__tools">
                    {{-- ── Airline filter slider (results column width, aligned with filter header row) ── --}}
                    <div class="as-wrap" id="as-wrap">
                        <button class="as-arrow as-arrow--prev" id="as-prev" aria-label="Scroll left" disabled>
                            <i class="bx bx-chevron-left"></i>
                        </button>

                        <div class="as-viewport" id="as-viewport">
                            <div class="as-track" id="as-track">

                                {{-- All pill --}}
                                <button class="as-pill as-pill--active" data-as-code="all">
                                    <div class="as-pill__logo as-pill__logo--all">
                                        <i class="bx bxs-plane-take-off"></i>
                                    </div>
                                    <div class="as-pill__body">
                                        <span class="as-pill__nameline">All <span class="as-pill__cnt">({{ $itineraryCount }})</span></span>
                                        <span class="as-pill__price">All Airlines</span>
                                    </div>
                                </button>

                                @foreach($airlineMap as $_al)
                                <button class="as-pill" data-as-code="{{ $_al['code'] }}">
                                    <img class="as-pill__logo"
                                        src="{{ fl_carrier_logo($_al['code']) }}"
                                        loading="lazy"
                                        alt="{{ $_al['name'] }}"
                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($_al['code']) }}&background=cd1b4f&color=fff&size=80'">
                                    <div class="as-pill__body">
                                        <span class="as-pill__nameline">{{ $_al['name'] }} <span class="as-pill__cnt">({{ $_al['count'] }})</span></span>
                                        <span class="as-pill__price">{{ $currencyCode }} {{ number_format($_al['min_price'], 2) }}</span>
                                    </div>
                                </button>
                                @endforeach

                            </div>
                        </div>

                        <button class="as-arrow as-arrow--next" id="as-next" aria-label="Scroll right">
                            <i class="bx bx-chevron-right"></i>
                        </button>
                    </div>

                    <div class="rp-bar rp-bar--sort">
                        <div class="rp-bar__sort">
                            <span class="rp-bar__sortlabel">SORT BY:</span>
                            <div class="rp-sortrow">
                                <button type="button" class="rp-sortbtn" data-rp-sort-key="airline">Airline <i class="bx bx-up-arrow-alt rp-sortbtn__dir" aria-hidden="true"></i></button>
                                <button type="button" class="rp-sortbtn" data-rp-sort-key="departure">Departure <i class="bx bx-up-arrow-alt rp-sortbtn__dir" aria-hidden="true"></i></button>
                                <button type="button" class="rp-sortbtn" data-rp-sort-key="duration">Duration <i class="bx bx-up-arrow-alt rp-sortbtn__dir" aria-hidden="true"></i></button>
                                <button type="button" class="rp-sortbtn" data-rp-sort-key="best-value">Best Value <i class="bx bx-up-arrow-alt rp-sortbtn__dir" aria-hidden="true"></i></button>
                                <button type="button" class="rp-sortbtn" data-rp-sort-key="arrival">Arrival <i class="bx bx-up-arrow-alt rp-sortbtn__dir" aria-hidden="true"></i></button>
                                <button type="button" class="rp-sortbtn rp-sortbtn--active is-active" data-rp-sort-key="price">
                                    Price <i class="bx bx-up-arrow-alt rp-sortbtn__dir" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>{{-- /.rp-stack__tools --}}

                <div class="rp-stack__main rp-main">
                <div id="rp-list" data-rp-trip="{{ $isRoundTrip ? 'round_trip' : 'one_way' }}">
                    @foreach ($results as $result)
                        @php
                            $lid        = $result['id'];
                            $meta       = $result['listing_meta'] ?? [];
                            $legs       = $result['legs'] ?? [];
                            $firstSeg   = $legs[0]['segments'][0] ?? [];

                            $fareOptions = $result['fare_options'] ?? [[
                                'fare_brand' => trim((string) ($result['fare_brand'] ?? '')),
                                'totalPrice' => (float) ($result['totalPrice'] ?? ($meta['price'] ?? 0)),
                                'currency' => strtoupper((string) ($result['currency'] ?? $currencyCode)),
                                'non_refundable' => (bool) ($result['non_refundable'] ?? false),
                                'baggage_notes' => $result['baggage_notes'] ?? '',
                                'baggage_details' => $result['baggage_details'] ?? [],
                                'fare_rules' => $result['fare_rules'] ?? [],
                                'fare_tags' => $result['fare_tags'] ?? [],
                                'cabin_code' => $firstSeg['cabin_code'] ?? null,
                                'booking_code' => $firstSeg['booking_code'] ?? null,
                            ]];
                            $cardCur    = strtoupper((string) ($fareOptions[0]['currency'] ?? $result['currency'] ?? $currencyCode));
                            $totalPrice = (float) ($result['totalPrice'] ?? ($meta['price'] ?? 0));
                            $cardSupplier = strtolower((string) ($result['supplier'] ?? 'sabre'));

                            // Modal tab labels from search endpoints (not expanded segment endpoints).
                            $searchFrom = strtoupper((string) ($query['from'] ?? ''));
                            $searchTo = strtoupper((string) ($query['to'] ?? ''));
                            $legRoutes = [];
                            foreach ($legs as $li2 => $lg2) {
                                $routeFrom = $li2 === 0 ? $searchFrom : $searchTo;
                                $routeTo = $li2 === 0 ? $searchTo : $searchFrom;
                                $legRoutes[] = [
                                    'from' => $routeFrom !== '' ? $routeFrom : ($lg2['segments'][0]['from'] ?? ' - '),
                                    'to' => $routeTo !== '' ? $routeTo : ($lg2['segments'][array_key_last($lg2['segments'] ?? [])]['to'] ?? ' - '),
                                    'from_label' => resolveFlightCityLabel('', $routeFrom),
                                    'to_label' => resolveFlightCityLabel('', $routeTo),
                                    'label'=> $li2 === 0 ? 'Outbound' : 'Return',
                                    'icon' => $li2 === 0 ? 'bxs-plane-take-off' : 'bxs-plane-land',
                                ];
                            }
                        @endphp
                        @php
                            $_fls    = $legs[0]['segments'] ?? [];
                            $_flLast = !empty($_fls) ? $_fls[array_key_last($_fls)] : [];
                            $rpStops = max(0, count($_fls) - 1) + (int)array_sum(array_column($_fls, 'stop_count'));
                            $rpDepH  = isset($firstSeg['departure_clock']) ? (int)explode(':', $firstSeg['departure_clock'])[0] : -1;
                            $rpArrH  = isset($_flLast['arrival_clock'])    ? (int)explode(':', $_flLast['arrival_clock'])[0]    : -1;
                            $rpDur   = (int)($legs[0]['elapsedTime'] ?? 0);
                        @endphp

                        <div class="rc rc--has-provider-badge"
                             data-rp-id="{{ $lid }}"
                             data-rp-meta='@json($meta)'
                             data-rp-stops="{{ $rpStops }}"
                             data-rp-refund="{{ ($result['non_refundable'] ?? false) ? '0' : '1' }}"
                             data-rp-price="{{ $totalPrice }}"
                             data-rp-dep-h="{{ $rpDepH }}"
                             data-rp-arr-h="{{ $rpArrH }}"
                             data-rp-dur="{{ $rpDur }}"
                             data-rp-supplier="{{ $cardSupplier }}">

                            {{-- TODO: remove — temporary provider test strip --}}
                            <div class="rc__provider-strip rc__provider-strip--{{ $cardSupplier }}" title="Testing only — GDS provider">
                                <span class="rc__provider-strip__label">{{ $cardSupplier === 'travelport' ? 'Travelport' : 'Sabre' }}</span>
                            </div>

                            {{-- ── per-leg rows ── --}}
                            @foreach ($legs as $li => $leg)
                                @php
                                    $segs       = $leg['segments'] ?? [];
                                    $s0         = $segs[0] ?? [];
                                    $sLast      = [];
                                    if (!empty($segs)) {
                                        $lk    = array_key_last($segs);
                                        $sLast = is_array($segs[$lk] ?? null) ? $segs[$lk] : [];
                                    }
                                    $connCount  = max(0, count($segs) - 1);
                                    $techStops  = collect($segs)->sum(fn($s) => (int)($s['stop_count']??0));
                                    $stopsTotal = $connCount + $techStops;
                                    $dur        = fl_format_hm(isset($leg['elapsedTime']) ? (int)$leg['elapsedTime'] : null);
                                    $stopsLbl   = $stopsTotal === 0 ? 'Non stop'
                                                : ($stopsTotal === 1 ? '1 stop' : $stopsTotal.' stops');
                                    $depClock   = $s0['departure_clock'] ?? null;
                                    $isRedEye   = flightClockIsRedEye($depClock) || (bool) ($s0['is_red_eye_segment'] ?? false);
                                    $isMorning  = ! $isRedEye && flightClockIsMorning($depClock);
                                    $nextDay    = (bool)($sLast['next_day_hint'] ?? false);

                                    $midApts = [];
                                    for ($mi = 0; $mi < count($segs) - 1; $mi++) {
                                        $midApts[] = resolveFlightCityLabel('', $segs[$mi]['to'] ?? '');
                                    }
                                @endphp

                                <div class="rc__leg {{ $li === 0 ? 'rc__leg--first' : 'rc__leg--ret' }}">

                                    {{-- airline --}}
                                    <div class="rc__airline">
                                        <div class="rc__logo-wrap">
                                            <img class="rc__logo"
                                                src="{{ fl_carrier_logo($s0['carrier'] ?? '') }}"
                                                loading="lazy" alt="{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}"
                                                onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($s0['carrier']??'FL') }}&background=cd1b4f&color=fff&size=100'">
                                        </div>
                                        <div>
                                            <div class="rc__aname">{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}</div>
                                            <div class="rc__aflight">{{ strtoupper((string)($s0['carrier']??'')) }}{{ $s0['flight_number']??'' }}</div>
                                        </div>
                                    </div>

                                    {{-- departure --}}
                                    <div class="rc__point">
                                        <div class="rc__time">
                                            {{ formatFlightClock($s0['departure_clock'] ?? null) }}
                                            @if ($isRedEye)
                                                <i class="bx bxs-moon rc__time-icon rc__time-icon--moon" title="Night departure"></i>
                                            @elseif ($isMorning)
                                                <i class="bx bxs-sun rc__time-icon rc__time-icon--sun" title="Morning departure"></i>
                                            @endif
                                        </div>
                                        <div class="rc__dt">{{ $s0['departure_weekday']??'' }}, {{ $s0['departure_label']??'' }}</div>
                                        <div class="rc__city">
                                            {{ fl_city_label($s0, true) }}@if(!empty($s0['departure_terminal'])), Terminal {{ $s0['departure_terminal'] }}@endif
                                        </div>
                                    </div>

                                    {{-- bridge --}}
                                    <div class="rc__bridge">
                                        <div class="rc__btime">{{ $dur }}</div>
                                        <div class="rc__btrack">
                                            <span class="rc__bdot"></span>
                                            @foreach($midApts as $ma)
                                                <span class="rc__bvia">{{ $ma }}</span>
                                            @endforeach
                                            <span class="rc__bline"></span>
                                            <span class="rc__bdot"></span>
                                        </div>
                                        <div class="rc__bstop {{ $stopsTotal===0?'rc__bstop--direct':'rc__bstop--via' }}">
                                            {{ $stopsLbl }}
                                        </div>
                                    </div>

                                    {{-- arrival --}}
                                    <div class="rc__point rc__point--arr">
                                        <div class="rc__time">
                                            {{ formatFlightClock($sLast['arrival_clock'] ?? null) }}
                                            @if($nextDay)<span class="rc__nextday">Next Day</span>@endif
                                        </div>
                                        <div class="rc__dt">{{ $sLast['arrival_weekday']??'' }}, {{ $sLast['arrival_label']??'' }}</div>
                                        <div class="rc__city">
                                            {{ fl_city_label($sLast, false) }}@if(!empty($sLast['arrival_terminal'])), Terminal {{ $sLast['arrival_terminal'] }}@endif
                                        </div>
                                    </div>

                                    {{-- time-of-day col --}}
                                    <div class="rc__redeyecol">
                                        @if ($isRedEye)
                                            <i class="bx bxs-moon rc__time-icon rc__time-icon--moon" title="Night departure"></i>
                                        @elseif ($isMorning)
                                            <i class="bx bxs-sun rc__time-icon rc__time-icon--sun" title="Morning departure"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- ── centered flight-details link ── --}}
                            <div class="rc__details-center">
                                <button type="button" class="rc__details-link" data-fd-open="fd-{{ $lid }}">
                                    <i class="bx bx-info-circle"></i>
                                    Flight Details
                                    <i class="bx bx-chevron-down"></i>
                                </button>
                            </div>

                            {{-- ── fare rows (first visible, rest expandable) ── --}}
                            @php
                                $defaultVisibleFares = 3;
                                $extraFareCount = max(0, count($fareOptions) - $defaultVisibleFares);
                            @endphp
                            <div class="rc__fares" data-rc-fares>
                                @foreach ($fareOptions as $fi => $fare)
                                    @php
                                        $fareRulesRow = $fare['fare_rules'] ?? [];
                                        $nonRefund  = array_key_exists('refundable', $fareRulesRow)
                                            ? ! (bool) $fareRulesRow['refundable']
                                            : (bool) ($fare['non_refundable'] ?? false);
                                        $bagNote    = $fare['baggage_notes'] ?? '';
                                        $farePrice  = (float) ($fare['totalPrice'] ?? 0);
                                        $fareCur    = strtoupper((string) ($fare['currency'] ?? $cardCur));
                                        $fareLegRows = flightFareLegDisplayRows(
                                            $fare,
                                            $legs,
                                            $isRoundTrip,
                                            $query['from'] ?? '',
                                            $query['to'] ?? '',
                                            $nonRefund,
                                            $bagNote,
                                        );
                                    @endphp
                                    <div class="rc__fare {{ $fi >= $defaultVisibleFares && $extraFareCount > 0 ? 'rc__fare--collapsed' : '' }}"
                                        data-rc-fare-row="{{ $fi }}">
                                        <div class="rc__fare-left {{ $isRoundTrip && count($fareLegRows) > 1 ? 'rc__fare-left--split' : '' }}">
                                            @if($isRoundTrip && count($fareLegRows) > 1)
                                                <div class="rc__fare-leg-tags" aria-hidden="true">
                                                    @foreach($fareLegRows as $legRow)
                                                        @if(($legRow['tag'] ?? '') !== '')
                                                            <span class="rc__leg-tag rc__leg-tag--{{ strtolower($legRow['tag']) }}" title="{{ $legRow['tag_title'] ?? '' }}">{{ $legRow['tag'] }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="rc__fare-lines">
                                                @foreach($fareLegRows as $legIndex => $legRow)
                                                    <div class="rc__fare-line {{ $legIndex > 0 ? 'rc__fare-line--return' : '' }}">
                                                        @include('user.flights.partials.fare-line-pills', ['legRow' => array_merge($legRow, ['tag' => $isRoundTrip && count($fareLegRows) > 1 ? '' : ($legRow['tag'] ?? '')])])
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="rc__fare-right">
                                            <div class="rc__price">
                                                <div class="rc__price-label">NET FARE</div>
                                                <div class="rc__price-amount">
                                                    @if($fareCur === 'AED')
                                                        <span class="dirham">AED</span>
                                                    @else
                                                        <span class="rc__price-cur">{{ $fareCur }}</span>
                                                    @endif
                                                    {{ number_format($farePrice, 2) }}
                                                </div>
                                            </div>
                                            <button type="button"
                                                class="rc__details-mini"
                                                data-fd-open="fd-{{ $lid }}"
                                                data-fd-open-tab="baggage"
                                                data-fd-fare="{{ $fi }}">
                                                Details
                                            </button>
                                            <a href="{{ route('user.flights.hold', ['itinerary' => $lid, 'fare' => $fi] + $query) }}"
                                                class="rc__hold">
                                                <i class="bx bx-time-five"></i> Hold
                                            </a>
                                            <a href="{{ route('user.flights.checkout', ['itinerary' => $lid, 'fare' => $fi] + $query) }}"
                                                class="rc__cta">
                                                Book Now <i class="bx bx-right-arrow-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach

                                @if($extraFareCount > 0)
                                    <button type="button"
                                        class="rc__more-fares"
                                        data-rc-more-fares
                                        aria-expanded="false">
                                        <span class="rc__more-fares__label">+{{ $extraFareCount }} More Fare{{ $extraFareCount === 1 ? '' : 's' }}</span>
                                        <i class="bx bx-chevron-down"></i>
                                    </button>
                                @endif
                            </div>

                        </div>{{-- /.rc --}}

                        {{-- ── FLIGHT DETAILS MODAL (fixed, escapes overflow) ── --}}
                        <div class="fd-modal" id="fd-{{ $lid }}" hidden aria-modal="true" role="dialog">
                            <div class="fd-backdrop" data-fd-close="fd-{{ $lid }}"></div>
                            <div class="fd-box">

                                <div class="fd-head">
                                    <div class="fd-head__title">
                                        <i class="bx bxs-plane"></i>
                                        {{ ($legRoutes[0]['from_label'] ?? $legRoutes[0]['from']) }} → {{ ($legRoutes[0]['to_label'] ?? $legRoutes[0]['to']) }}
                                        @if(count($legRoutes) > 1)
                                            ⇄ {{ ($legRoutes[1]['from_label'] ?? $legRoutes[1]['from']) }} → {{ ($legRoutes[1]['to_label'] ?? $legRoutes[1]['to']) }}
                                        @endif
                                        <span class="fd-head__sub">· Flight Details</span>
                                    </div>
                                    <button class="fd-close" data-fd-close="fd-{{ $lid }}" title="Close">
                                        <i class="bx bx-x"></i>
                                    </button>
                                </div>

                                {{-- tabs --}}
                                <div class="fd-tabs">
                                    @foreach($legRoutes as $ti => $lr)
                                        <button class="fd-tab {{ $ti === 0 ? 'fd-tab--active' : '' }}"
                                            data-fd-tab="{{ $ti }}">
                                            <i class="bx {{ $lr['icon'] }}"></i>
                                            {{ $lr['label'] }}
                                            <span class="fd-tab__route">{{ ($lr['from_label'] ?? $lr['from']) }} → {{ ($lr['to_label'] ?? $lr['to']) }}</span>
                                        </button>
                                    @endforeach
                                    <button class="fd-tab" data-fd-tab="baggage">
                                        <i class="bx bx-briefcase-alt-2"></i>
                                        Baggage
                                    </button>
                                    <button class="fd-tab" data-fd-tab="fare-rules">
                                        <i class="bx bx-receipt"></i>
                                        Fare Rules
                                    </button>
                                </div>

                                {{-- body --}}
                                <div class="fd-body">

                                    {{-- leg panels --}}
                                    @foreach($legs as $li => $leg)
                                        @php $segs = $leg['segments'] ?? []; @endphp
                                        <div class="fd-panel {{ $li === 0 ? '' : 'fd-panel--hidden' }}" data-fd-panel="{{ $li }}">
                                            @foreach($segs as $sIdx => $sx)
                                                @php
                                                    $sxMins  = fl_segment_minutes($sx);
                                                @endphp

                                                <div class="fd-seg">
                                                    <div class="fd-seg__air">
                                                        <img class="fd-seg__logo"
                                                            src="{{ fl_carrier_logo($sx['carrier']??'') }}"
                                                            loading="lazy" alt=""
                                                            onerror="this.style.visibility='hidden'">
                                                        <div>
                                                            <div class="fd-seg__aname">{{ $sx['carrier_display']??($sx['carrier']??'') }}</div>
                                                            <div class="fd-seg__ameta">
                                                                {{ strtoupper((string)($sx['carrier']??'')) }}{{ $sx['flight_number']??'' }}
                                                                @if(($sx['operating_carrier']??'') && ($sx['operating_carrier']??'') !== ($sx['carrier']??''))
                                                                    · Operated by {{ $sx['operating_carrier'] }}{{ $sx['operating_flight_number']??'' }}
                                                                @endif
                                                            </div>
                                                            @php $aircraftLabel = formatFlightAircraftLabel($sx); @endphp
                                                            @if($aircraftLabel !== '')
                                                                <div class="fd-seg__aircraft">
                                                                    <span class="fd-seg__aircraft-label">Aircraft</span>
                                                                    <span class="fd-seg__aircraft-name">{{ $aircraftLabel }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="fd-seg__chips">
                                                            @php
                                                                $segCabinTags = flightFareRowCabinLabels($sx['cabin_code'] ?? null, $sx['booking_code'] ?? null);
                                                            @endphp
                                                            @if($segCabinTags['cabin'] !== '')
                                                                <span class="fd-chip fd-chip--cab">{{ $segCabinTags['cabin'] }}</span>
                                                            @endif
                                                            @if($segCabinTags['booking'] !== '')
                                                                <span class="fd-chip">{{ $segCabinTags['booking'] }}</span>
                                                            @endif
                                                            @if(isset($sx['seats_available']))<span class="fd-chip fd-chip--seat"><i class="bx bx-group"></i> {{ (int)$sx['seats_available'] }} left</span>@endif
                                                        </div>
                                                    </div>

                                                    <div class="fd-seg__route">
                                                        <div class="fd-seg__pt">
                                                            <strong class="fd-seg__time">{{ formatFlightClock($sx['departure_clock'] ?? null) }}</strong>
                                                            <span class="fd-seg__code">{{ $sx['from']??'' }}</span>
                                                            <span class="fd-seg__city">
                                                                {{ resolveFlightCityLabel($sx['departure_city'] ?? '', $sx['from'] ?? '') }}
                                                                @if(!empty($sx['departure_terminal']))<small>Terminal {{ $sx['departure_terminal'] }}</small>@endif
                                                            </span>
                                                            <span class="fd-seg__date">{{ $sx['departure_label']??'' }}</span>
                                                        </div>

                                                        <div class="fd-seg__mid">
                                                            <span class="fd-seg__dur">{{ fl_format_hm($sxMins) }}</span>
                                                            <div class="fd-seg__track">
                                                                <span class="fd-seg__dot"></span>
                                                                <span class="fd-seg__line"></span>
                                                                <i class="bx bxs-plane fd-seg__plane"></i>
                                                                <span class="fd-seg__dot"></span>
                                                            </div>
                                                            @if((int)($sx['stop_count']??0)>0)
                                                                <span class="fd-seg__techstop">{{ (int)$sx['stop_count'] }} technical stop</span>
                                                            @else
                                                                <span class="fd-seg__nonstop">Non-stop</span>
                                                            @endif
                                                        </div>

                                                        <div class="fd-seg__pt fd-seg__pt--arr">
                                                            <strong class="fd-seg__time">{{ formatFlightClock($sx['arrival_clock'] ?? null) }}</strong>
                                                            <span class="fd-seg__code">{{ $sx['to']??'' }}</span>
                                                            <span class="fd-seg__city">
                                                                {{ resolveFlightCityLabel($sx['arrival_city'] ?? '', $sx['to'] ?? '') }}
                                                                @if(!empty($sx['arrival_terminal']))<small>Terminal {{ $sx['arrival_terminal'] }}</small>@endif
                                                            </span>
                                                            <span class="fd-seg__date">{{ $sx['arrival_label']??'' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    @if(count($fareOptions) > 1)
                                        <div class="fd-fare-tabs" hidden>
                                            @foreach($fareOptions as $fi => $fare)
                                                @php $tabBrand = trim((string) ($fare['fare_brand'] ?? ('Fare ' . ($fi + 1)))); @endphp
                                                <button type="button"
                                                    class="fd-fare-tab {{ $fi === 0 ? 'fd-fare-tab--active' : '' }}"
                                                    data-fd-fare-tab="{{ $fi }}">
                                                    {{ $tabBrand }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @foreach($fareOptions as $fi => $fare)
                                        @php
                                            $bagDetails = $fare['baggage_details'] ?? [];
                                            $fareRules  = $fare['fare_rules'] ?? [];
                                            $nonRefund  = (bool) ($fare['non_refundable'] ?? false);
                                            $fareBrand  = trim((string) ($fare['fare_brand'] ?? ''));
                                            $bagNote    = $fare['baggage_notes'] ?? '';
                                            $farePrice  = (float) ($fare['totalPrice'] ?? 0);
                                            $fareCur    = strtoupper((string) ($fare['currency'] ?? $cardCur));
                                            $checkedRows = $bagDetails['checked'] ?? [];
                                            $cabinRows = $bagDetails['cabin'] ?? [];
                                            $paxTable = $bagDetails['pax_table'] ?? [];
                                            $cabinNotes = $bagDetails['cabin_notes'] ?? [];
                                        @endphp

                                        {{-- baggage panel --}}
                                        <div class="fd-panel fd-panel--hidden fd-fare-panel {{ $fi === 0 ? 'fd-fare-panel--active' : '' }}"
                                            data-fd-panel="baggage"
                                            data-fd-fare-panel="{{ $fi }}">
                                            <div class="fd-bag">
                                                @if(!empty($paxTable))
                                                    <div class="fd-bag__section">
                                                        <div class="fd-bag__section-title">Baggage Allowance</div>
                                                        <div class="fd-bag__table-wrap">
                                                            <table class="fd-bag__table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Pax Type</th>
                                                                        <th>Check-in Baggage</th>
                                                                        <th>Cabin Baggage</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($paxTable as $paxRow)
                                                                        <tr>
                                                                            <td>{{ $paxRow['pax_type'] ?? 'Passenger' }}</td>
                                                                            <td>
                                                                                @if(!empty($paxRow['checked_items']) && is_array($paxRow['checked_items']))
                                                                                    @php $shownChecked = 0; @endphp
                                                                                    @foreach($paxRow['checked_items'] as $checkedItem)
                                                                                        @php $checkedAmount = trim((string) ($checkedItem['amount'] ?? '')); @endphp
                                                                                        @if($checkedAmount !== '' && strcasecmp($checkedAmount, 'Not included') !== 0)
                                                                                            @if($shownChecked > 0)<span class="fd-bag__sep"> · </span>@endif
                                                                                            <span class="fd-bag__amt">{{ $checkedAmount }}</span>
                                                                                            @php $shownChecked++; @endphp
                                                                                        @endif
                                                                                    @endforeach
                                                                                    @if($shownChecked === 0)
                                                                                        @include('user.flights.partials.baggage-allowance-display', [
                                                                                            'friendly' => $paxRow['checked_friendly'] ?? null,
                                                                                            'fallback' => $paxRow['checked'] ?? 'Not included',
                                                                                        ])
                                                                                    @endif
                                                                                @else
                                                                                    @include('user.flights.partials.baggage-allowance-display', [
                                                                                        'friendly' => $paxRow['checked_friendly'] ?? null,
                                                                                        'fallback' => $paxRow['checked'] ?? 'Not included',
                                                                                    ])
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @if(!empty($paxRow['cabin_items']) && is_array($paxRow['cabin_items']))
                                                                                    @php $shownCabin = 0; @endphp
                                                                                    @foreach($paxRow['cabin_items'] as $cabinItem)
                                                                                        @php $cabinAmount = trim((string) ($cabinItem['amount'] ?? '')); @endphp
                                                                                        @if($cabinAmount !== '' && strcasecmp($cabinAmount, 'Not included') !== 0)
                                                                                            @if($shownCabin > 0)<span class="fd-bag__sep"> · </span>@endif
                                                                                            <span class="fd-bag__amt">{{ $cabinAmount }}</span>
                                                                                            @php $shownCabin++; @endphp
                                                                                        @endif
                                                                                    @endforeach
                                                                                    @if($shownCabin === 0)
                                                                                        @include('user.flights.partials.baggage-allowance-display', [
                                                                                            'friendly' => $paxRow['cabin_friendly'] ?? null,
                                                                                            'fallback' => $paxRow['cabin'] ?? 'Not included',
                                                                                        ])
                                                                                    @endif
                                                                                @else
                                                                                    @include('user.flights.partials.baggage-allowance-display', [
                                                                                        'friendly' => $paxRow['cabin_friendly'] ?? null,
                                                                                        'fallback' => $paxRow['cabin'] ?? 'Not included',
                                                                                    ])
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        @if(!empty($cabinNotes))
                                                            <div class="fd-bag__footnote">
                                                                <i class="bx bx-info-circle"></i>
                                                                <span>{{ implode(' · ', $cabinNotes) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if(!empty($checkedRows))
                                                    <div class="fd-bag__section">
                                                        <div class="fd-bag__section-title">Checked Baggage</div>
                                                        @foreach($checkedRows as $bagRow)
                                                            <div class="fd-bag__row">
                                                                <i class="bx bx-briefcase-alt-2 fd-bag__icon"></i>
                                                                <div>
                                                                    <div class="fd-bag__label">{{ $bagRow['route'] ?? 'Segment' }}</div>
                                                                    <div class="fd-bag__val">
                                                                        @include('user.flights.partials.baggage-allowance-display', [
                                                                            'friendly' => $bagRow['friendly'] ?? null,
                                                                            'fallback' => $bagRow['allowance'] ?? 'Not included',
                                                                        ])
                                                                        @if(!empty($bagRow['airline']))
                                                                            <span class="fd-bag__meta">· {{ $bagRow['airline'] }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif(!empty($bagNote))
                                                    <div class="fd-bag__row">
                                                        <i class="bx bx-briefcase-alt-2 fd-bag__icon"></i>
                                                        <div>
                                                            <div class="fd-bag__label">Checked Baggage</div>
                                                            <div class="fd-bag__val">{{ $bagNote }}</div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if(!empty($cabinRows))
                                                    <div class="fd-bag__section">
                                                        <div class="fd-bag__section-title">Cabin / Hand Baggage</div>
                                                        @foreach($cabinRows as $bagRow)
                                                            <div class="fd-bag__row">
                                                                <i class="bx bx-shopping-bag fd-bag__icon"></i>
                                                                <div>
                                                                    <div class="fd-bag__label">{{ $bagRow['route'] ?? 'Segment' }}</div>
                                                                    <div class="fd-bag__val">
                                                                        @include('user.flights.partials.baggage-allowance-display', [
                                                                            'friendly' => $bagRow['friendly'] ?? null,
                                                                            'fallback' => $bagRow['allowance'] ?? 'Not included',
                                                                        ])
                                                                        @if(!empty($bagRow['airline']))
                                                                            <span class="fd-bag__meta">· {{ $bagRow['airline'] }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- fare rules panel --}}
                                        <div class="fd-panel fd-panel--hidden fd-fare-panel {{ $fi === 0 ? 'fd-fare-panel--active' : '' }}"
                                            data-fd-panel="fare-rules"
                                            data-fd-fare-panel="{{ $fi }}">
                                            <div class="fd-rules">
                                                @include('user.flights.partials.fare-rules-summary', [
                                                    'fareRules' => $fareRules,
                                                    'fareBrand' => $fareBrand,
                                                    'nonRefund' => $nonRefund,
                                                    'routeLabel' => ($legRoutes[0]['from'] ?? '') . ' → ' . ($legRoutes[0]['to'] ?? ''),
                                                ])
                                                @include('user.flights.partials.fare-rules-full')
                                            </div>
                                        </div>
                                    @endforeach

                                </div>{{-- /.fd-body --}}

                                <div class="fd-foot-wrap">
                                    @foreach($fareOptions as $fi => $fare)
                                        @php
                                            $farePrice  = (float) ($fare['totalPrice'] ?? 0);
                                            $fareCur    = strtoupper((string) ($fare['currency'] ?? $cardCur));
                                        @endphp
                                        <div class="fd-foot fd-fare-foot {{ $fi === 0 ? 'fd-fare-foot--active' : '' }}"
                                            data-fd-fare-foot="{{ $fi }}">
                                            <div class="fd-foot__price">
                                                <span class="fd-foot__label">Total</span>
                                                <span class="fd-foot__amount">
                                                    @if($fareCur === 'AED')
                                                        <span class="dirham">AED</span>
                                                    @else
                                                        {{ $fareCur }}
                                                    @endif
                                                    {{ number_format($farePrice, 2) }}
                                                </span>
                                            </div>
                                            <div class="fd-foot__btns">
                                                <a href="{{ route('user.flights.hold', ['itinerary' => $lid, 'fare' => $fi] + $query) }}"
                                                    class="fd-foot__hold">
                                                    <i class="bx bx-time-five"></i> Hold
                                                </a>
                                                <a href="{{ route('user.flights.checkout', ['itinerary' => $lid, 'fare' => $fi] + $query) }}"
                                                    class="fd-foot__book">
                                                    Book Now <i class="bx bx-right-arrow-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>{{-- /.fd-modal --}}

                    @endforeach
                </div>{{-- /#rp-list --}}
                </div>{{-- /.rp-stack__main --}}
                </div>{{-- /.rp-stack__body --}}
                </div>{{-- /.rp-stack --}}

            @elseif($hasSearch && empty($results))
                <div class="rp-empty">
                    <i class="bx bx-search-alt-2 rp-empty__icon"></i>
                    <h3>No flights found</h3>
                    <p>We couldn't find itineraries for this route. Try different dates or nearby airports.</p>
                    @if (!empty($messages))
                        <ul class="rp-empty__msgs">
                            @foreach ($messages as $message)
                                @if (in_array(strtolower((string) ($message['severity'] ?? '')), ['error', 'warning'], true))
                                    <li>{{ $message['text'] ?? '' }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('user.flights.index') }}" class="rp-emptybtn">Modify Search</a>
                </div>
            @else
                <div class="rp-empty">
                    <i class="bx bxs-plane-take-off rp-empty__icon rp-empty__icon--idle"></i>
                    <h3>Ready when you are</h3>
                    <p>Choose your route and dates above to see live Sabre inventory.</p>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('css')
<style>
/* =========================================================
   TOKENS
   ========================================================= */
.rp {
    --c-brand:       #cd1b4f;
    --c-brand2:      #a8173f;
    --c-brand-soft:  #fdeef3;
    --c-ink:         #1a2540;
    --c-slate:       #4a5568;
    --c-muted:       #8492a6;
    --c-line:        #dde3ef;
    --c-line-inner:  #e8ecf4;
    --c-line-strong: #b9c3d6;
    --c-bg:          #ffffff;
    --c-white:       #ffffff;
    --c-green:       #0f9d58;
    --c-green-soft:  #e8f9f1;
    --c-amber:       #d97706;
    --c-amber-soft:  #fef3c7;
    --c-blue:        #2563eb;
    --c-blue-soft:   #eff6ff;
    --c-violet:      #7c3aed;
    --c-violet-soft: #ede9fe;
    --c-shadow:      none;
    --c-shadow-hov:  none;
    --mono: "JetBrains Mono", ui-monospace, Menlo, monospace;
    --sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    font-family: var(--sans);
    color: var(--c-ink);
    background: var(--c-bg);
    padding-bottom: 3rem;
}
.rp * { box-sizing: border-box; }
.rp a { text-decoration: none; }

/* =========================================================
   SEARCH WRAP
   ========================================================= */
.rp-swrap { background: #fff; }
.rp-shell { padding-top: 2rem; }

.rp-listing-prelude {
    --rp-prelude-gap: 0.5rem;
    margin-bottom: var(--rp-prelude-gap);
}
.rp-listing-prelude__count-wrap {
    padding: .45rem 0 .35rem;
}
.rp-advisory {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: .85rem;
    align-items: start;
    padding: .85rem 1rem .85rem .95rem;
    border-radius: 12px;
    border: 1px solid rgba(205, 27, 79, .14);
    background:
        linear-gradient(135deg, rgba(253, 238, 243, .95) 0%, rgba(255, 255, 255, .98) 58%),
        var(--c-white);
    box-shadow: inset 3px 0 0 var(--c-brand);
}
.rp-advisory__mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 10px;
    background: var(--c-brand-soft);
    color: var(--c-brand);
    flex-shrink: 0;
}
.rp-advisory__mark i {
    font-size: 1.25rem;
    line-height: 1;
}
.rp-advisory__body {
    min-width: 0;
}
.rp-advisory__tag {
    display: inline-block;
    margin-bottom: .28rem;
    padding: .12rem .45rem;
    border-radius: 999px;
    background: rgba(205, 27, 79, .08);
    color: var(--c-brand2);
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.rp-advisory__title {
    margin: 0 0 .2rem;
    font-size: .84rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--c-ink);
}
.rp-advisory__text {
    margin: 0;
    font-size: .78rem;
    line-height: 1.5;
    color: var(--c-slate);
}

/* =========================================================
   TOOLBAR
   ========================================================= */
.rp-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .65rem 1rem;
    margin-bottom: .85rem;
    padding: .45rem 0;
    background: transparent;
    border: none;
}
.rp-bar.rp-bar--sort {
    justify-content: flex-end;
}
.rp-bar__count { font-size: .82rem; color: var(--c-slate); }
.rp-bar__count strong { color: var(--c-ink); font-weight: 700; }
.rp-bar__sort { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
.rp-bar__sortlabel {
    font-size: .65rem; font-weight: 700; letter-spacing: .12em;
    color: var(--c-muted); text-transform: uppercase;
}
.rp-sortrow { display: flex; gap: .2rem; flex-wrap: wrap; }
.rp-sortbtn {
    border: none; background: transparent; font: inherit;
    font-size: .78rem; font-weight: 600; color: var(--c-slate);
    padding: .3rem .65rem; border-radius: 6px; cursor: pointer;
    display: inline-flex; align-items: center; gap: .2rem;
    transition: background .12s, color .12s;
}
.rp-sortbtn:hover { background: var(--c-bg); color: var(--c-ink); }
.rp-sortbtn--active, .rp-sortbtn.is-active { background: var(--c-brand); color: #fff; }
.rp-sortbtn__dir { display: none; font-size: .95em; transition: transform .2s ease; }
.rp-sortbtn.is-active .rp-sortbtn__dir,
.rp-sortbtn.rp-sortbtn--active .rp-sortbtn__dir { display: inline-block; }
.rp-sortbtn.is-desc .rp-sortbtn__dir { transform: rotate(180deg); }

/* =========================================================
   CARD
   ========================================================= */
#rp-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: .35rem .35rem .75rem;
    margin: 0 -.35rem;
    border-radius: 16px;
}

.rc {
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 16px;
    overflow: hidden;
    transition: border-color .15s ease, box-shadow .15s ease;
    box-shadow: 0 0 15px 1px #00000020;
}
.rc:hover {
    border-color: rgba(205, 27, 79, 0.22);
    box-shadow: 0 2px 8px rgba(26, 37, 64, 0.055);
}

/* TODO: remove — temporary provider test strip (corner ribbon) */
.rc--has-provider-badge { position: relative; }
.rc__provider-strip {
    position: absolute;
    top: 0;
    right: 0;
    width: 5.25rem;
    height: 5.25rem;
    overflow: hidden;
    z-index: 3;
    pointer-events: none;
}
.rc__provider-strip__label {
    position: absolute;
    display: block;
    width: 7.25rem;
    padding: .26rem 0;
    right: -1.85rem;
    top: 1rem;
    transform: rotate(45deg);
    text-align: center;
    font-size: .56rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    line-height: 1;
    box-shadow: 0 2px 6px rgba(15, 23, 42, .12);
}
.rc__provider-strip--sabre .rc__provider-strip__label {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #fff;
}
.rc__provider-strip--travelport .rc__provider-strip__label {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    color: #fff;
}

.rc__hold--disabled,
.rc__cta--disabled,
.fd-foot__hold--disabled,
.fd-foot__book--disabled {
    opacity: .55;
    cursor: not-allowed;
    pointer-events: none;
}

/* leg row */
.rc__leg {
    display: grid;
    grid-template-columns: 165px 1fr 160px 1fr 38px;
    align-items: center;
    gap: .35rem .75rem;
    padding: .55rem .85rem;
    border-bottom: 1px solid var(--c-line-inner);
}
.rc__leg--ret { background: #fafbfd; }

/* airline */
.rc__airline { display: flex; gap: .5rem; align-items: center; min-width: 0; }

.rc__logo-wrap {
    width: 44px; height: 44px; flex-shrink: 0;
    border-radius: 10px;
    border: 1.5px solid var(--c-line);
    background: #fff;
    box-shadow: 0 1px 3px rgba(26, 37, 64, 0.05);
    display: flex; align-items: center; justify-content: center;
    padding: 3px;
    overflow: hidden;
}
.rc__logo { width: 100%; height: 100%; object-fit: contain; display: block; }

.rc__aname {
    font-size: .8rem; font-weight: 700; color: var(--c-ink);
    line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__aflight { font-family: var(--mono); font-size: .66rem; color: var(--c-muted); margin-top: .04rem; }

/* dep / arr */
.rc__point { min-width: 0; }
.rc__point--arr { text-align: right; }
.rc__time {
    font-family: var(--mono); font-size: 1.12rem; font-weight: 700;
    color: var(--c-ink); line-height: 1;
    display: inline-flex; align-items: center; gap: .35rem;
}
.rc__time-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.45rem;
    height: 1.45rem;
    border-radius: 50%;
    font-size: 1rem;
    line-height: 1;
    flex-shrink: 0;
}
.rc__time-icon--sun {
    background: #fef3c7;
    color: #d97706;
    box-shadow: 0 0 0 1px rgba(217, 119, 6, .18);
}
.rc__time-icon--moon {
    background: #e0e7ff;
    color: #4338ca;
    box-shadow: 0 0 0 1px rgba(67, 56, 202, .16);
}
.rc__redeyecol .rc__time-icon {
    width: 1.75rem;
    height: 1.75rem;
    font-size: 1.2rem;
}
.rc__nextday {
    font-size: .58rem; font-weight: 700;
    background: var(--c-amber-soft); color: var(--c-amber);
    padding: .05rem .3rem; border-radius: 4px; margin-left: .2rem;
    letter-spacing: .04em; text-transform: uppercase; font-family: var(--sans);
}
.rc__dt { font-size: .68rem; color: var(--c-muted); margin-top: .12rem; white-space: nowrap; }
.rc__city {
    font-size: .7rem; color: var(--c-slate); font-weight: 500; margin-top: .02rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* bridge */
.rc__bridge { display: flex; flex-direction: column; align-items: center; gap: .22rem; }
.rc__btime { font-size: .7rem; font-weight: 600; color: var(--c-slate); font-family: var(--mono); }
.rc__btrack { width: 100%; display: flex; align-items: center; gap: .2rem; }
.rc__bline { flex: 1; height: 1px; background: var(--c-muted); opacity: .35; }
.rc__bdot { width: 6px; height: 6px; border-radius: 50%; background: var(--c-brand); flex-shrink: 0; }
.rc__bvia {
    font-family: var(--mono); font-size: .58rem; font-weight: 700;
    color: #fff; background: var(--c-amber); padding: .08rem .32rem;
    border-radius: 4px; flex-shrink: 0;
}
.rc__bstop {
    font-size: .63rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; padding: .1rem .42rem; border-radius: 4px;
}
.rc__bstop--direct { background: var(--c-green-soft); color: var(--c-green); }
.rc__bstop--via    { background: var(--c-amber-soft); color: var(--c-amber); }

/* time-of-day col */
.rc__redeyecol {
    display: flex;
    align-items: center;
    justify-content: center;
    align-self: center;
}

/* fare row */
.rc__fares { border-top: 1px solid var(--c-line-inner); }
.rc__fare {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;
    gap: .45rem .75rem;
    padding: .38rem .85rem;
    border-top: 1px solid var(--c-line-inner);
}
.rc__fares .rc__fare:first-child { border-top: none; }
.rc__fare--collapsed { display: none; }
.rc__fares.is-expanded .rc__fare--collapsed {
    display: flex;
    border-top: 1px dashed var(--c-line-inner);
}
.rc__more-fares {
    display: flex; align-items: center; justify-content: center; gap: .3rem;
    width: 100%; border: none; background: var(--c-blue-soft);
    color: var(--c-blue); font: inherit; font-size: .72rem; font-weight: 700;
    padding: .38rem .75rem; cursor: pointer;
    border-top: 1px solid var(--c-line-inner);
    transition: background .14s, color .14s;
}
.rc__more-fares:hover { background: #dbeafe; color: #1d4ed8; }
.rc__more-fares i {
    font-size: 1rem;
    transition: transform .18s ease;
}
.rc__fares.is-expanded .rc__more-fares i { transform: rotate(180deg); }
.rc__fare-left {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .1rem;
    flex: 1 1 auto;
    min-width: 0;
}
.rc__fare-left--split {
    flex-direction: row;
    align-items: stretch;
    gap: .32rem;
}
.rc__fare-leg-tags {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: .16rem;
    flex-shrink: 0;
    padding: .02rem 0;
}
.rc__fare-lines {
    display: flex;
    flex-direction: column;
    gap: .1rem;
    flex: 1 1 auto;
    min-width: 0;
}
.rc__fare-line {
    display: flex;
    align-items: center;
    gap: .22rem;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
    min-height: 1rem;
}
.rc__fare-line--return {
    padding-left: 0;
}
.rc__leg-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.28rem;
    height: 1.12rem;
    padding: 0 .24rem;
    border-radius: 3px;
    border: 1px solid var(--c-line);
    background: #f8fafc;
    color: var(--c-slate);
    font-size: .52rem;
    font-weight: 800;
    letter-spacing: .04em;
    line-height: 1;
    flex-shrink: 0;
}
.rc__leg-tag--ow {
    background: var(--c-brand-soft);
    color: var(--c-brand);
    border-color: rgba(205, 27, 79, .2);
}
.rc__leg-tag--rt {
    background: var(--c-blue-soft);
    color: var(--c-blue);
    border-color: rgba(37, 99, 235, .18);
}
.rc__fare-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .38rem;
    flex: 0 0 auto;
    margin-left: auto;
    flex-wrap: nowrap;
}
.rc__fare-left .rc__ftag {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rc__details-mini {
    border: 1px solid var(--c-line); background: #fff; color: var(--c-slate);
    font: inherit; font-size: .64rem; font-weight: 700; border-radius: 999px;
    padding: .2rem .52rem; cursor: pointer;
}
.rc__details-mini:hover { border-color: var(--c-brand); color: var(--c-brand); }

.rc__fbadge {
    font-size: .58rem; font-weight: 700; padding: .12rem .42rem;
    border-radius: 4px; text-transform: uppercase; letter-spacing: .06em;
}
.rc__fbadge--tick {
    min-width: 1.05rem;
    height: 1.05rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .62rem;
    letter-spacing: 0;
    cursor: help;
}
.tooltip.rc-fare-tip {
    z-index: 1085;
    opacity: 1 !important;
}
.tooltip.rc-fare-tip .tooltip-inner {
    background-color: #1a2540 !important;
    color: #ffffff !important;
    font-size: .68rem;
    font-weight: 600;
    line-height: 1.3;
    padding: .32rem .58rem;
    border-radius: 5px;
    box-shadow: 0 4px 14px rgba(26, 37, 64, .22);
    max-width: 220px;
    text-align: center;
}
.tooltip.rc-fare-tip.bs-tooltip-top .tooltip-arrow::before,
.tooltip.rc-fare-tip.bs-tooltip-auto[data-popper-placement^="top"] .tooltip-arrow::before {
    border-top-color: #1a2540 !important;
}
.tooltip.rc-fare-tip.bs-tooltip-bottom .tooltip-arrow::before,
.tooltip.rc-fare-tip.bs-tooltip-auto[data-popper-placement^="bottom"] .tooltip-arrow::before {
    border-bottom-color: #1a2540 !important;
}
.tooltip.rc-fare-tip.bs-tooltip-start .tooltip-arrow::before,
.tooltip.rc-fare-tip.bs-tooltip-auto[data-popper-placement^="left"] .tooltip-arrow::before {
    border-left-color: #1a2540 !important;
}
.tooltip.rc-fare-tip.bs-tooltip-end .tooltip-arrow::before,
.tooltip.rc-fare-tip.bs-tooltip-auto[data-popper-placement^="right"] .tooltip-arrow::before {
    border-right-color: #1a2540 !important;
}
.rc__fbadge--ref { background: var(--c-green-soft); color: var(--c-green); }
.rc__fbadge--nr  { background: #fff0f3; color: #c0143c; }
.rc__fbadge--brand { background: #eef2ff; color: #4338ca; text-transform: none; letter-spacing: 0; font-size: .64rem; }

.rc__brand {
    font-size: .72rem; font-weight: 700; color: #4338ca;
    margin-top: .08rem; line-height: 1.25;
}

.rc__ftag {
    display: inline-flex; align-items: center; gap: .12rem;
    font-size: .62rem; font-weight: 600; background: #edf0f7;
    color: var(--c-slate); padding: .06rem .32rem; border-radius: 4px;
    line-height: 1.15;
}
.rc__ftag i { font-size: .7rem; flex-shrink: 0; }
.rc__ftag--bag { background: #f1f5f9; }
.rc__ftag--basis {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .02em;
    background: #f8fafc;
    color: #475569;
}
.rc__ftag--seat { background: var(--c-amber-soft); color: var(--c-amber); min-width: 1.65rem; justify-content: center; }

/* flight details centered link */
.rc__details-center {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .18rem 0;
    border-top: 1px solid var(--c-line-inner);
    border-bottom: 1px solid var(--c-line-inner);
    background: #fff;
}
.rc__details-link {
    border: none; background: transparent; font: inherit;
    font-size: .72rem; font-weight: 600; color: var(--c-brand);
    cursor: pointer; display: inline-flex; align-items: center; gap: .22rem;
    padding: .08rem .35rem;
    text-decoration: underline; text-underline-offset: 2px;
    text-decoration-color: transparent;
    transition: text-decoration-color .14s, opacity .14s;
}
.rc__details-link:hover {
    text-decoration-color: var(--c-brand);
    opacity: .85;
}

/* price */
.rc__price-label {
    font-size: .52rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--c-brand); margin-bottom: .02rem;
}
.rc__price-amount {
    font-family: var(--mono); font-size: 1.08rem; font-weight: 700;
    color: var(--c-brand); line-height: 1;
    display: flex; align-items: baseline; gap: .06rem;
}
.rc__price-cur { font-size: .72rem; color: var(--c-slate); font-weight: 600; }

/* dirham symbol  -  UAE custom font */
.dirham {
    font-family: "UAEDirham", "Segoe UI", sans-serif;
    font-size: .8em;
    font-weight: 400;
    color: inherit;
    margin-right: .06rem;
    vertical-align: baseline;
}

/* Hold button */
.rc__hold {
    border: 1.5px solid var(--c-amber);
    background: transparent;
    color: var(--c-amber);
    font: inherit;
    font-size: .74rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .22rem;
    padding: .34rem .72rem;
    border-radius: 7px;
    white-space: nowrap;
    transition: background .13s, color .13s;
}
.rc__hold:hover { background: var(--c-amber); color: #fff; }

/* Book Now CTA */
.rc__cta {
    display: inline-flex; align-items: center; gap: .22rem;
    padding: .36rem .85rem; border-radius: 7px;
    background: linear-gradient(180deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color: #fff !important; font-weight: 700; font-size: .76rem;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(205, 27, 79, 0.18);
    transition: transform .13s, box-shadow .13s;
}
.rc__cta:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(205, 27, 79, 0.22); }

/* =========================================================
   FLIGHT DETAILS MODAL
   ========================================================= */
.fd-modal {
    position: fixed; inset: 0; z-index: 9000;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.fd-modal[hidden] { display: none; }

.fd-backdrop {
    position: absolute; inset: 0;
    background: rgba(15,22,48,.55);
    backdrop-filter: blur(3px);
    cursor: pointer;
}

.fd-box {
    position: relative; z-index: 1;
    width: 100%; max-width: 760px;
    max-height: 88vh;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 28px 60px rgba(15,22,48,.22);
    display: flex; flex-direction: column;
    overflow: hidden;
    animation: fd-in .22s ease;
}
/* only the body panel scrolls */
.fd-head, .fd-tabs, .fd-foot-wrap, .fd-foot { flex-shrink: 0; overflow: hidden; }
@keyframes fd-in {
    from { opacity: 0; transform: translateY(18px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* modal head */
.fd-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem .85rem;
    border-bottom: 1px solid var(--c-line);
    background: linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%);
}
.fd-head__title {
    font-size: 1.05rem; font-weight: 700; color: #fff;
    display: flex; align-items: center; gap: .45rem;
}
.fd-head__title i { font-size: 1.2rem; }
.fd-head__sub { font-size: .75rem; font-weight: 400; opacity: .8; }

.fd-close {
    border: none; background: rgba(255,255,255,.2); color: #fff;
    width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; transition: background .12s; flex-shrink: 0;
}
.fd-close:hover { background: rgba(255,255,255,.35); }

/* tabs */
.fd-tabs {
    display: flex; gap: .45rem;
    border-bottom: 1px solid var(--c-line);
    padding: .6rem .9rem .55rem;
    background: #f9fafb;
    overflow: hidden;
    flex-wrap: wrap;
}
.fd-tab {
    border: 1.5px solid var(--c-line);
    background: #fff;
    font: inherit;
    font-size: .78rem; font-weight: 600; color: var(--c-slate);
    padding: .38rem .85rem;
    border-radius: 8px;
    display: inline-flex; align-items: center; gap: .32rem;
    cursor: pointer; white-space: nowrap;
    transition: background .13s, color .13s, border-color .13s, box-shadow .13s;
    box-shadow: 0 1px 3px rgba(26,37,64,.06);
}
.fd-tab:hover {
    background: var(--c-brand-soft);
    border-color: var(--c-brand);
    color: var(--c-brand);
}
.fd-tab--active {
    background: var(--c-brand);
    border-color: var(--c-brand);
    color: #fff;
    box-shadow: 0 3px 10px rgba(205,27,79,.22);
}
.fd-tab__route {
    font-family: var(--mono); font-size: .63rem; font-weight: 500;
    color: inherit; opacity: .8;
}

/* body / panels */
.fd-body { overflow-y: auto; flex: 1; }
.fd-panel { padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: .75rem; }
.fd-panel--hidden { display: none; }

/* layover */
.fd-layover {
    align-self: center; display: inline-flex; align-items: center; gap: .3rem;
    background: var(--c-violet-soft); color: var(--c-violet);
    font-size: .72rem; font-weight: 600;
    padding: .28rem .7rem; border-radius: 999px;
    border: 1px dashed rgba(124,58,237,.3);
}

/* segment */
.fd-seg {
    border: 1px solid var(--c-line); border-radius: 12px;
    overflow: hidden; background: #fff;
}
.fd-seg__air {
    display: flex; align-items: center; gap: .65rem;
    padding: .65rem .9rem;
    background: var(--c-bg);
    border-bottom: 1px solid var(--c-line);
    flex-wrap: wrap;
}
.fd-seg__logo {
    width: 34px; height: 34px; border-radius: 8px;
    object-fit: contain; background: #fff;
    border: 1px solid var(--c-line); padding: 2px; flex-shrink: 0;
}
.fd-seg__aname { font-size: .85rem; font-weight: 700; color: var(--c-ink); }
.fd-seg__ameta { font-family: var(--mono); font-size: .65rem; color: var(--c-muted); margin-top: .05rem; }
.fd-seg__aircraft { margin-top: .45rem; }
.fd-seg__aircraft-label {
    display: block;
    font-size: .58rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--c-muted);
}
.fd-seg__aircraft-name {
    display: block;
    margin-top: .12rem;
    font-size: .78rem;
    font-weight: 600;
    color: var(--c-slate);
}
.fd-seg__chips { display: flex; gap: .3rem; flex-wrap: wrap; margin-left: auto; }
.fd-chip {
    display: inline-flex; align-items: center; gap: .15rem;
    background: #edf0f7; color: var(--c-slate);
    font-family: var(--mono); font-size: .62rem; font-weight: 600;
    padding: .1rem .38rem; border-radius: 4px;
}
.fd-chip--cab  { background: var(--c-blue-soft); color: var(--c-blue); }
.fd-chip--seat { background: var(--c-amber-soft); color: var(--c-amber); }

.fd-seg__route {
    display: grid; grid-template-columns: 1fr auto 1fr;
    gap: 1rem; align-items: center; padding: .9rem 1rem;
}
.fd-seg__pt { display: flex; flex-direction: column; gap: .12rem; }
.fd-seg__pt--arr { align-items: flex-end; text-align: right; }
.fd-seg__time { font-family: var(--mono); font-size: 1.45rem; font-weight: 700; color: var(--c-ink); }
.fd-seg__code { font-family: var(--mono); font-size: .88rem; font-weight: 700; color: var(--c-slate); }
.fd-seg__city { font-size: .78rem; color: var(--c-ink); font-weight: 500; }
.fd-seg__city small { display: block; font-size: .68rem; color: var(--c-muted); font-weight: 400; }
.fd-seg__date { font-size: .68rem; color: var(--c-muted); }

.fd-seg__mid {
    display: flex; flex-direction: column; align-items: center; gap: .2rem;
    font-family: var(--mono); font-size: .7rem; color: var(--c-muted);
}
.fd-seg__track { display: flex; align-items: center; width: 100%; gap: 0; }
.fd-seg__dot   { width: 8px; height: 8px; border-radius: 50%; background: var(--c-brand); flex-shrink: 0; }
.fd-seg__line  { flex: 1; height: 2px; background: linear-gradient(90deg, var(--c-brand), var(--c-line)); }
.fd-seg__plane { font-size: 1rem; color: var(--c-brand); margin: 0 2px; }
.fd-seg__nonstop { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--c-green); }
.fd-seg__techstop { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--c-amber); }
.fd-seg__dur { font-weight: 700; font-size: .78rem; color: var(--c-slate); }

/* baggage panel */
.fd-bag { display: flex; flex-direction: column; gap: .65rem; }
.fd-bag__row {
    display: flex; gap: .75rem; align-items: flex-start;
    background: var(--c-bg); border: 1px solid var(--c-line);
    border-radius: 10px; padding: .7rem .9rem;
}
.fd-bag__icon { font-size: 1.25rem; color: var(--c-brand); flex-shrink: 0; margin-top: .05rem; }
.fd-bag__label { font-size: .72rem; font-weight: 700; color: var(--c-muted); text-transform: uppercase; letter-spacing: .07em; }
.fd-bag__val { font-size: .86rem; font-weight: 600; color: var(--c-ink); margin-top: .15rem; }
.fd-bag__meta { color: var(--c-muted); font-weight: 600; }
.fd-bag__section { display: flex; flex-direction: column; gap: .55rem; }
.fd-bag__section-title {
    font-size: .68rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--c-muted);
}
.fd-bag__table-wrap { overflow-x: auto; }
.fd-bag__table {
    width: 100%; border-collapse: collapse; background: #fff;
    border: 1px solid var(--c-line); border-radius: 10px; overflow: hidden;
}
.fd-bag__table th,
.fd-bag__table td {
    padding: .65rem .75rem; font-size: .82rem; text-align: left;
    border-bottom: 1px solid var(--c-line-inner);
}
.fd-bag__table th {
    background: #f3f6fb; color: var(--c-blue); font-size: .72rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
}
.fd-bag__table tr:last-child td { border-bottom: none; }
.fd-bag__amt { font-weight: 700; color: var(--c-ink); }
.fd-bag__amt-label { font-weight: 500; color: var(--c-slate); margin-left: .2rem; }
.fd-bag__amt--na { color: var(--c-muted); font-weight: 600; }
.fd-bag__footnote {
    display: flex; align-items: flex-start; gap: .4rem;
    margin-top: .45rem; padding: .55rem .7rem;
    background: #fff8ef; border: 1px solid #fde6c7; border-radius: 8px;
    font-size: .76rem; color: #b45309; line-height: 1.45;
}
.fd-bag__footnote i { font-size: .95rem; flex-shrink: 0; margin-top: .05rem; }

.fd-fare-tabs {
    display: flex; gap: .45rem; flex-wrap: wrap;
    padding: .75rem 1.25rem 0;
}
.fd-fare-tab {
    border: 1px solid var(--c-line); background: #fff; color: var(--c-slate);
    font: inherit; font-size: .72rem; font-weight: 700; border-radius: 999px;
    padding: .35rem .75rem; cursor: pointer;
}
.fd-fare-tab--active {
    background: var(--c-brand); border-color: var(--c-brand); color: #fff;
}
.fd-fare-panel:not(.fd-fare-panel--active) { display: none; }
.fd-foot-wrap {
    position: relative;
    border-top: 1px solid var(--c-line);
    background: var(--c-bg);
}
.fd-foot.fd-fare-foot:not(.fd-fare-foot--active) { display: none; }
.fd-foot.fd-fare-foot--active { display: flex; }

@include('user.flights.partials.fare-rules-styles')

/* modal footer */
.fd-foot {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .65rem;
    padding: .85rem 1.25rem;
    border-top: 1px solid var(--c-line);
    background: var(--c-bg);
}
.fd-foot__label { font-size: .6rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--c-brand); }
.fd-foot__amount {
    font-family: var(--mono); font-size: 1.35rem; font-weight: 700; color: var(--c-brand);
    display: flex; align-items: baseline; gap: .08rem;
}
.fd-foot__btns { display: flex; gap: .6rem; }
.fd-foot__hold {
    border: 2px solid var(--c-amber); background: transparent; color: var(--c-amber);
    font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: .28rem;
    padding: .52rem .95rem; border-radius: 8px; transition: background .13s, color .13s;
}
.fd-foot__hold:hover { background: var(--c-amber); color: #fff; }
.fd-foot__book {
    display: inline-flex; align-items: center; gap: .28rem;
    padding: .55rem 1.2rem; border-radius: 8px;
    background: linear-gradient(180deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color: #fff !important; font-weight: 700; font-size: .84rem; white-space: nowrap;
    box-shadow: 0 2px 6px rgba(205, 27, 79, 0.18); transition: transform .13s;
}
.fd-foot__book:hover { transform: translateY(-1px); }

/* =========================================================
   EMPTY
   ========================================================= */
.rp-empty {
    margin: 3rem auto; max-width: 480px; text-align: center;
    padding: 2rem 1.5rem; background: var(--c-white);
    border: 1px solid var(--c-line); border-radius: 16px; box-shadow: var(--c-shadow);
}
.rp-empty__icon { font-size: 2.4rem; color: var(--c-brand); display: block; margin-bottom: .75rem; }
.rp-empty__icon--idle { color: var(--c-blue); }
.rp-empty h3 { font-size: 1.15rem; font-weight: 700; margin: 0 0 .5rem; color: var(--c-ink); }
.rp-empty p  { font-size: .88rem; color: var(--c-slate); margin: 0 0 1.1rem; line-height: 1.55; }
.rp-empty__msgs { text-align: left; max-width: 520px; margin: 0 auto 1rem; padding: 0; list-style: none; font-size: .82rem; color: #b45309; }
.rp-empty__msgs li { margin: .25rem 0; }
.rp-emptybtn {
    display: inline-flex; align-items: center; gap: .3rem;
    background: var(--c-brand); color: #fff !important;
    padding: .6rem 1.2rem; border-radius: 8px;
    font-weight: 700; font-size: .86rem;
    box-shadow: 0 2px 8px rgba(205, 27, 79, 0.2);
}

/* =========================================================
   RESULTS GRID (filter head row aligns with slider; cards align with filter body)
   ========================================================= */
.rp-stack {
    display: grid;
    grid-template-columns: 270px 1fr;
    gap: 1.1rem;
    align-items: start;
}
.rp-stack__lead {
    grid-column: 1;
    display: flex;
    flex-direction: column;
    gap: 0;
    min-width: 0;
    position: sticky;
    top: 1rem;
    align-self: start;
}
.rp-stack__body {
    grid-column: 2;
    display: flex;
    flex-direction: column;
    gap: 0;
    min-width: 0;
}
.rp-stack__tools {
    flex-shrink: 0;
    min-width: 0;
}
.rp-stack__main {
    flex: 1 1 auto;
    min-width: 0;
}

.rp-stack .sf.sf--filter-cap,
.rp-stack .sf.sf--filter-rest {
    position: static;
}
.rp-stack__lead .sf.sf--filter-cap {
    border-radius: 14px 14px 0 0;
    border: 1px solid var(--c-line);
    border-bottom: none;
    overflow: hidden;
    background: var(--c-white);
}
.rp-stack__lead .sf.sf--filter-rest {
    border-radius: 0 0 14px 14px;
    border: 1px solid var(--c-line);
    border-top: none;
    overflow: hidden;
}
.rp-main { min-width: 0; }

/* =========================================================
   FILTER SIDEBAR
   ========================================================= */
.sf {
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    overflow: hidden;
    position: sticky;
    top: 1rem;
}

/* sidebar header */
.sf__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .8rem 1rem;
    border-bottom: 1px solid var(--c-line);
    background: linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%);
}
.sf__title {
    font-size: .85rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.sf__title i { font-size: 1rem; }
.sf__reset {
    border: 1.5px solid rgba(255,255,255,.5);
    background: transparent;
    color: rgba(255,255,255,.9);
    font: inherit;
    font-size: .7rem;
    font-weight: 600;
    padding: .2rem .55rem;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .22rem;
    transition: background .13s;
}
.sf__reset:hover { background: rgba(255,255,255,.15); }

/* section */
.sf__section {
    padding: .85rem 1rem;
    border-bottom: 1px solid var(--c-line);
}
.sf__section:last-child { border-bottom: none; }

.sf__sechead {
    font-size: .72rem;
    font-weight: 700;
    color: var(--c-ink);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: .65rem;
    display: flex;
    align-items: center;
    gap: .3rem;
}
.sf__sechead i { font-size: .9rem; color: var(--c-brand); }

/* ── Price dual range ── */
.sf__price-labels {
    display: flex;
    justify-content: space-between;
    font-size: .75rem;
    font-weight: 700;
    font-family: var(--mono);
    color: var(--c-brand);
    margin-bottom: .55rem;
}
.sf__dual-wrap {
    position: relative;
    height: 18px;
    margin: .4rem .3rem;
}
.sf__dual-track {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    left: 0; right: 0;
    height: 4px;
    background: var(--c-line);
    border-radius: 2px;
    pointer-events: none;
}
.sf__dual-fill {
    position: absolute;
    top: 0; height: 100%;
    background: var(--c-brand);
    border-radius: 2px;
}
.sf__dual-input {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    height: 4px;
    background: transparent;
    -webkit-appearance: none;
    appearance: none;
    pointer-events: none;
    outline: none;
    margin: 0;
}
.sf__dual-input::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: var(--c-brand);
    border: 2.5px solid #fff;
    box-shadow: 0 1px 5px rgba(205,27,79,.4);
    cursor: pointer;
    pointer-events: all;
    transition: transform .12s;
}
.sf__dual-input::-webkit-slider-thumb:hover { transform: scale(1.2); }
.sf__dual-input::-moz-range-thumb {
    width: 16px; height: 16px;
    border-radius: 50%;
    background: var(--c-brand);
    border: 2.5px solid #fff;
    box-shadow: 0 1px 5px rgba(205,27,79,.4);
    cursor: pointer;
    pointer-events: all;
}

/* ── Single range (duration) ── */
.sf__single-range {
    width: 100%;
    -webkit-appearance: none;
    appearance: none;
    height: 4px;
    background: var(--c-brand);
    border-radius: 2px;
    outline: none;
    cursor: pointer;
    margin-top: .4rem;
    /* filled left side will be set via inline style / JS */
}
.sf__single-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: var(--c-brand);
    border: 2.5px solid #fff;
    box-shadow: 0 1px 5px rgba(205,27,79,.35);
    cursor: pointer;
    transition: transform .12s;
}
.sf__single-range::-webkit-slider-thumb:hover { transform: scale(1.2); }
.sf__dur-val {
    font-family: var(--mono);
    font-size: .68rem;
    font-weight: 700;
    color: var(--c-brand);
    margin-left: auto;
    letter-spacing: 0;
}

/* ── Stops ── */
.sf__stop-row { display: flex; gap: .4rem; flex-wrap: wrap; }
.sf__stoplbl { cursor: pointer; }
.sf__stoplbl input { display: none; }
.sf__stoppill {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .72rem;
    font-weight: 600;
    padding: .28rem .7rem;
    border-radius: 20px;
    border: 1.5px solid var(--c-line);
    background: #fff;
    color: var(--c-slate);
    cursor: pointer;
    transition: border-color .12s, background .12s, color .12s;
    white-space: nowrap;
}
.sf__stoppill i { font-size: .85rem; }
.sf__stoplbl input:checked + .sf__stoppill {
    border-color: var(--c-brand);
    background: var(--c-brand-soft);
    color: var(--c-brand);
}
.sf__stoppill:hover { border-color: rgba(205,27,79,.3); background: var(--c-brand-soft); }
.sf__stoppill--ref.sf__stoppill,
.sf__stoplbl input:checked + .sf__stoppill--ref {
    border-color: rgba(22, 163, 74, .35);
}
.sf__stoplbl input:checked + .sf__stoppill--ref {
    background: var(--c-green-soft);
    color: var(--c-green);
}
.sf__stoppill--nr.sf__stoppill,
.sf__stoplbl input:checked + .sf__stoppill--nr {
    border-color: rgba(192, 20, 60, .35);
}
.sf__stoplbl input:checked + .sf__stoppill--nr {
    background: #fff0f3;
    color: #c0143c;
}
.sf__pillcnt {
    font-weight: 700;
    opacity: .85;
}

/* ── Time slot grid ── */
.sf__time-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .35rem;
}
.sf__timebtn {
    border: 1.5px solid var(--c-line);
    background: #fff;
    border-radius: 8px;
    padding: .4rem .3rem;
    cursor: pointer;
    font: inherit;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .1rem;
    transition: border-color .12s, background .12s, color .12s;
}
.sf__timebtn i { font-size: 1.05rem; color: var(--c-muted); transition: color .12s; }
.sf__timebtn span { font-size: .68rem; font-weight: 700; color: var(--c-ink); }
.sf__timebtn small { font-size: .58rem; color: var(--c-muted); font-family: var(--mono); }
.sf__timebtn:hover,
.sf__timebtn.sf-active {
    border-color: var(--c-brand);
    background: var(--c-brand-soft);
}
.sf__timebtn.sf-active i,
.sf__timebtn.sf-active span { color: var(--c-brand); }
.sf__timebtn:hover i { color: var(--c-brand); }

/* ── Checkboxes (fare type) ── */
.sf__chklbl {
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .32rem 0;
    cursor: pointer;
}
.sf__chklbl input[type="checkbox"] { display: none; }
.sf__chkmark {
    width: 16px; height: 16px;
    border-radius: 4px;
    border: 1.5px solid var(--c-line);
    background: #fff;
    flex-shrink: 0;
    position: relative;
    transition: border-color .12s, background .12s;
}
.sf__chklbl input:checked ~ .sf__chkmark {
    background: var(--c-brand);
    border-color: var(--c-brand);
}
.sf__chklbl input:checked ~ .sf__chkmark::after {
    content: '';
    position: absolute;
    left: 4px; top: 1px;
    width: 5px; height: 9px;
    border: 2px solid #fff;
    border-top: none; border-left: none;
    transform: rotate(40deg);
}
.sf__chktxt {
    font-size: .78rem;
    font-weight: 600;
    color: var(--c-slate);
    display: flex;
    align-items: center;
    gap: .28rem;
}
.sf__chktxt i { font-size: .9rem; }
.sf__chktxt--ref i { color: var(--c-green); }
.sf__chktxt--nr  i { color: #c0143c; }

/* active filter count badge */
.sf__head-badge {
    background: rgba(255,255,255,.25);
    color: #fff;
    font-size: .62rem;
    font-weight: 700;
    padding: .05rem .38rem;
    border-radius: 10px;
    margin-left: .25rem;
    display: none;
}
.sf__head-badge.visible { display: inline-block; }

/* =========================================================
   AIRLINE FILTER SLIDER
   ========================================================= */
.as-wrap {
    display: flex;
    align-items: stretch;
    margin-bottom: .85rem;
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 12px;
    overflow: hidden;
}

/* arrow buttons */
.as-arrow {
    flex-shrink: 0;
    width: 34px;
    border: none;
    background: transparent;
    color: #475569;
    cursor: pointer;
    font-size: 1.35rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .13s, background .13s, opacity .13s;
}
.as-arrow i {
    display: block;
    opacity: 1;
}
.as-arrow:hover:not(:disabled) { color: var(--c-brand); background: var(--c-brand-soft); }
/* Disabled must stay visible (avoid opacity on whole control — was ~invisible on white) */
.as-arrow:disabled {
    opacity: 1;
    color: #94a3b8;
    cursor: default;
}
.as-arrow:focus-visible {
    outline: 2px solid var(--c-brand);
    outline-offset: 1px;
}
.as-arrow--prev { border-right: 1px solid var(--c-line); }
.as-arrow--next { border-left:  1px solid var(--c-line); }

/* viewport */
.as-viewport {
    flex: 1;
    overflow: hidden;
    position: relative;
}
.as-viewport::before,
.as-viewport::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0; width: 24px;
    pointer-events: none; z-index: 1;
}
.as-viewport::before { left:  0; background: linear-gradient(to right, #fff 0%, transparent 100%); }
.as-viewport::after  { right: 0; background: linear-gradient(to left,  #fff 0%, transparent 100%); }

/* scrollable track */
.as-track {
    display: flex;
    align-items: stretch;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.as-track::-webkit-scrollbar { display: none; }

/* each airline item */
.as-pill {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .7rem 1.1rem;
    border: none;
    border-right: 1px solid var(--c-line);
    background: transparent;
    cursor: pointer;
    font: inherit;
    text-align: left;
    white-space: nowrap;
    position: relative;
    transition: background .13s;
}
.as-pill:last-child { border-right: none; }
.as-pill::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: var(--c-brand);
    border-radius: 3px 3px 0 0;
    transform: scaleX(0);
    transition: transform .15s ease;
}
.as-pill:hover { background: var(--c-brand-soft); }
.as-pill--active { background: var(--c-brand-soft); }
.as-pill--active::after { transform: scaleX(1); }

/* logo */
.as-pill__logo {
    width: 38px;
    height: 38px;
    object-fit: contain;
    flex-shrink: 0;
    border-radius: 6px;
    display: block;
}
.as-pill__logo--all {
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 6px;
    background: var(--c-brand-soft);
    color: var(--c-brand);
    font-size: 1.2rem;
    flex-shrink: 0;
}

/* text */
.as-pill__body {
    display: flex;
    flex-direction: column;
    gap: .12rem;
    min-width: 0;
}
.as-pill__nameline {
    font-size: .8rem;
    font-weight: 600;
    color: var(--c-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
    line-height: 1.2;
}
.as-pill--active .as-pill__nameline { color: var(--c-brand); }
.as-pill__cnt {
    font-weight: 400;
    color: var(--c-muted);
    font-size: .78rem;
}
.as-pill--active .as-pill__cnt { color: var(--c-brand); opacity: .75; }

.as-pill__price {
    font-size: .76rem;
    font-weight: 700;
    color: var(--c-brand);
    font-family: var(--mono);
    white-space: nowrap;
}
.as-pill:not(.as-pill--active) .as-pill__price { color: var(--c-slate); font-weight: 600; }

/* filtered-out cards */
.rc.as-hidden  { display: none; }
.rc.sf-hidden  { display: none; }

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 1100px) {
    .rp-stack { grid-template-columns: 230px 1fr; }
}
@media (max-width: 991px) {
    .rc__leg { grid-template-columns: 155px 1fr 145px 1fr 24px; gap: .4rem .65rem; }
    .rp-stack { grid-template-columns: 210px 1fr; gap: .75rem; }
    .sf__time-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 767px) {
    .rp-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .rp-stack__lead,
    .rp-stack__body {
        grid-column: unset;
        grid-row: unset;
        position: static;
        width: 100%;
    }
    .rp-stack__tools,
    .rp-stack__main {
        width: 100%;
    }
    .sf { position: static; }
    .sf__section { padding: .7rem .9rem; }
    .rc__leg {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto auto;
    }
    .rc__airline { grid-column: 1 / -1; }
    .rc__bridge  { grid-column: 1 / -1; flex-direction: row; justify-content: center; gap: .5rem; }
    .rc__btrack  { flex: 1; }
    .rc__redeyecol { display: none; }
    .rc__point--arr { text-align: left; }
    .rc__fare {
        flex-direction: column;
        align-items: stretch;
        flex-wrap: wrap;
    }
    .rc__fare-right {
        width: 100%;
        margin-left: 0;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .fd-seg__route { grid-template-columns: 1fr; }
    .fd-seg__pt--arr { align-items: flex-start; text-align: left; }
    .fd-box { max-height: 95vh; border-radius: 12px; }
    /* airline slider on mobile */
    .as-arrow { width: 30px; font-size: 1.25rem; }
    .as-pill { padding: .6rem .75rem; gap: .45rem; }
    .as-pill__logo, .as-pill__logo--all { width: 30px; height: 30px; }
    .as-pill__nameline { max-width: 90px; font-size: .74rem; }
    .as-pill__price { font-size: .7rem; }
}
</style>
@endpush

@push('js')
@include('user.flights.partials.fare-rules-scripts')
<script>
(function(){
    const list = document.getElementById('rp-list');
    if (!list) return;

    /* ─────────────────────────────────────────────────────
       SHARED HELPERS
    ───────────────────────────────────────────────────── */
    function parseMeta(card){
        try { return JSON.parse(card.dataset.rpMeta||'{}'); } catch(e){ return {}; }
    }
    function cd(card, key){ return card.dataset[key] ?? ''; }

    const IS_ROUND_TRIP = (list.dataset.rpTrip || '') === 'round_trip';
    const SLOT_TO_BUCKET = { night: 4, morning: 1, afternoon: 2, evening: 3 };

    function bucketsFromSlots(slotSet){
        return [...slotSet].map(s => SLOT_TO_BUCKET[s]).filter(Boolean);
    }
    function legMatchesTimeBuckets(selectedSlots, legBuckets){
        if (!selectedSlots || selectedSlots.size === 0) return true;
        const wanted = bucketsFromSlots(selectedSlots);
        const buckets = Array.isArray(legBuckets) ? legBuckets.map(Number) : [];
        if (buckets.length === 0) return false;
        return wanted.some(w => buckets.includes(w));
    }
    function cardStopsTier(meta){
        const stO = Number(meta.st_o) || 0;
        const stR = meta.st_r === null || meta.st_r === undefined ? null : Number(meta.st_r);
        const st = IS_ROUND_TRIP && stR !== null ? Math.max(stO, stR) : stO;
        return st >= 2 ? 2 : st;
    }
    function fmtDurMinutes(m){
        m = parseInt(m, 10) || 0;
        return Math.floor(m / 60) + 'h ' + (m % 60) + 'm';
    }

    function initFareTooltips(root){
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        (root || document).querySelectorAll('.rc__fare [data-bs-toggle="tooltip"]').forEach(el=>{
            const existing = bootstrap.Tooltip.getInstance(el);
            if (existing) existing.dispose();
            new bootstrap.Tooltip(el, {
                trigger: 'hover focus',
                container: 'body',
                placement: 'top',
                customClass: 'rc-fare-tip',
                boundary: 'viewport',
            });
        });
    }

    /* ─────────────────────────────────────────────────────
       FILTER STATE
    ───────────────────────────────────────────────────── */
    const allCards = [...list.querySelectorAll('.rc')];
    const allPrices = allCards.map(c => parseFloat(cd(c,'rpPrice'))||0);
    const globalMin = Math.min(...allPrices);
    const globalMax = Math.max(...allPrices);

    const state = {
        sliderAirline : 'all',
        priceMin      : globalMin,
        priceMax      : globalMax,
        stops         : new Set(),    // empty = all
        depSlots      : new Set(),    // outbound departure
        arrSlots      : new Set(),    // outbound arrival
        depSlotsR     : new Set(),    // return departure
        arrSlotsR     : new Set(),    // return arrival
        refund        : new Set(),    // empty = all; values: '0', '1'
        maxDur        : Infinity,
    };

    /* ─────────────────────────────────────────────────────
       APPLY FILTERS - single source of truth
    ───────────────────────────────────────────────────── */
    function applyFilters(){
        let visible = 0;
        allCards.forEach(card=>{
            const price  = parseFloat(cd(card,'rpPrice'))  || 0;
            const refund = cd(card,'rpRefund');
            const dur    = parseInt(cd(card,'rpDur'),10)   || 0;
            const meta   = parseMeta(card);
            const als    = Array.isArray(meta.al) ? meta.al.map(c=>String(c).toUpperCase()) : [];

            let ok = true;

            // airline slider
            if (state.sliderAirline !== 'all' && !als.includes(state.sliderAirline)) ok = false;
            // price
            if (ok && (price < state.priceMin || price > state.priceMax)) ok = false;
            // stops (worst leg on round trip)
            if (ok && state.stops.size > 0){
                const tier = cardStopsTier(meta);
                if (!state.stops.has(String(tier))) ok = false;
            }
            // outbound departure / arrival buckets (all segments on that leg)
            if (ok && !legMatchesTimeBuckets(state.depSlots, meta.dba_o)) ok = false;
            if (ok && !legMatchesTimeBuckets(state.arrSlots, meta.aba_o)) ok = false;
            // return departure / arrival buckets
            if (ok && IS_ROUND_TRIP && !legMatchesTimeBuckets(state.depSlotsR, meta.dba_r)) ok = false;
            if (ok && IS_ROUND_TRIP && !legMatchesTimeBuckets(state.arrSlotsR, meta.aba_r)) ok = false;
            // fare type
            if (ok && state.refund.size > 0 && !state.refund.has(refund)) ok = false;
            // duration (outbound leg)
            if (ok && dur > 0 && state.maxDur !== Infinity && dur > state.maxDur) ok = false;

            card.classList.toggle('sf-hidden', !ok);
            if (ok) visible++;
        });

        // update count badge
        const badge = document.querySelector('.rp-bar__count strong:first-child');
        if (badge) badge.textContent = visible;

        // update active filter count on sidebar header
        updateBadge();
    }

    function countActiveFilters(){
        let n = 0;
        if (state.sliderAirline !== 'all') n++;
        if (state.priceMin > globalMin || state.priceMax < globalMax) n++;
        if (state.stops.size)    n++;
        if (state.depSlots.size) n++;
        if (state.arrSlots.size) n++;
        if (state.depSlotsR.size) n++;
        if (state.arrSlotsR.size) n++;
        if (state.refund.size)   n++;
        if (state.maxDur !== Infinity) n++;
        return n;
    }
    function updateBadge(){
        const b = document.getElementById('sf-active-badge');
        if (!b) return;
        const n = countActiveFilters();
        b.textContent = n;
        b.classList.toggle('visible', n > 0);
    }

    /* ─────────────────────────────────────────────────────
       SORT
    ───────────────────────────────────────────────────── */
    let currentSortKey = 'price';
    let currentSortDir = 'asc';

    function cardSortValue(card, key){
        const meta = parseMeta(card);
        const price = Number(meta.price) || parseFloat(cd(card, 'rpPrice')) || 0;

        switch (key) {
            case 'price':
                return price;
            case 'airline':
                return String(meta.airline_name || meta.airline_primary || (meta.al || [])[0] || '').toLowerCase();
            case 'departure':
                return Number(meta.dep_ts) || Date.parse(meta.first_dep_iso || '') || 0;
            case 'arrival':
                return Number(meta.arr_ts) || Date.parse(meta.first_arr_iso || '') || 0;
            case 'duration':
                return Number(meta.dur_o) || parseInt(cd(card, 'rpDur'), 10) || 0;
            case 'best-value': {
                const minutes = Math.max(1, Number(meta.dur_total) || Number(meta.dur_o) || parseInt(cd(card, 'rpDur'), 10) || 1);
                return price / minutes;
            }
            default:
                return price;
        }
    }

    function compareCards(a, b){
        let cmp = 0;

        if (currentSortKey === 'airline') {
            cmp = cardSortValue(a, 'airline').localeCompare(cardSortValue(b, 'airline'), undefined, { sensitivity: 'base' });
        } else {
            cmp = cardSortValue(a, currentSortKey) - cardSortValue(b, currentSortKey);
        }

        if (cmp === 0) {
            cmp = cardSortValue(a, 'price') - cardSortValue(b, 'price');
        }
        if (cmp === 0) {
            cmp = String(a.dataset.rpId || '').localeCompare(String(b.dataset.rpId || ''));
        }

        return currentSortDir === 'desc' ? -cmp : cmp;
    }

    function updateSortButtons(){
        document.querySelectorAll('.rp-sortbtn[data-rp-sort-key]').forEach(btn=>{
            const active = btn.dataset.rpSortKey === currentSortKey;
            btn.classList.toggle('is-active', active);
            btn.classList.toggle('rp-sortbtn--active', active);
            btn.classList.toggle('is-desc', active && currentSortDir === 'desc');
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function sortCards(){
        const items = [...list.querySelectorAll('.rc')];
        items.sort(compareCards);
        items.forEach(el=>list.appendChild(el));
    }

    document.querySelectorAll('.rp-sortbtn[data-rp-sort-key]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const key = btn.dataset.rpSortKey || 'price';

            if (key === currentSortKey) {
                currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortKey = key;
                currentSortDir = 'asc';
            }

            updateSortButtons();
            sortCards();
        });
    });

    updateSortButtons();

    /* ─────────────────────────────────────────────────────
       MODAL
    ───────────────────────────────────────────────────── */
    function getModalFareIndex(modal) {
        return modal?.dataset.activeFare ?? '0';
    }

    function setModalFareIndex(modal, fareIndex) {
        if (!modal) return;
        modal.dataset.activeFare = String(fareIndex);

        modal.querySelectorAll('.fd-fare-tab').forEach(tab => {
            tab.classList.toggle('fd-fare-tab--active', tab.dataset.fdFareTab === String(fareIndex));
        });

        modal.querySelectorAll('.fd-fare-foot').forEach(foot => {
            foot.classList.toggle('fd-fare-foot--active', foot.dataset.fdFareFoot === String(fareIndex));
        });

        const activePanel = modal.dataset.activePanel;
        if (activePanel) {
            activateModalTab(modal, activePanel);
        }
    }

    function loadFullFareRules(modal) {
        if (window.FlightFareRules) {
            window.FlightFareRules.loadForModal(modal);
        }
    }

    function activateModalTab(modal, panelKey){
        if (!modal || panelKey === undefined || panelKey === null) return;
        modal.dataset.activePanel = String(panelKey);
        const fareIndex = getModalFareIndex(modal);

        modal.querySelectorAll('.fd-tab').forEach(tab=>{
            tab.classList.toggle('fd-tab--active', tab.dataset.fdTab === String(panelKey));
        });

        modal.querySelectorAll('.fd-panel').forEach(panel=>{
            const panelType = panel.dataset.fdPanel;
            const isLegPanel = panelType === '0' || panelType === '1' || /^\d+$/.test(panelType || '');

            if (panel.classList.contains('fd-fare-panel')) {
                const matchesFare = panel.dataset.fdFarePanel === fareIndex;
                const matchesTab = panelType === String(panelKey);
                panel.classList.toggle('fd-panel--hidden', !(matchesFare && matchesTab));
                panel.classList.toggle('fd-fare-panel--active', matchesFare && matchesTab);
                return;
            }

            if (isLegPanel) {
                panel.classList.toggle('fd-panel--hidden', panelType !== String(panelKey));
            }
        });

        const fareTabs = modal.querySelector('.fd-fare-tabs');
        if (fareTabs) {
            const singleFareMode = modal.dataset.singleFareMode === '1';
            const showFareTabs = !singleFareMode && (panelKey === 'baggage' || panelKey === 'fare-rules');
            fareTabs.hidden = !showFareTabs;
        }

        if (String(panelKey) === 'fare-rules') {
            loadFullFareRules(modal);
        }
    }

    function openModal(id, tabKey, fareIndex, singleFareMode = false){
        const m = document.getElementById(id);
        if(!m) return;
        m.hidden = false;
        document.body.style.overflow = 'hidden';
        m.dataset.singleFareMode = singleFareMode ? '1' : '0';
        setModalFareIndex(m, fareIndex ?? getModalFareIndex(m));
        if (tabKey) activateModalTab(m, tabKey);
        else activateModalTab(m, m.dataset.activePanel || '0');
    }
    function closeModal(id){
        const m = document.getElementById(id);
        if(!m) return;
        m.hidden = true;
        document.body.style.overflow = '';
    }
    document.querySelectorAll('[data-fd-open]').forEach(btn=>{
        btn.addEventListener('click', ()=> {
            const singleFare = btn.dataset.fdFare !== undefined && btn.dataset.fdFare !== '';
            openModal(
                btn.dataset.fdOpen,
                btn.dataset.fdOpenTab || null,
                singleFare ? btn.dataset.fdFare : null,
                singleFare,
            );
        });
    });
    document.querySelectorAll('[data-fd-close]').forEach(el=>{
        el.addEventListener('click', ()=> closeModal(el.dataset.fdClose));
    });
    document.addEventListener('keydown', e=>{
        if(e.key === 'Escape'){
            document.querySelectorAll('.fd-modal:not([hidden])').forEach(m=> closeModal(m.id));
        }
    });
    document.querySelectorAll('.fd-modal').forEach(modal=>{
        modal.querySelectorAll('.fd-tab').forEach(tab=>{
            tab.addEventListener('click', ()=>{
                activateModalTab(modal, tab.dataset.fdTab);
            });
        });
        modal.querySelectorAll('.fd-fare-tab').forEach(tab=>{
            tab.addEventListener('click', ()=>{
                setModalFareIndex(modal, tab.dataset.fdFareTab);
            });
        });
    });

    document.querySelectorAll('[data-rc-more-fares]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const wrap = btn.closest('[data-rc-fares]');
            if (!wrap) return;

            const expanded = wrap.classList.toggle('is-expanded');
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');

            const hiddenCount = wrap.querySelectorAll('.rc__fare--collapsed').length;
            const label = btn.querySelector('.rc__more-fares__label');
            if (label) {
                label.textContent = expanded
                    ? 'Show fewer fares'
                    : `+${hiddenCount} More Fare${hiddenCount === 1 ? '' : 's'}`;
            }

            if (expanded) initFareTooltips(wrap);
        });
    });

    /* ─────────────────────────────────────────────────────
       AIRLINE SLIDER
    ───────────────────────────────────────────────────── */
    (function(){
        const track   = document.getElementById('as-track');
        const btnPrev = document.getElementById('as-prev');
        const btnNext = document.getElementById('as-next');
        if (!track) return;
        const STEP = 260;
        function updateArrows(){
            const sv = track.scrollLeft;
            const mx = track.scrollWidth - track.clientWidth;
            if (btnPrev) btnPrev.disabled = sv <= 2;
            if (btnNext) btnNext.disabled = sv >= mx - 2;
        }
        track.addEventListener('scroll', updateArrows, {passive:true});
        updateArrows();
        if (btnPrev) btnPrev.addEventListener('click', ()=> track.scrollBy({left:-STEP,behavior:'smooth'}));
        if (btnNext) btnNext.addEventListener('click', ()=> track.scrollBy({left: STEP,behavior:'smooth'}));

        document.querySelectorAll('.as-pill[data-as-code]').forEach(pill=>{
            pill.addEventListener('click', ()=>{
                const code = pill.dataset.asCode;
                document.querySelectorAll('.as-pill').forEach(p=>p.classList.remove('as-pill--active'));
                pill.classList.add('as-pill--active');
                state.sliderAirline = code;
                applyFilters();
            });
        });
    })();

    /* ─────────────────────────────────────────────────────
       SIDEBAR FILTERS
    ───────────────────────────────────────────────────── */

    /* Price dual range */
    const pLo = document.getElementById('sf-plo');
    const pHi = document.getElementById('sf-phi');
    const pLoLbl = document.getElementById('sf-plo-lbl');
    const pHiLbl = document.getElementById('sf-phi-lbl');
    const pFill  = document.getElementById('sf-price-fill');
    const cur    = (pLo?.dataset.cur) || '';

    function fmtPrice(v){ return parseFloat(v).toLocaleString(undefined,{maximumFractionDigits:0}); }
    function updatePriceFill(){
        if (!pLo || !pHi || !pFill) return;
        const mn = parseFloat(pLo.min), mx = parseFloat(pLo.max);
        const lo = parseFloat(pLo.value), hi = parseFloat(pHi.value);
        const pct = v => ((v - mn)/(mx - mn)) * 100;
        pFill.style.left  = pct(lo) + '%';
        pFill.style.width = (pct(hi) - pct(lo)) + '%';
        // update labels (currency prefix is in the static label text, keep it)
        const prefix = pLoLbl?.textContent.match(/^[A-Z]+\s?/)?.[0] || '';
        if (pLoLbl) pLoLbl.textContent = prefix + fmtPrice(lo);
        if (pHiLbl) pHiLbl.textContent = prefix + fmtPrice(hi);
    }
    if (pLo && pHi){
        pLo.addEventListener('input', ()=>{
            if (parseFloat(pLo.value) > parseFloat(pHi.value) - 1) pLo.value = parseFloat(pHi.value) - 1;
            state.priceMin = parseFloat(pLo.value);
            updatePriceFill();
            applyFilters();
        });
        pHi.addEventListener('input', ()=>{
            if (parseFloat(pHi.value) < parseFloat(pLo.value) + 1) pHi.value = parseFloat(pLo.value) + 1;
            state.priceMax = parseFloat(pHi.value);
            updatePriceFill();
            applyFilters();
        });
        updatePriceFill();
    }

    /* Stops checkboxes */
    document.querySelectorAll('[data-sf="stops"]').forEach(chk=>{
        chk.addEventListener('change', ()=>{
            const v = String(chk.value);
            chk.checked ? state.stops.add(v) : state.stops.delete(v);
            applyFilters();
        });
    });

    /* Time slot buttons - outbound departure */
    document.querySelectorAll('[data-sf-dep]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const slot = btn.dataset.sfDep;
            btn.classList.toggle('sf-active');
            btn.classList.contains('sf-active') ? state.depSlots.add(slot) : state.depSlots.delete(slot);
            applyFilters();
        });
    });

    /* Time slot buttons - outbound arrival */
    document.querySelectorAll('[data-sf-arr]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const slot = btn.dataset.sfArr;
            btn.classList.toggle('sf-active');
            btn.classList.contains('sf-active') ? state.arrSlots.add(slot) : state.arrSlots.delete(slot);
            applyFilters();
        });
    });

    /* Time slot buttons - return departure */
    document.querySelectorAll('[data-sf-dep-r]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const slot = btn.dataset.sfDepR;
            btn.classList.toggle('sf-active');
            btn.classList.contains('sf-active') ? state.depSlotsR.add(slot) : state.depSlotsR.delete(slot);
            applyFilters();
        });
    });

    /* Time slot buttons - return arrival */
    document.querySelectorAll('[data-sf-arr-r]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const slot = btn.dataset.sfArrR;
            btn.classList.toggle('sf-active');
            btn.classList.contains('sf-active') ? state.arrSlotsR.add(slot) : state.arrSlotsR.delete(slot);
            applyFilters();
        });
    });

    /* Fare type checkboxes */
    document.querySelectorAll('[data-sf="refund"]').forEach(chk=>{
        chk.addEventListener('change', ()=>{
            const v = chk.value;
            chk.checked ? state.refund.add(v) : state.refund.delete(v);
            applyFilters();
        });
    });

    /* Duration slider */
    const durSlider = document.getElementById('sf-dur');
    const durLbl    = document.getElementById('sf-dur-lbl');
    if (durSlider){
        durSlider.addEventListener('input', ()=>{
            state.maxDur = parseInt(durSlider.value, 10);
            if (durLbl) durLbl.textContent = fmtDurMinutes(durSlider.value);
            const pct = ((durSlider.value - durSlider.min)/(durSlider.max - durSlider.min))*100;
            durSlider.style.background = `linear-gradient(to right, var(--c-brand) ${pct}%, var(--c-line) ${pct}%)`;
            applyFilters();
        });
        durSlider.style.background = `linear-gradient(to right, var(--c-brand) 100%, var(--c-line) 100%)`;
    }

    /* Reset button */
    const resetBtn = document.getElementById('sf-reset');
    if (resetBtn){
        resetBtn.addEventListener('click', ()=>{
            // reset state
            state.sliderAirline = 'all';
            state.priceMin      = globalMin;
            state.priceMax      = globalMax;
            state.stops.clear();
            state.depSlots.clear();
            state.arrSlots.clear();
            state.depSlotsR.clear();
            state.arrSlotsR.clear();
            state.refund.clear();
            state.maxDur        = Infinity;

            // reset airline slider
            document.querySelectorAll('.as-pill').forEach(p=>p.classList.remove('as-pill--active'));
            const allPill = document.querySelector('.as-pill[data-as-code="all"]');
            if (allPill) allPill.classList.add('as-pill--active');

            // reset price sliders
            if (pLo) { pLo.value = pLo.min; }
            if (pHi) { pHi.value = pHi.max; }
            updatePriceFill();

            // reset stop checkboxes
            document.querySelectorAll('[data-sf="stops"]').forEach(c=>{ c.checked = false; });

            // reset time buttons
            document.querySelectorAll('[data-sf-dep],[data-sf-arr],[data-sf-dep-r],[data-sf-arr-r]').forEach(b=> b.classList.remove('sf-active'));

            // reset fare checkboxes
            document.querySelectorAll('[data-sf="refund"]').forEach(c=>{ c.checked = false; });

            // reset duration
            if (durSlider){
                durSlider.value = durSlider.max;
                if (durLbl) durLbl.textContent = fmtDurMinutes(durSlider.max);
                durSlider.style.background = `linear-gradient(to right, var(--c-brand) 100%, var(--c-line) 100%)`;
            }

            applyFilters();
        });
    }

    /* ─────────────────────────────────────────────────────
       INIT
    ───────────────────────────────────────────────────── */
    // Add hidden CSS rule (use sf-hidden class instead of as-hidden)
    const style = document.createElement('style');
    style.textContent = '.rc.sf-hidden { display: none; }';
    document.head.appendChild(style);

    // Add badge span to sidebar header
    const sfTitle = document.querySelector('.sf__title');
    if (sfTitle){
        const badge = document.createElement('span');
        badge.className = 'sf__head-badge';
        badge.id = 'sf-active-badge';
        sfTitle.appendChild(badge);
    }

    sortCards();
    applyFilters();
    initFareTooltips(list);

})();
</script>
@endpush
