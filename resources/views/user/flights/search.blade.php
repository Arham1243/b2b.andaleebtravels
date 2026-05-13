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
                return max(0, (int) $d->diffInMinutes($a, false));
            } catch (\Throwable $e) { return null; }
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
            $k = $isDep ? 'departure_city' : 'arrival_city';
            $c = trim((string) ($seg[$k] ?? ''));
            if ($c !== '') return $c;
            return trim((string) ($isDep ? ($seg['from'] ?? '') : ($seg['to'] ?? '')));
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
                    /* Build unique airline map sorted by min price */
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
                @endphp

                {{-- ── Airline filter slider ── --}}
                <div class="as-wrap" id="as-wrap">
                    <button class="as-arrow as-arrow--prev" id="as-prev" aria-label="Scroll left" disabled>
                        <i class="bx bx-chevron-left"></i>
                    </button>

                    <div class="as-viewport" id="as-viewport">
                        <div class="as-track" id="as-track">

                            {{-- All pill --}}
                            <button class="as-pill as-pill--all as-pill--active" data-as-code="all">
                                <div class="as-pill__logo-wrap as-pill__logo-wrap--all">
                                    <i class="bx bxs-plane"></i>
                                </div>
                                <div class="as-pill__body">
                                    <span class="as-pill__name">All Airlines</span>
                                    <span class="as-pill__meta">
                                        <span class="as-pill__count">{{ $itineraryCount }} flights</span>
                                    </span>
                                </div>
                            </button>

                            @foreach($airlineMap as $_al)
                            <button class="as-pill" data-as-code="{{ $_al['code'] }}">
                                <div class="as-pill__logo-wrap">
                                    <img class="as-pill__logo"
                                        src="{{ fl_carrier_logo($_al['code']) }}"
                                        loading="lazy"
                                        alt="{{ $_al['name'] }}"
                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($_al['code']) }}&background=cd1b4f&color=fff&size=80'">
                                </div>
                                <div class="as-pill__body">
                                    <span class="as-pill__name">{{ $_al['name'] }}</span>
                                    <span class="as-pill__meta">
                                        <span class="as-pill__count">{{ $_al['count'] }}</span>
                                        <span class="as-pill__sep">·</span>
                                        <span class="as-pill__price">
                                            {{ $currencyCode === 'AED' ? 'AED' : $currencyCode }}
                                            {{ number_format($_al['min_price'], 0) }}
                                        </span>
                                    </span>
                                </div>
                            </button>
                            @endforeach

                        </div>
                    </div>

                    <button class="as-arrow as-arrow--next" id="as-next" aria-label="Scroll right">
                        <i class="bx bx-chevron-right"></i>
                    </button>
                </div>

                {{-- toolbar --}}
                <div class="rp-bar">
                    <span class="rp-bar__count">
                        Showing <strong>{{ $itineraryCount }}</strong> of <strong>{{ $itineraryCount }}</strong> Fares found
                    </span>
                    <div class="rp-bar__sort">
                        <span class="rp-bar__sortlabel">SORT BY:</span>
                        <div class="rp-sortrow">
                            <button class="rp-sortbtn" data-rp-sort="airline">Airline</button>
                            <button class="rp-sortbtn" data-rp-sort="departure-o">Departure</button>
                            <button class="rp-sortbtn" data-rp-sort="duration-o-asc">Duration</button>
                            <button class="rp-sortbtn" data-rp-sort="best-value">Best Value</button>
                            <button class="rp-sortbtn" data-rp-sort="arrival-o">Arrival</button>
                            <button class="rp-sortbtn rp-sortbtn--active" data-rp-sort="price-asc" data-rp-cycle>
                                Price <i class="bx bx-down-arrow-alt rp-sortbtn__dir"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- results --}}
                <div id="rp-list">
                    @foreach ($results as $result)
                        @php
                            $lid        = $result['id'];
                            $meta       = $result['listing_meta'] ?? [];
                            $legs       = $result['legs'] ?? [];
                            $firstSeg   = $legs[0]['segments'][0] ?? [];

                            $seatList = [];
                            foreach ($legs as $lg) {
                                foreach ($lg['segments'] ?? [] as $sx) {
                                    if (isset($sx['seats_available'])) $seatList[] = (int) $sx['seats_available'];
                                }
                            }
                            $seatMin    = !empty($seatList) ? min($seatList) : null;
                            $nonRefund  = (bool) ($result['non_refundable'] ?? false);
                            $bagNote    = $result['baggage_notes'] ?? '';
                            $totalPrice = $result['totalPrice'] ?? 0;
                            $cardCur    = strtoupper((string) ($result['currency'] ?? $currencyCode));
                            $cabinTop   = $firstSeg['cabin_code'] ?? 'Y';
                            $rbdTop     = $firstSeg['booking_code'] ?? '';

                            // Modal tab labels: "DXB → KHI" per leg
                            $legRoutes = [];
                            foreach ($legs as $li2 => $lg2) {
                                $sg2 = $lg2['segments'] ?? [];
                                $f2  = $sg2[0] ?? [];
                                $l2  = !empty($sg2) ? $sg2[array_key_last($sg2)] : [];
                                $legRoutes[] = [
                                    'from' => $f2['from'] ?? ' - ',
                                    'to'   => $l2['to']   ?? ' - ',
                                    'label'=> $li2 === 0 ? 'Outbound' : 'Return',
                                    'icon' => $li2 === 0 ? 'bxs-plane-take-off' : 'bxs-plane-land',
                                ];
                            }
                        @endphp

                        <div class="rc" data-rp-meta='@json($meta)'>

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
                                    $isRedEye   = (bool)($s0['is_red_eye_segment'] ?? false);
                                    $nextDay    = (bool)($sLast['next_day_hint'] ?? false);

                                    $midApts = [];
                                    for ($mi = 0; $mi < count($segs) - 1; $mi++) {
                                        $midApts[] = $segs[$mi]['to'] ?? '';
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
                                            {{ $s0['departure_clock'] ?? ' - ' }}
                                            @if ($isRedEye)<i class="bx bxs-moon rc__moon"></i>@endif
                                        </div>
                                        <div class="rc__dt">{{ $s0['departure_weekday']??'' }}, {{ $s0['departure_label']??'' }}</div>
                                        <div class="rc__city">
                                            {{ fl_city_label($s0, true) }}@if(!empty($s0['departure_terminal'])), T{{ $s0['departure_terminal'] }}@endif
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
                                            {{ $sLast['arrival_clock'] ?? ' - ' }}
                                            @if($nextDay)<span class="rc__nextday">Next Day</span>@endif
                                        </div>
                                        <div class="rc__dt">{{ $sLast['arrival_weekday']??'' }}, {{ $sLast['arrival_label']??'' }}</div>
                                        <div class="rc__city">
                                            {{ fl_city_label($sLast, false) }}@if(!empty($sLast['arrival_terminal'])), T{{ $sLast['arrival_terminal'] }}@endif
                                        </div>
                                    </div>

                                    {{-- moon col --}}
                                    <div class="rc__redeyecol">
                                        @if($isRedEye)<i class="bx bxs-moon"></i>@endif
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

                            {{-- ── fare row ── --}}
                            <div class="rc__fare">
                                <div class="rc__fare-left">
                                    @if($nonRefund)
                                        <span class="rc__fbadge rc__fbadge--nr">Non-Refundable</span>
                                    @else
                                        <span class="rc__fbadge rc__fbadge--ref">Refundable</span>
                                    @endif
                                    @if(!empty($cabinTop))
                                        <span class="rc__ftag">{{ $cabinTop }}</span>
                                    @endif
                                    @if(!empty($rbdTop))
                                        <span class="rc__ftag">Class {{ $rbdTop }}</span>
                                    @endif
                                    @if(!empty($bagNote))
                                        <span class="rc__ftag"><i class="bx bx-briefcase-alt-2"></i> {{ $bagNote }}</span>
                                    @endif
                                    @if(!is_null($seatMin))
                                        <span class="rc__ftag rc__ftag--seat"><i class="bx bx-user"></i> {{ $seatMin }} seats</span>
                                    @endif
                                </div>

                                <div class="rc__fare-right">
                                    <div class="rc__price">
                                        <div class="rc__price-label">NET FARE</div>
                                        <div class="rc__price-amount">
                                            @if($cardCur === 'AED')
                                                <span class="dirham">AED</span>
                                            @else
                                                <span class="rc__price-cur">{{ $cardCur }}</span>
                                            @endif
                                            {{ number_format($totalPrice, 2) }}
                                        </div>
                                    </div>
                                    <a href="{{ route('user.flights.hold', ['itinerary' => $lid] + $query) }}"
                                        class="rc__hold">
                                        <i class="bx bx-time-five"></i> Hold
                                    </a>
                                    <a href="{{ route('user.flights.checkout', ['itinerary' => $lid] + $query) }}"
                                        class="rc__cta">
                                        Book Now <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                </div>
                            </div>

                        </div>{{-- /.rc --}}

                        {{-- ── FLIGHT DETAILS MODAL (fixed, escapes overflow) ── --}}
                        <div class="fd-modal" id="fd-{{ $lid }}" hidden aria-modal="true" role="dialog">
                            <div class="fd-backdrop" data-fd-close="fd-{{ $lid }}"></div>
                            <div class="fd-box">

                                <div class="fd-head">
                                    <div class="fd-head__title">
                                        <i class="bx bxs-plane"></i>
                                        {{ $legRoutes[0]['from'] }} → {{ $legRoutes[0]['to'] }}
                                        @if(count($legRoutes) > 1)
                                            ⇄ {{ $legRoutes[1]['from'] }} → {{ $legRoutes[1]['to'] }}
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
                                            <span class="fd-tab__route">{{ $lr['from'] }} → {{ $lr['to'] }}</span>
                                        </button>
                                    @endforeach
                                    <button class="fd-tab" data-fd-tab="baggage">
                                        <i class="bx bx-briefcase-alt-2"></i>
                                        Baggage &amp; Info
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
                                                    $layover = $sIdx > 0 ? fl_layover_minutes($segs[$sIdx-1], $sx) : null;
                                                @endphp

                                                @if($layover !== null)
                                                    <div class="fd-layover">
                                                        <i class="bx bx-time-five"></i>
                                                        <strong>{{ fl_format_hm($layover) }}</strong> layover at
                                                        <strong>{{ $sx['from']??'' }}</strong>
                                                        @if(!empty($sx['departure_city'])) · {{ $sx['departure_city'] }}@endif
                                                    </div>
                                                @endif

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
                                                                @if(!empty($sx['equipment'])) · {{ $sx['equipment'] }}@endif
                                                            </div>
                                                        </div>
                                                        <div class="fd-seg__chips">
                                                            @if(!empty($sx['cabin_code']))<span class="fd-chip fd-chip--cab">{{ $sx['cabin_code'] }}</span>@endif
                                                            @if(!empty($sx['booking_code']))<span class="fd-chip">Class {{ $sx['booking_code'] }}</span>@endif
                                                            @if(isset($sx['seats_available']))<span class="fd-chip fd-chip--seat"><i class="bx bx-group"></i> {{ (int)$sx['seats_available'] }} left</span>@endif
                                                        </div>
                                                    </div>

                                                    <div class="fd-seg__route">
                                                        <div class="fd-seg__pt">
                                                            <strong class="fd-seg__time">{{ $sx['departure_clock']??' - ' }}</strong>
                                                            <span class="fd-seg__code">{{ $sx['from']??'' }}</span>
                                                            <span class="fd-seg__city">
                                                                @if(!empty($sx['departure_city'])){{ $sx['departure_city'] }}@endif
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
                                                            <strong class="fd-seg__time">{{ $sx['arrival_clock']??' - ' }}</strong>
                                                            <span class="fd-seg__code">{{ $sx['to']??'' }}</span>
                                                            <span class="fd-seg__city">
                                                                @if(!empty($sx['arrival_city'])){{ $sx['arrival_city'] }}@endif
                                                                @if(!empty($sx['arrival_terminal']))<small>Terminal {{ $sx['arrival_terminal'] }}</small>@endif
                                                            </span>
                                                            <span class="fd-seg__date">{{ $sx['arrival_label']??'' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    {{-- baggage panel --}}
                                    <div class="fd-panel fd-panel--hidden" data-fd-panel="baggage">
                                        <div class="fd-bag">
                                            <div class="fd-bag__row">
                                                <i class="bx bx-briefcase-alt-2 fd-bag__icon"></i>
                                                <div>
                                                    <div class="fd-bag__label">Checked Baggage</div>
                                                    <div class="fd-bag__val">{{ !empty($bagNote) ? $bagNote : 'See fare rules' }}</div>
                                                </div>
                                            </div>
                                            <div class="fd-bag__row">
                                                <i class="bx bx-shopping-bag fd-bag__icon"></i>
                                                <div>
                                                    <div class="fd-bag__label">Cabin / Hand Baggage</div>
                                                    <div class="fd-bag__val">1 piece  -  as per airline policy</div>
                                                </div>
                                            </div>
                                            <div class="fd-bag__row">
                                                <i class="bx bx-receipt fd-bag__icon"></i>
                                                <div>
                                                    <div class="fd-bag__label">Fare Type</div>
                                                    <div class="fd-bag__val">
                                                        {{ !empty($cabinTop) ? $cabinTop : '' }}
                                                        @if(!empty($rbdTop)) · Class {{ $rbdTop }}@endif
                                                        &nbsp;·&nbsp;
                                                        @if($nonRefund)
                                                            <span style="color:#c0143c;font-weight:700;">Non-Refundable</span>
                                                        @else
                                                            <span style="color:#0f9d58;font-weight:700;">Refundable</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if(!is_null($seatMin))
                                                <div class="fd-bag__row">
                                                    <i class="bx bx-user fd-bag__icon"></i>
                                                    <div>
                                                        <div class="fd-bag__label">Seats Available</div>
                                                        <div class="fd-bag__val">{{ $seatMin }} seats remaining</div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                </div>{{-- /.fd-body --}}

                                <div class="fd-foot">
                                    <div class="fd-foot__price">
                                        <span class="fd-foot__label">Total</span>
                                        <span class="fd-foot__amount">
                                            @if($cardCur === 'AED')
                                                <span class="dirham">AED</span>
                                            @else
                                                {{ $cardCur }}
                                            @endif
                                            {{ number_format($totalPrice, 2) }}
                                        </span>
                                    </div>
                                    <div class="fd-foot__btns">
                                        <a href="{{ route('user.flights.hold', ['itinerary' => $lid] + $query) }}"
                                            class="fd-foot__hold">
                                            <i class="bx bx-time-five"></i> Hold
                                        </a>
                                        <a href="{{ route('user.flights.checkout', ['itinerary' => $lid] + $query) }}"
                                            class="fd-foot__book">
                                            Book Now <i class="bx bx-right-arrow-alt"></i>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>{{-- /.fd-modal --}}

                    @endforeach
                </div>

            @elseif($hasSearch && empty($results))
                <div class="rp-empty">
                    <i class="bx bx-search-alt-2 rp-empty__icon"></i>
                    <h3>No flights found</h3>
                    <p>We couldn't find itineraries for this route. Try different dates or nearby airports.</p>
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
    --c-bg:          #f3f5fb;
    --c-white:       #ffffff;
    --c-green:       #0f9d58;
    --c-green-soft:  #e8f9f1;
    --c-amber:       #d97706;
    --c-amber-soft:  #fef3c7;
    --c-blue:        #2563eb;
    --c-blue-soft:   #eff6ff;
    --c-violet:      #7c3aed;
    --c-violet-soft: #ede9fe;
    --c-shadow:      0 2px 8px rgba(26,37,64,.07);
    --c-shadow-hov:  0 6px 22px rgba(26,37,64,.12);
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
.rp-swrap { background: #fff; border-bottom: 1px solid var(--c-line); }
.rp-shell { padding-top: 1.1rem; }

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
    padding: .55rem .75rem;
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 12px;
    box-shadow: var(--c-shadow);
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
.rp-sortbtn__dir { transition: transform .2s ease; }
.rp-sortbtn.is-desc .rp-sortbtn__dir { transform: rotate(180deg); }

/* =========================================================
   CARD
   ========================================================= */
#rp-list { display: flex; flex-direction: column; gap: .75rem; }

.rc {
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    box-shadow: var(--c-shadow);
    overflow: hidden;
    transition: box-shadow .15s, border-color .15s;
}
.rc:hover { border-color: rgba(205,27,79,.25); box-shadow: var(--c-shadow-hov); }

/* leg row */
.rc__leg {
    display: grid;
    grid-template-columns: 175px 1fr 170px 1fr 28px;
    align-items: center;
    gap: .5rem 1rem;
    padding: .9rem 1.1rem;
    border-bottom: 1px solid var(--c-line);
}
.rc__leg--ret { background: var(--c-white); }
/* last leg has no bottom border  -  the details-center row provides the separator */
.rc__leg { border-bottom: 1px solid var(--c-line); }

/* airline */
.rc__airline { display: flex; gap: .7rem; align-items: center; min-width: 0; }

.rc__logo-wrap {
    width: 52px; height: 52px; flex-shrink: 0;
    border-radius: 12px;
    border: 1.5px solid var(--c-line);
    background: #fff;
    box-shadow: 0 2px 8px rgba(26,37,64,.08);
    display: flex; align-items: center; justify-content: center;
    padding: 4px;
    overflow: hidden;
}
.rc__logo { width: 100%; height: 100%; object-fit: contain; display: block; }

.rc__aname {
    font-size: .84rem; font-weight: 700; color: var(--c-ink);
    line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rc__aflight { font-family: var(--mono); font-size: .69rem; color: var(--c-muted); margin-top: .08rem; }

/* dep / arr */
.rc__point { min-width: 0; }
.rc__point--arr { text-align: right; }
.rc__time {
    font-family: var(--mono); font-size: 1.3rem; font-weight: 700;
    color: var(--c-ink); line-height: 1;
    display: inline-flex; align-items: center; gap: .3rem;
}
.rc__moon { font-size: .85rem; color: #6366f1; }
.rc__nextday {
    font-size: .58rem; font-weight: 700;
    background: var(--c-amber-soft); color: var(--c-amber);
    padding: .05rem .3rem; border-radius: 4px; margin-left: .2rem;
    letter-spacing: .04em; text-transform: uppercase; font-family: var(--sans);
}
.rc__dt { font-size: .71rem; color: var(--c-muted); margin-top: .18rem; white-space: nowrap; }
.rc__city {
    font-size: .74rem; color: var(--c-slate); font-weight: 500; margin-top: .05rem;
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

/* redeye col */
.rc__redeyecol { display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #6366f1; }

/* fare row */
.rc__fare {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .5rem 1rem;
    padding: .6rem 1.1rem;
    background: #f8fafd;
    border-top: 1px solid var(--c-line);
}
.rc__fare-left  { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.rc__fare-right { display: flex; align-items: center; gap: .6rem; }

.rc__fbadge {
    font-size: .6rem; font-weight: 700; padding: .17rem .52rem;
    border-radius: 4px; text-transform: uppercase; letter-spacing: .06em;
}
.rc__fbadge--ref { background: var(--c-green-soft); color: var(--c-green); }
.rc__fbadge--nr  { background: #fff0f3; color: #c0143c; }

.rc__ftag {
    display: inline-flex; align-items: center; gap: .18rem;
    font-size: .68rem; font-weight: 600; background: #edf0f7;
    color: var(--c-slate); padding: .13rem .45rem; border-radius: 4px;
}
.rc__ftag i { font-size: .8rem; }
.rc__ftag--seat { background: var(--c-amber-soft); color: var(--c-amber); }

/* flight details centered link */
.rc__details-center {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .35rem 0;
    border-top: 1px solid var(--c-line);
    border-bottom: 1px solid var(--c-line);
    background: #fff;
}
.rc__details-link {
    border: none; background: transparent; font: inherit;
    font-size: .76rem; font-weight: 600; color: var(--c-brand);
    cursor: pointer; display: inline-flex; align-items: center; gap: .28rem;
    padding: .15rem .4rem;
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
    font-size: .55rem; font-weight: 700; letter-spacing: .14em;
    text-transform: uppercase; color: var(--c-brand); margin-bottom: .06rem;
}
.rc__price-amount {
    font-family: var(--mono); font-size: 1.32rem; font-weight: 700;
    color: var(--c-brand); line-height: 1;
    display: flex; align-items: baseline; gap: .08rem;
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
    border: 2px solid var(--c-amber);
    background: transparent;
    color: var(--c-amber);
    font: inherit;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .28rem;
    padding: .52rem .95rem;
    border-radius: 8px;
    white-space: nowrap;
    transition: background .13s, color .13s;
}
.rc__hold:hover { background: var(--c-amber); color: #fff; }

/* Book Now CTA */
.rc__cta {
    display: inline-flex; align-items: center; gap: .28rem;
    padding: .55rem 1.15rem; border-radius: 8px;
    background: linear-gradient(180deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color: #fff !important; font-weight: 700; font-size: .84rem;
    white-space: nowrap;
    box-shadow: 0 5px 14px rgba(205,27,79,.25);
    transition: transform .13s, box-shadow .13s;
}
.rc__cta:hover { transform: translateY(-1px); box-shadow: 0 9px 20px rgba(205,27,79,.35); }

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
.fd-head, .fd-tabs, .fd-foot { flex-shrink: 0; overflow: hidden; }
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
    box-shadow: 0 5px 14px rgba(205,27,79,.25); transition: transform .13s;
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
.rp-emptybtn {
    display: inline-flex; align-items: center; gap: .3rem;
    background: var(--c-brand); color: #fff !important;
    padding: .6rem 1.2rem; border-radius: 8px;
    font-weight: 700; font-size: .86rem;
    box-shadow: 0 6px 16px rgba(205,27,79,.25);
}

/* =========================================================
   AIRLINE FILTER SLIDER
   ========================================================= */
.as-wrap {
    display: flex;
    align-items: stretch;
    gap: 0;
    margin-bottom: .85rem;
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    box-shadow: var(--c-shadow);
    overflow: hidden;
    position: relative;
}

/* scroll arrows */
.as-arrow {
    flex-shrink: 0;
    width: 36px;
    border: none;
    background: transparent;
    color: var(--c-slate);
    cursor: pointer;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .13s, color .13s;
    z-index: 2;
}
.as-arrow:hover:not(:disabled) { background: var(--c-brand-soft); color: var(--c-brand); }
.as-arrow:disabled { opacity: .3; cursor: default; }
.as-arrow--prev { border-right: 1px solid var(--c-line); }
.as-arrow--next { border-left:  1px solid var(--c-line); }

/* viewport + track */
.as-viewport {
    flex: 1;
    overflow: hidden;
    position: relative;
}
/* fade edges */
.as-viewport::before,
.as-viewport::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0;
    width: 28px;
    pointer-events: none;
    z-index: 1;
}
.as-viewport::before { left: 0;  background: linear-gradient(to right,  #fff 0%, transparent 100%); }
.as-viewport::after  { right: 0; background: linear-gradient(to left,   #fff 0%, transparent 100%); }

.as-track {
    display: flex;
    gap: 0;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: .55rem .5rem;
    gap: .4rem;
}
.as-track::-webkit-scrollbar { display: none; }

/* pill card */
.as-pill {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .48rem .75rem .48rem .52rem;
    border: 1.5px solid var(--c-line);
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    transition: border-color .13s, background .13s, box-shadow .13s, color .13s;
    white-space: nowrap;
    min-width: 0;
    text-align: left;
    box-shadow: 0 1px 3px rgba(26,37,64,.05);
    font: inherit;
}
.as-pill:hover {
    border-color: rgba(205,27,79,.35);
    background: var(--c-brand-soft);
    box-shadow: 0 3px 10px rgba(205,27,79,.1);
}
.as-pill--active {
    border-color: var(--c-brand);
    background: linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    box-shadow: 0 4px 14px rgba(205,27,79,.28);
    color: #fff;
}
.as-pill--active:hover {
    background: linear-gradient(135deg, #d91d56 0%, #b01844 100%);
    border-color: var(--c-brand2);
}

/* logo box */
.as-pill__logo-wrap {
    width: 40px; height: 40px;
    flex-shrink: 0;
    border-radius: 8px;
    border: 1.5px solid var(--c-line);
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    padding: 3px;
    box-shadow: 0 1px 4px rgba(26,37,64,.08);
    transition: border-color .13s;
}
.as-pill__logo-wrap--all {
    background: var(--c-brand-soft);
    border-color: rgba(205,27,79,.2);
    font-size: 1.2rem;
    color: var(--c-brand);
}
.as-pill--active .as-pill__logo-wrap {
    border-color: rgba(255,255,255,.35);
    background: rgba(255,255,255,.18);
}
.as-pill--active .as-pill__logo-wrap--all {
    background: rgba(255,255,255,.2);
    color: #fff;
}
.as-pill__logo {
    width: 100%; height: 100%;
    object-fit: contain; display: block;
}

/* text body */
.as-pill__body {
    display: flex;
    flex-direction: column;
    gap: .08rem;
    min-width: 0;
}
.as-pill__name {
    font-size: .78rem;
    font-weight: 700;
    color: var(--c-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 110px;
    line-height: 1.2;
}
.as-pill--active .as-pill__name { color: #fff; }

.as-pill__meta {
    display: flex;
    align-items: center;
    gap: .2rem;
    font-size: .66rem;
    line-height: 1;
}
.as-pill__count {
    font-weight: 700;
    color: var(--c-brand);
    font-family: var(--mono);
}
.as-pill--active .as-pill__count { color: rgba(255,255,255,.85); }

.as-pill__sep { color: var(--c-muted); opacity: .5; }
.as-pill--active .as-pill__sep { color: rgba(255,255,255,.5); }

.as-pill__price {
    color: var(--c-slate);
    font-weight: 600;
    font-family: var(--mono);
    font-size: .66rem;
}
.as-pill--active .as-pill__price { color: rgba(255,255,255,.9); }

/* filtered-out cards */
.rc.as-hidden {
    display: none;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 991px) {
    .rc__leg { grid-template-columns: 155px 1fr 145px 1fr 24px; gap: .4rem .65rem; }
}
@media (max-width: 767px) {
    .rc__leg {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto auto;
    }
    .rc__airline { grid-column: 1 / -1; }
    .rc__bridge  { grid-column: 1 / -1; flex-direction: row; justify-content: center; gap: .5rem; }
    .rc__btrack  { flex: 1; }
    .rc__redeyecol { display: none; }
    .rc__point--arr { text-align: left; }
    .rc__fare { flex-direction: column; align-items: stretch; }
    .rc__fare-right { justify-content: space-between; }
    .fd-seg__route { grid-template-columns: 1fr; }
    .fd-seg__pt--arr { align-items: flex-start; text-align: left; }
    .fd-box { max-height: 95vh; border-radius: 12px; }
    /* airline slider on mobile */
    .as-arrow { width: 28px; font-size: 1.1rem; }
    .as-pill__name { max-width: 80px; }
    .as-pill { padding: .4rem .55rem .4rem .45rem; gap: .4rem; }
    .as-pill__logo-wrap { width: 34px; height: 34px; }
}
</style>
@endpush

@push('js')
<script>
(function(){
    const list = document.getElementById('rp-list');
    if (!list) return;

    /* ── sort ── */
    function parseMeta(card){
        try { return JSON.parse(card.dataset.rpMeta||'{}'); } catch(e){ return {}; }
    }

    let currentSort = 'price-asc';

    function sortCards(){
        const items = [...list.querySelectorAll('.rc')];
        items.sort((a,b)=>{
            const ma=parseMeta(a), mb=parseMeta(b);
            switch(currentSort){
                case 'price-desc':     return (Number(mb.price)||0)-(Number(ma.price)||0);
                case 'duration-o-asc': return (Number(ma.dur_o)||9e9)-(Number(mb.dur_o)||9e9);
                case 'departure-o':    return String(ma.first_dep_iso||'').localeCompare(String(mb.first_dep_iso||''));
                case 'arrival-o':      return String(ma.first_arr_iso||'').localeCompare(String(mb.first_arr_iso||''));
                case 'airline':        return String((ma.al||[])[0]||'').localeCompare(String((mb.al||[])[0]||''));
                case 'best-value': {
                    const va=(Number(ma.price)||9e9)/Math.max(1,Number(ma.dur_o)||1);
                    const vb=(Number(mb.price)||9e9)/Math.max(1,Number(mb.dur_o)||1);
                    return va-vb;
                }
                default: return (Number(ma.price)||0)-(Number(mb.price)||0);
            }
        });
        items.forEach(el=>list.appendChild(el));
    }

    document.querySelectorAll('.rp-sortbtn[data-rp-sort]').forEach(btn=>{
        btn.addEventListener('click',()=>{
            let target = btn.dataset.rpSort;
            const canCycle = btn.hasAttribute('data-rp-cycle');
            if(canCycle && (btn.classList.contains('is-active')||btn.classList.contains('rp-sortbtn--active'))){
                if(target==='price-asc'){ target='price-desc'; btn.classList.add('is-desc'); }
                else { target='price-asc'; btn.classList.remove('is-desc'); }
                btn.dataset.rpSort = target;
            } else if(canCycle){
                btn.classList.remove('is-desc');
            }
            document.querySelectorAll('.rp-sortbtn').forEach(b=>b.classList.remove('rp-sortbtn--active','is-active'));
            btn.classList.add('is-active');
            currentSort = target;
            sortCards();
        });
    });

    /* ── modal ── */
    function openModal(id){
        const m = document.getElementById(id);
        if(!m) return;
        m.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id){
        const m = document.getElementById(id);
        if(!m) return;
        m.hidden = true;
        document.body.style.overflow = '';
    }

    // Open triggers
    document.querySelectorAll('[data-fd-open]').forEach(btn=>{
        btn.addEventListener('click', ()=> openModal(btn.dataset.fdOpen));
    });

    // Close triggers (backdrop + × button)
    document.querySelectorAll('[data-fd-close]').forEach(el=>{
        el.addEventListener('click', ()=> closeModal(el.dataset.fdClose));
    });

    // Escape key
    document.addEventListener('keydown', e=>{
        if(e.key === 'Escape'){
            document.querySelectorAll('.fd-modal:not([hidden])').forEach(m=> closeModal(m.id));
        }
    });

    // Tab switching inside any modal
    document.querySelectorAll('.fd-modal').forEach(modal=>{
        modal.querySelectorAll('.fd-tab').forEach(tab=>{
            tab.addEventListener('click', ()=>{
                const panelKey = tab.dataset.fdTab;

                // activate tab
                modal.querySelectorAll('.fd-tab').forEach(t=>t.classList.remove('fd-tab--active'));
                tab.classList.add('fd-tab--active');

                // show panel
                modal.querySelectorAll('.fd-panel').forEach(p=>{
                    p.classList.toggle('fd-panel--hidden', p.dataset.fdPanel !== panelKey);
                });
            });
        });
    });

    sortCards();

    /* ── Airline slider filter ── */
    (function(){
        const track    = document.getElementById('as-track');
        const viewport = document.getElementById('as-viewport');
        const btnPrev  = document.getElementById('as-prev');
        const btnNext  = document.getElementById('as-next');
        const countEl  = document.querySelector('.rp-bar__count strong:first-child');
        if (!track) return;

        const SCROLL_STEP = 260;

        function updateArrows(){
            const sv = track.scrollLeft;
            const maxScroll = track.scrollWidth - track.clientWidth;
            if (btnPrev) btnPrev.disabled = sv <= 2;
            if (btnNext) btnNext.disabled = sv >= maxScroll - 2;
        }
        track.addEventListener('scroll', updateArrows, {passive:true});
        updateArrows();

        if (btnPrev) btnPrev.addEventListener('click', ()=>{
            track.scrollBy({left: -SCROLL_STEP, behavior:'smooth'});
        });
        if (btnNext) btnNext.addEventListener('click', ()=>{
            track.scrollBy({left:  SCROLL_STEP, behavior:'smooth'});
        });

        /* filter cards */
        let activeCode = 'all';

        document.querySelectorAll('.as-pill[data-as-code]').forEach(pill=>{
            pill.addEventListener('click', ()=>{
                const code = pill.dataset.asCode;
                if (code === activeCode) return;
                activeCode = code;

                /* update active pill */
                document.querySelectorAll('.as-pill').forEach(p=>p.classList.remove('as-pill--active'));
                pill.classList.add('as-pill--active');

                /* filter cards */
                let visible = 0;
                document.querySelectorAll('#rp-list .rc').forEach(card=>{
                    if (code === 'all'){
                        card.classList.remove('as-hidden');
                        visible++;
                    } else {
                        const meta = parseMeta(card);
                        const airlines = Array.isArray(meta.al) ? meta.al.map(c=>String(c).toUpperCase()) : [];
                        const match = airlines.includes(code);
                        card.classList.toggle('as-hidden', !match);
                        if (match) visible++;
                    }
                });

                /* update showing count */
                const allCount = document.querySelectorAll('#rp-list .rc').length;
                const first = document.querySelector('.rp-bar__count strong:first-child');
                if (first) first.textContent = visible;
            });
        });
    })();

})();
</script>
@endpush
