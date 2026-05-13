@extends('user.layouts.main')
@section('content')
    @php
        /**
         * ============================================================
         * FLIGHT LISTING — view-only helpers and incoming variables
         * ============================================================
         */
        $query = request()->query();
        /** @var string $tripType */
        $tripType = $tripType ?? ($query['trip_type'] ?? 'one_way');
        $isRoundTrip = $tripType === 'round_trip';

        /** @var array<int,array<string,mixed>> $results */
        $results = $results ?? [];

        /** @var array{min:float,max:float} $priceRange */
        $priceRange = $priceRange ?? ['min' => 0.0, 'max' => 1.0];

        /** @var array<string,mixed> $filterCatalog */
        $filterCatalog = $filterCatalog ?? [
            'airlines' => [],
            'dep_out' => [],
            'dep_ret' => [],
            'conn_out' => [],
            'conn_ret' => [],
        ];

        $itineraryCount = $itineraryCount ?? count($results);
        $messages = $messages ?? [];

        $currencyCode = strtoupper((string) ($results[0]['currency'] ?? 'BDT'));

        $hasSearch = !empty($query['from']) || !empty($query['to']) || !empty($query['departure_date']);

        function fl_format_hm(?int $mins): string
        {
            if ($mins === null || $mins < 1) {
                return '—';
            }

            $m = (int) $mins;
            $h = intdiv($m, 60);
            $r = $m % 60;
            if ($h > 0 && $r === 0) {
                return "{$h}h";
            }

            if ($h === 0) {
                return "{$r}m";
            }

            return "{$h}h {$r}m";
        }

        function fl_segment_minutes(array $seg): ?int
        {
            $dep = $seg['departure_datetime'] ?? null;
            $arr = $seg['arrival_datetime'] ?? null;
            if (!$dep || !$arr) {
                return null;
            }

            try {
                $d = \Carbon\Carbon::parse($dep);
                $a = \Carbon\Carbon::parse($arr);

                return max(0, (int) $d->diffInMinutes($a, false));
            } catch (\Throwable $e) {
                return null;
            }
        }

        function fl_layover_minutes(array $prev, array $next): ?int
        {
            $arr = $prev['arrival_datetime'] ?? null;
            $dep = $next['departure_datetime'] ?? null;
            if (!$arr || !$dep) {
                return null;
            }

            try {
                $a = \Carbon\Carbon::parse($arr);
                $d = \Carbon\Carbon::parse($dep);

                return max(0, (int) $a->diffInMinutes($d, false));
            } catch (\Throwable $e) {
                return null;
            }
        }

        function fl_carrier_logo(?string $code): string
        {
            $c = strtoupper(trim((string) ($code ?: 'XX')));

            return "https://pics.avs.io/120/120/{$c}.png";
        }

        function fl_city(array $seg, string $fallbackCode): string
        {
            $c = trim((string) ($seg['departure_city'] ?? $seg['arrival_city'] ?? ''));

            return $c !== '' ? $c : $fallbackCode;
        }

        function fl_stops_phrase(int $stops): string
        {
            if ($stops <= 0) {
                return 'Non-stop';
            }

            if ($stops === 1) {
                return '1 stop';
            }

            return $stops . ' stops';
        }
    @endphp

    <div class="fl-page">

        {{-- ============================================================
             SEARCH FORM (untouched — Vue mount)
             ============================================================ --}}
        <div class="fl-search-wrap">
            <div class="container">
                @include('user.vue.main', [
                    'appId' => 'flights-search',
                    'appComponent' => 'flights-search',
                    'appJs' => 'flights-search',
                    'flightSearchListingMode' => true,
                ])
            </div>
        </div>

        <div class="container fl-shell">

            {{-- ============================================================
                 ALERT MESSAGES
                 ============================================================ --}}
            @if (!empty($messages))
                <div class="fl-alerts">
                    @foreach ($messages as $msg)
                        @php $sev = strtoupper(trim((string) ($msg['severity'] ?? ''))); @endphp
                        <div
                            class="fl-alert {{ $sev === 'ERROR' ? 'fl-alert--err' : 'fl-alert--info' }}">
                            <i class="bx {{ $sev === 'ERROR' ? 'bx-error-circle' : 'bx-info-circle' }}"></i>
                            <span>{{ $msg['text'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (($itineraryCount ?? 0) > 0)

                {{-- ============================================================
                     ADVISORY NOTE
                     ============================================================ --}}
                <div class="fl-notice">
                    <span class="fl-notice__icon"><i class="bx bxs-megaphone"></i></span>
                    <p>
                        <strong>Note:</strong> Creating multiple bookings for the same passenger
                        on the same airline may result in ADM. The penalty will be debited to the agent.
                    </p>
                </div>

                {{-- ============================================================
                     RESULTS TOP-BAR: COUNT + SORT
                     ============================================================ --}}
                <div class="fl-toolbar">
                    <div class="fl-toolbar__left">
                        <span class="fl-toolbar__count">
                            <span class="fl-toolbar__count-num"
                                id="fl-visible">{{ $itineraryCount }}</span>
                            <span>of</span>
                            <strong>{{ $itineraryCount }}</strong>
                            <span>fares</span>
                        </span>
                        <span class="fl-toolbar__live">
                            <span class="fl-toolbar__dot"></span>
                            Live Sabre inventory
                        </span>
                    </div>

                    <div class="fl-toolbar__right">
                        <span class="fl-toolbar__sortlabel">Sort By:</span>
                        <div class="fl-sort">
                            <button type="button" class="fl-sort__btn" data-fl-sort="airline">
                                <i class="bx bxs-plane"></i> Airline
                            </button>
                            <button type="button" class="fl-sort__btn" data-fl-sort="departure-o">
                                <i class="bx bx-time"></i> Departure
                            </button>
                            <button type="button" class="fl-sort__btn" data-fl-sort="duration-o-asc">
                                <i class="bx bx-stopwatch"></i> Duration
                            </button>
                            <button type="button" class="fl-sort__btn" data-fl-sort="best-value">
                                <i class="bx bxs-medal"></i> Best Value
                            </button>
                            <button type="button" class="fl-sort__btn is-active" data-fl-sort="price-asc"
                                data-fl-cycle>
                                <i class="bx bxs-purchase-tag"></i> Price
                                <i class="bx bx-down-arrow-alt fl-sort__dir"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
                     GRID: SIDEBAR + RESULTS
                     ============================================================ --}}
                <div class="fl-grid">

                    {{-- ───────────── SIDEBAR ───────────── --}}
                    <aside class="fl-side" id="fl-side">
                        <div class="fl-side__inner">

                            <header class="fl-side__head">
                                <div>
                                    <span class="fl-side__eyebrow">
                                        <span class="fl-side__eyebrow-dot"></span> Refine
                                    </span>
                                    <h3 class="fl-side__title">Filter Your Search</h3>
                                </div>
                                <button type="button" class="fl-side__reset" id="fl-reset"
                                    title="Reset all filters">
                                    <i class="bx bx-revision"></i> Reset
                                </button>
                            </header>

                            @if ($isRoundTrip)
                                <div class="fl-side__tabs" role="tablist">
                                    <button type="button" class="fl-side__tab is-active"
                                        data-fl-leg="out">
                                        <i class="bx bxs-plane-take-off"></i> Onward
                                    </button>
                                    <button type="button" class="fl-side__tab" data-fl-leg="ret">
                                        <i class="bx bxs-plane-land"></i> Return
                                    </button>
                                </div>
                            @endif

                            {{-- Stops --}}
                            <section class="fl-fset" data-fl-fset>
                                <h4 class="fl-fset__title">
                                    <span><i class="bx bx-shuffle"></i> Stops</span>
                                    <i class="bx bx-chevron-down fl-fset__caret"></i>
                                </h4>
                                <div class="fl-fset__body">
                                    <div class="fl-stops" data-fl-leg-panel="out">
                                        @foreach ([0 => 'Non-stop', 1 => '1 stop', 2 => '2+'] as $tier => $lbl)
                                            <label class="fl-chip-pick">
                                                <input type="checkbox" class="fl-filter"
                                                    data-fl-axis="stops-o" value="{{ $tier }}" checked>
                                                <span>{{ $lbl }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($isRoundTrip)
                                        <div class="fl-stops fl-only-ret"
                                            data-fl-leg-panel="ret">
                                            @foreach ([0 => 'Non-stop', 1 => '1 stop', 2 => '2+'] as $tier => $lbl)
                                                <label class="fl-chip-pick">
                                                    <input type="checkbox" class="fl-filter"
                                                        data-fl-axis="stops-r" value="{{ $tier }}" checked>
                                                    <span>{{ $lbl }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </section>

                            {{-- Departure times --}}
                            <section class="fl-fset" data-fl-fset>
                                <h4 class="fl-fset__title">
                                    <span><i class="bx bxs-sun"></i> Departure Times</span>
                                    <i class="bx bx-chevron-down fl-fset__caret"></i>
                                </h4>
                                <div class="fl-fset__body">
                                    <div class="fl-tgrid" data-fl-leg-panel="out">
                                        @foreach (
                                            [
                                                1 => ['Morning', '05–12', 'bxs-sun'],
                                                2 => ['Afternoon', '12–18', 'bxs-cloud-light-rain'],
                                                3 => ['Evening', '18–24', 'bxs-cloud'],
                                                4 => ['Night', '24–05', 'bxs-moon'],
                                            ]
                                            as $b => $info
                                        )
                                            <label class="fl-tchip">
                                                <input type="checkbox" class="fl-filter"
                                                    data-fl-axis="dep-o" data-fl-bucket="{{ $b }}"
                                                    value="{{ $b }}" checked>
                                                <span class="fl-tchip__inner">
                                                    <i class="bx {{ $info[2] }}"></i>
                                                    <strong>{{ $info[0] }}</strong>
                                                    <small>{{ $info[1] }}</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($isRoundTrip)
                                        <div class="fl-tgrid fl-only-ret"
                                            data-fl-leg-panel="ret">
                                            @foreach (
                                                [
                                                    1 => ['Morning', '05–12', 'bxs-sun'],
                                                    2 => ['Afternoon', '12–18', 'bxs-cloud-light-rain'],
                                                    3 => ['Evening', '18–24', 'bxs-cloud'],
                                                    4 => ['Night', '24–05', 'bxs-moon'],
                                                ]
                                                as $b => $info
                                            )
                                                <label class="fl-tchip">
                                                    <input type="checkbox" class="fl-filter"
                                                        data-fl-axis="dep-r" data-fl-bucket="{{ $b }}"
                                                        value="{{ $b }}" checked>
                                                    <span class="fl-tchip__inner">
                                                        <i class="bx {{ $info[2] }}"></i>
                                                        <strong>{{ $info[0] }}</strong>
                                                        <small>{{ $info[1] }}</small>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </section>

                            {{-- Arrival times --}}
                            <section class="fl-fset" data-fl-fset>
                                <h4 class="fl-fset__title">
                                    <span><i class="bx bxs-plane-land"></i> Arrival Times</span>
                                    <i class="bx bx-chevron-down fl-fset__caret"></i>
                                </h4>
                                <div class="fl-fset__body">
                                    <div class="fl-tgrid" data-fl-leg-panel="out">
                                        @foreach (
                                            [
                                                1 => ['Morning', '05–12', 'bxs-sun'],
                                                2 => ['Afternoon', '12–18', 'bxs-cloud-light-rain'],
                                                3 => ['Evening', '18–24', 'bxs-cloud'],
                                                4 => ['Night', '24–05', 'bxs-moon'],
                                            ]
                                            as $b => $info
                                        )
                                            <label class="fl-tchip">
                                                <input type="checkbox" class="fl-filter"
                                                    data-fl-axis="arr-o" data-fl-bucket="{{ $b }}"
                                                    value="{{ $b }}" checked>
                                                <span class="fl-tchip__inner">
                                                    <i class="bx {{ $info[2] }}"></i>
                                                    <strong>{{ $info[0] }}</strong>
                                                    <small>{{ $info[1] }}</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($isRoundTrip)
                                        <div class="fl-tgrid fl-only-ret"
                                            data-fl-leg-panel="ret">
                                            @foreach (
                                                [
                                                    1 => ['Morning', '05–12', 'bxs-sun'],
                                                    2 => ['Afternoon', '12–18', 'bxs-cloud-light-rain'],
                                                    3 => ['Evening', '18–24', 'bxs-cloud'],
                                                    4 => ['Night', '24–05', 'bxs-moon'],
                                                ]
                                                as $b => $info
                                            )
                                                <label class="fl-tchip">
                                                    <input type="checkbox" class="fl-filter"
                                                        data-fl-axis="arr-r" data-fl-bucket="{{ $b }}"
                                                        value="{{ $b }}" checked>
                                                    <span class="fl-tchip__inner">
                                                        <i class="bx {{ $info[2] }}"></i>
                                                        <strong>{{ $info[0] }}</strong>
                                                        <small>{{ $info[1] }}</small>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </section>

                            {{-- Price --}}
                            <section class="fl-fset" data-fl-fset>
                                <h4 class="fl-fset__title">
                                    <span><i class="bx bxs-purchase-tag"></i> Price Range</span>
                                    <i class="bx bx-chevron-down fl-fset__caret"></i>
                                </h4>
                                <div class="fl-fset__body">
                                    @php
                                        $pmin = (float) $priceRange['min'];
                                        $pmax = max((float) $priceRange['max'], $pmin + 1);
                                    @endphp
                                    <input type="range" id="fl-price" class="fl-range"
                                        min="{{ floor($pmin) }}" max="{{ ceil($pmax) }}"
                                        value="{{ ceil($pmax) }}" step="1">
                                    <div class="fl-range__labels">
                                        <span class="fl-range__min">{{ $currencyCode }}
                                            {{ number_format($pmin, 0) }}</span>
                                        <span class="fl-range__cap">
                                            <span class="fl-range__cap-cur">{{ $currencyCode }}</span>
                                            <span id="fl-price-label">{{ number_format($pmax, 0) }}</span>
                                        </span>
                                    </div>
                                </div>
                            </section>

                            {{-- Airlines --}}
                            @if (!empty($filterCatalog['airlines']))
                                <section class="fl-fset" data-fl-fset>
                                    <h4 class="fl-fset__title">
                                        <span><i class="bx bxs-plane"></i> Airlines</span>
                                        <i class="bx bx-chevron-down fl-fset__caret"></i>
                                    </h4>
                                    <div class="fl-fset__body">
                                        <div class="fl-scroll">
                                            @foreach ($filterCatalog['airlines'] as $al)
                                                <label class="fl-check fl-check--al">
                                                    <input type="checkbox" class="fl-filter"
                                                        data-fl-axis="airline"
                                                        value="{{ $al['code'] ?? '' }}" checked>
                                                    <img class="fl-check__logo"
                                                        src="{{ fl_carrier_logo($al['code'] ?? '') }}"
                                                        loading="lazy" alt="" onerror="this.style.visibility='hidden'">
                                                    <span class="fl-check__label">
                                                        <span>{{ strtoupper((string) ($al['code'] ?? '')) }}</span>
                                                    </span>
                                                    <span class="fl-check__count">{{ $al['count'] ?? 0 }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </section>
                            @endif

                            {{-- Departure airports --}}
                            @if (!empty($filterCatalog['dep_out']) || (!empty($filterCatalog['dep_ret']) && $isRoundTrip))
                                <section class="fl-fset" data-fl-fset>
                                    <h4 class="fl-fset__title">
                                        <span><i class="bx bx-buildings"></i> Departure Airports</span>
                                        <i class="bx bx-chevron-down fl-fset__caret"></i>
                                    </h4>
                                    <div class="fl-fset__body">
                                        <div class="fl-scroll" data-fl-leg-panel="out">
                                            @foreach ($filterCatalog['dep_out'] ?? [] as $apt)
                                                <label class="fl-check">
                                                    <input type="checkbox" class="fl-filter"
                                                        data-fl-axis="depa-o" value="{{ $apt }}" checked>
                                                    <span class="fl-check__label"><strong>{{ $apt }}</strong></span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @if ($isRoundTrip)
                                            <div class="fl-scroll fl-only-ret"
                                                data-fl-leg-panel="ret">
                                                @foreach ($filterCatalog['dep_ret'] ?? [] as $apt)
                                                    <label class="fl-check">
                                                        <input type="checkbox" class="fl-filter"
                                                            data-fl-axis="depa-r" value="{{ $apt }}" checked>
                                                        <span class="fl-check__label"><strong>{{ $apt }}</strong></span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </section>
                            @endif

                            {{-- Connecting airports --}}
                            @if (!empty($filterCatalog['conn_out']) || (!empty($filterCatalog['conn_ret']) && $isRoundTrip))
                                <section class="fl-fset" data-fl-fset>
                                    <h4 class="fl-fset__title">
                                        <span><i class="bx bx-transfer"></i> Connecting Airports</span>
                                        <i class="bx bx-chevron-down fl-fset__caret"></i>
                                    </h4>
                                    <div class="fl-fset__body">
                                        <div class="fl-scroll" data-fl-leg-panel="out">
                                            @foreach ($filterCatalog['conn_out'] ?? [] as $cx)
                                                <label class="fl-check">
                                                    <input type="checkbox" class="fl-filter"
                                                        data-fl-axis="conn-o" value="{{ $cx }}" checked>
                                                    <span class="fl-check__label"><strong>{{ $cx }}</strong></span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @if ($isRoundTrip)
                                            <div class="fl-scroll fl-only-ret"
                                                data-fl-leg-panel="ret">
                                                @foreach ($filterCatalog['conn_ret'] ?? [] as $cx)
                                                    <label class="fl-check">
                                                        <input type="checkbox" class="fl-filter"
                                                            data-fl-axis="conn-r" value="{{ $cx }}"
                                                            checked>
                                                        <span class="fl-check__label"><strong>{{ $cx }}</strong></span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </section>
                            @endif

                            {{-- Fare type --}}
                            <section class="fl-fset" data-fl-fset>
                                <h4 class="fl-fset__title">
                                    <span><i class="bx bxs-offer"></i> Fare Type</span>
                                    <i class="bx bx-chevron-down fl-fset__caret"></i>
                                </h4>
                                <div class="fl-fset__body">
                                    <label class="fl-check">
                                        <input type="checkbox" class="fl-filter" data-fl-axis="fare"
                                            value="ndc" checked>
                                        <span class="fl-check__label">NDC</span>
                                    </label>
                                    <label class="fl-check">
                                        <input type="checkbox" class="fl-filter" data-fl-axis="fare"
                                            value="published" checked>
                                        <span class="fl-check__label">Published / ATPCO</span>
                                    </label>
                                    <label class="fl-check">
                                        <input type="checkbox" class="fl-filter" data-fl-axis="fare"
                                            value="other" checked>
                                        <span class="fl-check__label">Other</span>
                                    </label>
                                </div>
                            </section>
                        </div>
                    </aside>

                    {{-- ───────────── RESULTS LIST ───────────── --}}
                    <main class="fl-list" id="fl-list">
                        @foreach ($results as $result)
                            @php
                                $lid = $result['id'];
                                $meta = $result['listing_meta'] ?? [];
                                $legs = $result['legs'] ?? [];

                                $firstSeg = $legs[0]['segments'][0] ?? [];
                                $cabinTop = $firstSeg['cabin_code'] ?? 'Y';
                                $rbdTop = $firstSeg['booking_code'] ?? '';

                                $seatList = [];
                                foreach ($legs as $lg) {
                                    foreach ($lg['segments'] ?? [] as $sx) {
                                        if (isset($sx['seats_available'])) {
                                            $seatList[] = (int) $sx['seats_available'];
                                        }
                                    }
                                }
                                $seatMin = !empty($seatList) ? min($seatList) : null;

                                $vcCode = $result['validating_carrier'] ?? '';
                                $subSrc = $result['pricing_subsource'] ?? '';
                                $nonRefund = (bool) ($result['non_refundable'] ?? false);
                                $bagNote = $result['baggage_notes'] ?? '';
                                $supplier = $result['supplier'] ?? '';

                                $fareTags = $result['fare_tags'] ?? [];

                                $totalPrice = $result['totalPrice'] ?? 0;
                                $cardCurrency = strtoupper((string) ($result['currency'] ?? $currencyCode));
                            @endphp

                            <article class="fl-card" data-fl-meta='@json($meta)'
                                data-fl-id="{{ $lid }}">

                                {{-- ─── CARD HEADER ─── --}}
                                <header class="fl-card__head">
                                    <div class="fl-card__head-id">
                                        <span class="fl-card__index">#{{ $loop->iteration }}</span>
                                        <div>
                                            <span class="fl-card__eyebrow">Itinerary</span>
                                            <h3 class="fl-card__title">
                                                {{ $firstSeg['carrier_display'] ?? ($firstSeg['carrier'] ?? '') }}
                                                @if (!empty($firstSeg['flight_number']))
                                                    <span class="fl-card__flightno">·
                                                        {{ strtoupper((string) ($firstSeg['carrier'] ?? '')) }}{{ $firstSeg['flight_number'] }}</span>
                                                @endif
                                            </h3>
                                        </div>
                                    </div>

                                    <div class="fl-card__head-tags">
                                        @if ($vcCode)
                                            <span class="fl-tag fl-tag--vc"
                                                title="Validating carrier"><i class="bx bxs-shield"></i>
                                                VC · {{ strtoupper($vcCode) }}</span>
                                        @endif
                                        @if ($subSrc)
                                            <span class="fl-tag fl-tag--src">{{ strtoupper((string) $subSrc) }}</span>
                                        @endif
                                        @if (!$nonRefund)
                                            <span class="fl-tag fl-tag--ok"><i
                                                    class="bx bx-check-circle"></i> Refundable</span>
                                        @else
                                            <span class="fl-tag fl-tag--warn"><i class="bx bx-x-circle"></i>
                                                Non-Refundable</span>
                                        @endif
                                        @if ($bagNote)
                                            <span class="fl-tag fl-tag--bag"><i
                                                    class="bx bx-briefcase-alt-2"></i>
                                                {{ $bagNote }}</span>
                                        @endif
                                        @if (!is_null($seatMin))
                                            <span class="fl-tag fl-tag--seat"><i class="bx bx-group"></i>
                                                {{ $seatMin }} seats</span>
                                        @endif
                                        @if ($supplier)
                                            <span class="fl-tag fl-tag--desk">Desk ·
                                                {{ strtoupper((string) $supplier) }}</span>
                                        @endif
                                    </div>
                                </header>

                                {{-- ─── LEGS ─── --}}
                                <div class="fl-card__body">
                                    @foreach ($legs as $li => $leg)
                                        @php
                                            $segs = $leg['segments'] ?? [];
                                            $s0 = $segs[0] ?? [];

                                            $sLast = [];
                                            if (!empty($segs)) {
                                                $lk = array_key_last($segs);
                                                $sLast = is_array($segs[$lk] ?? null) ? $segs[$lk] : [];
                                            }

                                            $connCount = max(0, count($segs) - 1);
                                            $techStops = collect($segs)->sum(
                                                fn($s) => (int) ($s['stop_count'] ?? 0),
                                            );
                                            $stopsTotal = $connCount + $techStops;
                                            $stopsPhrase = fl_stops_phrase($stopsTotal);

                                            $legKind = $li === 0 ? 'out' : 'ret';
                                            $legLabel = $li === 0 ? ($isRoundTrip ? 'Outbound' : 'Flight') : 'Return';
                                            $legIcon = $li === 0 ? 'bxs-plane-take-off' : 'bxs-plane-land';
                                        @endphp

                                        <section class="fl-leg fl-leg--{{ $legKind }}">
                                            <header class="fl-leg__head">
                                                <span class="fl-leg__tag">
                                                    <i class="bx {{ $legIcon }}"></i>
                                                    {{ $legLabel }}
                                                </span>
                                                <span class="fl-leg__hmeta fl-leg__hmeta--date">
                                                    <i class="bx bx-calendar"></i>
                                                    {{ $s0['departure_weekday'] ?? '' }},
                                                    {{ $s0['departure_label'] ?? '' }}
                                                </span>
                                                <span class="fl-leg__hmeta">
                                                    <i class="bx bx-stopwatch"></i>
                                                    {{ fl_format_hm(isset($leg['elapsedTime']) ? (int) $leg['elapsedTime'] : null) }}
                                                </span>
                                                <span
                                                    class="fl-leg__hmeta fl-leg__stops fl-leg__stops--{{ $stopsTotal === 0 ? 'direct' : 'via' }}">
                                                    {{ $stopsPhrase }}
                                                </span>
                                            </header>

                                            <div class="fl-leg__row">

                                                <div class="fl-leg__airline">
                                                    <img class="fl-leg__logo"
                                                        src="{{ fl_carrier_logo($s0['carrier'] ?? '') }}"
                                                        loading="lazy" alt=""
                                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(trim((string) ($s0['carrier'] ?? 'FL'))) }}&background=cd1b4f&color=fff'">
                                                    <div>
                                                        <span class="fl-leg__airline-name">{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}</span>
                                                        <span class="fl-leg__airline-meta">
                                                            {{ strtoupper((string) ($s0['carrier'] ?? '')) }}{{ $s0['flight_number'] ?? '' }}
                                                            @if (
                                                                ($s0['operating_carrier'] ?? '') &&
                                                                    ($s0['operating_carrier'] ?? '') !== ($s0['carrier'] ?? ''))
                                                                · Op {{ $s0['operating_carrier'] }}@if (!empty($s0['operating_flight_number']))
                                                                    {{ $s0['operating_flight_number'] }}
                                                                @endif
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="fl-leg__from">
                                                    <strong class="fl-leg__time">
                                                        {{ $s0['departure_clock'] ?? '—' }}
                                                        @if ($s0['is_red_eye_segment'] ?? false)
                                                            <i class="bx bxs-moon fl-leg__redeye"
                                                                title="Red-eye"></i>
                                                        @endif
                                                    </strong>
                                                    <span class="fl-leg__code">
                                                        {{ $s0['from'] ?? '' }}@if (!empty($s0['departure_terminal']))
                                                            <span class="fl-leg__term">·
                                                                T{{ $s0['departure_terminal'] }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="fl-leg__city">{{ fl_city($s0, $s0['from'] ?? '') }}</span>
                                                </div>

                                                <div class="fl-leg__bridge">
                                                    <span class="fl-leg__bridge-time">
                                                        {{ fl_format_hm(isset($leg['elapsedTime']) ? (int) $leg['elapsedTime'] : null) }}
                                                    </span>
                                                    <div class="fl-leg__bridge-track">
                                                        <span class="fl-leg__bridge-dot fl-leg__bridge-dot--l"></span>
                                                        @if ($connCount > 0)
                                                            @foreach (array_slice($segs, 1, -1) as $mid)
                                                                <span class="fl-leg__bridge-mid"
                                                                    title="{{ $mid['from'] ?? '' }}">{{ $mid['from'] ?? '' }}</span>
                                                            @endforeach
                                                            @php $midDest = $sLast['from'] ?? ''; @endphp
                                                            @if ($connCount >= 1)
                                                                <span class="fl-leg__bridge-mid"
                                                                    title="{{ $midDest }}">{{ $midDest }}</span>
                                                            @endif
                                                        @endif
                                                        <span class="fl-leg__bridge-plane"><i
                                                                class="bx bxs-plane"></i></span>
                                                        <span class="fl-leg__bridge-dot fl-leg__bridge-dot--r"></span>
                                                    </div>
                                                    <span class="fl-leg__bridge-meta">
                                                        @if ($stopsTotal === 0)
                                                            <span class="fl-stoppill fl-stoppill--direct">Non-stop</span>
                                                        @else
                                                            <span class="fl-stoppill fl-stoppill--via">{{ $stopsPhrase }}</span>
                                                        @endif
                                                    </span>
                                                </div>

                                                <div class="fl-leg__to">
                                                    <strong class="fl-leg__time">
                                                        {{ $sLast['arrival_clock'] ?? '—' }}
                                                        @if ($sLast['next_day_hint'] ?? false)
                                                            <span class="fl-leg__nextday"
                                                                title="Arrives next day">+1</span>
                                                        @endif
                                                    </strong>
                                                    <span class="fl-leg__code">
                                                        {{ $sLast['to'] ?? '' }}@if (!empty($sLast['arrival_terminal']))
                                                            <span class="fl-leg__term">·
                                                                T{{ $sLast['arrival_terminal'] }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="fl-leg__city">{{ fl_city($sLast, $sLast['to'] ?? '') }}</span>
                                                </div>

                                            </div>

                                            <footer class="fl-leg__foot">
                                                @if (!empty($s0['cabin_code']))
                                                    <span class="fl-pillet fl-pillet--cab">
                                                        <i class="bx bx-purchase-tag-alt"></i>
                                                        {{ $s0['cabin_code'] }}
                                                    </span>
                                                @endif
                                                @if (!empty($s0['booking_code']))
                                                    <span class="fl-pillet">Class
                                                        {{ $s0['booking_code'] }}</span>
                                                @endif
                                                @if (!empty($s0['equipment']))
                                                    <span class="fl-pillet">Equipment
                                                        {{ $s0['equipment'] }}</span>
                                                @endif

                                                <button type="button" class="fl-leg__expand"
                                                    data-fl-expand>
                                                    <i class="bx bx-list-ul"></i>
                                                    <span data-fl-expand-label>Flight Details</span>
                                                    <i class="bx bx-chevron-down fl-leg__expand-caret"></i>
                                                </button>
                                            </footer>

                                            <div class="fl-leg__details" data-fl-details hidden>
                                                @foreach ($segs as $sIdx => $sx)
                                                    @php
                                                        $sxMins = fl_segment_minutes($sx);
                                                        $sxLayover =
                                                            $sIdx > 0
                                                                ? fl_layover_minutes($segs[$sIdx - 1], $sx)
                                                                : null;
                                                    @endphp

                                                    @if ($sxLayover !== null)
                                                        <div class="fl-layover">
                                                            <i class="bx bx-time-five"></i>
                                                            <span><strong>{{ fl_format_hm($sxLayover) }}</strong>
                                                                layover at
                                                                <strong>{{ $sx['from'] ?? '' }}</strong>
                                                                @if (!empty($sx['departure_city']))
                                                                    · {{ $sx['departure_city'] }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif

                                                    <div class="fl-seg">
                                                        <div class="fl-seg__air">
                                                            <img class="fl-seg__logo"
                                                                src="{{ fl_carrier_logo($sx['carrier'] ?? '') }}"
                                                                loading="lazy" alt=""
                                                                onerror="this.style.visibility='hidden'">
                                                            <div>
                                                                <span class="fl-seg__air-name">{{ $sx['carrier_display'] ?? ($sx['carrier'] ?? '') }}</span>
                                                                <span class="fl-seg__air-meta">
                                                                    {{ strtoupper((string) ($sx['carrier'] ?? '')) }}{{ $sx['flight_number'] ?? '' }}
                                                                    @if (
                                                                        ($sx['operating_carrier'] ?? '') &&
                                                                            ($sx['operating_carrier'] ?? '') !== ($sx['carrier'] ?? ''))
                                                                        · Op
                                                                        {{ $sx['operating_carrier'] }}{{ $sx['operating_flight_number'] ?? '' }}
                                                                    @endif
                                                                    @if (!empty($sx['equipment']))
                                                                        · {{ $sx['equipment'] }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="fl-seg__route">
                                                            <div class="fl-seg__pt">
                                                                <strong>{{ $sx['departure_clock'] ?? '—' }}</strong>
                                                                <span>{{ $sx['from'] ?? '' }}
                                                                    @if (!empty($sx['departure_city']))
                                                                        · {{ $sx['departure_city'] }}
                                                                    @endif
                                                                </span>
                                                                <small>{{ $sx['departure_label'] ?? '' }}@if (!empty($sx['departure_terminal']))
                                                                        · T{{ $sx['departure_terminal'] }}
                                                                    @endif</small>
                                                            </div>
                                                            <div class="fl-seg__dur">
                                                                <span>{{ fl_format_hm($sxMins) }}</span>
                                                                <div class="fl-seg__line"></div>
                                                                @if ((int) ($sx['stop_count'] ?? 0) > 0)
                                                                    <small>{{ (int) $sx['stop_count'] }}
                                                                        tech stop</small>
                                                                @endif
                                                            </div>
                                                            <div class="fl-seg__pt fl-seg__pt--arr">
                                                                <strong>{{ $sx['arrival_clock'] ?? '—' }}</strong>
                                                                <span>{{ $sx['to'] ?? '' }}
                                                                    @if (!empty($sx['arrival_city']))
                                                                        · {{ $sx['arrival_city'] }}
                                                                    @endif
                                                                </span>
                                                                <small>{{ $sx['arrival_label'] ?? '' }}@if (!empty($sx['arrival_terminal']))
                                                                        · T{{ $sx['arrival_terminal'] }}
                                                                    @endif</small>
                                                            </div>
                                                        </div>

                                                        <div class="fl-seg__chips">
                                                            @if (!empty($sx['cabin_code']))
                                                                <span class="fl-chip fl-chip--cab">{{ $sx['cabin_code'] }}</span>
                                                            @endif
                                                            @if (!empty($sx['booking_code']))
                                                                <span class="fl-chip">Class
                                                                    {{ $sx['booking_code'] }}</span>
                                                            @endif
                                                            @if (isset($sx['seats_available']))
                                                                <span
                                                                    class="fl-chip fl-chip--seat"><i
                                                                        class="bx bx-group"></i>
                                                                    {{ (int) $sx['seats_available'] }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                </div>

                                {{-- ─── CARD FOOTER ─── --}}
                                <footer class="fl-card__foot">
                                    <div class="fl-card__fare">
                                        @if (!empty($cabinTop))
                                            <span class="fl-card__fare-cabin">{{ $cabinTop }}</span>
                                        @endif
                                        @if (!empty($rbdTop))
                                            <span class="fl-card__fare-rbd">Class {{ $rbdTop }}</span>
                                        @endif
                                        @foreach ($fareTags as $ft)
                                            <span class="fl-card__fare-tag">{{ strtoupper((string) $ft) }}</span>
                                        @endforeach
                                    </div>

                                    <div class="fl-card__price">
                                        <span class="fl-card__price-label">Net Fare</span>
                                        <span class="fl-card__price-amount">
                                            <span class="fl-card__price-cur">{{ $cardCurrency }}</span>
                                            {{ number_format($totalPrice, 2) }}
                                        </span>
                                        @if (!is_null($seatMin))
                                            <span class="fl-card__price-sub"><i class="bx bx-user"></i>
                                                {{ $seatMin }} seats left</span>
                                        @endif
                                    </div>

                                    <a href="{{ route('user.flights.checkout', ['itinerary' => $lid] + $query) }}"
                                        class="fl-cta">
                                        Continue
                                        <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                </footer>

                            </article>
                        @endforeach

                        <div class="fl-empty fl-empty--filtered" id="fl-empty-filtered" hidden>
                            <div class="fl-empty__icon"><i class="bx bx-filter-alt"></i></div>
                            <h3 class="fl-empty__title">No fares match these filters</h3>
                            <p class="fl-empty__copy">Try relaxing one of the filters on the left, or hit
                                <strong>Reset</strong> to see all itineraries again.</p>
                            <button type="button" class="fl-btn fl-btn--primary" id="fl-empty-reset">
                                <i class="bx bx-revision"></i> Reset filters
                            </button>
                        </div>
                    </main>
                </div>
            @elseif ($hasSearch && empty($results ?? []))
                <div class="fl-empty">
                    <div class="fl-empty__icon"><i class="bx bx-search-alt-2"></i></div>
                    <h3 class="fl-empty__title">No flights match this search</h3>
                    <p class="fl-empty__copy">We couldn't find itineraries for this route, dates and
                        passengers.
                        Widen the dates or try nearby airports, then run the search again.</p>
                    <div class="fl-empty__actions">
                        <a href="{{ route('user.flights.index') }}" class="fl-btn fl-btn--primary">
                            <i class="bx bx-edit"></i> Modify Search
                        </a>
                    </div>
                </div>
            @else
                <div class="fl-empty fl-empty--idle">
                    <div class="fl-empty__icon fl-empty__icon--idle"><i class="bx bxs-plane-take-off"></i>
                    </div>
                    <h3 class="fl-empty__title">Ready when you are</h3>
                    <p class="fl-empty__copy">Choose your trip type, cities and dates above. We'll query
                        live
                        Sabre inventory and surface consolidator-grade fares.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('css')
    <style>
        /* ============================================================
           TOKENS — scoped to .fl-page so we don't pollute the app
           ============================================================ */
        .fl-page {
            --fl-brand: #cd1b4f;
            --fl-brand-2: #b41642;
            --fl-brand-soft: #fde7ef;
            --fl-brand-tint: rgba(205, 27, 79, .08);

            --fl-indigo: #4338ca;
            --fl-indigo-soft: #eef2ff;

            --fl-ink: #15233f;
            --fl-ink-2: #1f2937;
            --fl-slate: #475569;
            --fl-slate-2: #64748b;
            --fl-muted: #94a3b8;

            --fl-line: #e5e9f1;
            --fl-line-soft: #eef2f7;
            --fl-canvas: #f1f4f9;
            --fl-surface: #ffffff;
            --fl-surface-2: #f7f9fc;
            --fl-surface-3: #f9fafe;

            --fl-emerald: #047857;
            --fl-emerald-soft: #ecfdf5;
            --fl-amber: #b45309;
            --fl-amber-soft: #fef3c7;
            --fl-sky: #0284c7;
            --fl-sky-soft: #e0f2fe;
            --fl-violet: #7c3aed;
            --fl-violet-soft: #ede9fe;
            --fl-rose: #be123c;
            --fl-rose-soft: #ffe4e6;

            --fl-shadow-sm: 0 1px 2px rgba(15, 23, 42, .05);
            --fl-shadow-md: 0 6px 18px rgba(15, 23, 42, .06);
            --fl-shadow-lg: 0 18px 38px -22px rgba(15, 23, 42, .28);

            --fl-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            --fl-font: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

            font-family: var(--fl-font);
            color: var(--fl-ink);
            background: var(--fl-surface-2);
            padding-bottom: 4rem;
        }

        .fl-page * {
            box-sizing: border-box;
        }

        .fl-page a {
            text-decoration: none;
        }

        .fl-shell {
            padding-top: 1.25rem;
        }

        /* ============================================================
           SEARCH WRAP
           ============================================================ */
        .fl-search-wrap {
            background:
                radial-gradient(80% 80% at 50% 0%, rgba(205, 27, 79, .07), transparent 60%),
                linear-gradient(180deg, #fff 0%, #f4f6fb 100%);
            border-bottom: 1px solid var(--fl-line);
            padding: 1rem 0;
        }

        /* ============================================================
           ALERTS
           ============================================================ */
        .fl-alerts {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            margin-bottom: 1rem;
        }

        .fl-alert {
            display: flex;
            gap: .55rem;
            align-items: flex-start;
            padding: .7rem .9rem;
            border-radius: 12px;
            font-size: .85rem;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .fl-alert i {
            font-size: 1.15rem;
            margin-top: .05rem;
        }

        .fl-alert--err {
            background: var(--fl-rose-soft);
            border-color: rgba(190, 18, 60, .25);
            color: var(--fl-rose);
        }

        .fl-alert--info {
            background: var(--fl-sky-soft);
            border-color: rgba(2, 132, 199, .25);
            color: var(--fl-sky);
        }

        /* ============================================================
           NOTICE
           ============================================================ */
        .fl-notice {
            display: flex;
            gap: .85rem;
            align-items: flex-start;
            padding: .7rem .95rem;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-left: 4px solid var(--fl-amber);
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: var(--fl-shadow-sm);
        }

        .fl-notice p {
            margin: 0;
            font-size: .82rem;
            color: var(--fl-slate);
            line-height: 1.5;
        }

        .fl-notice strong {
            color: var(--fl-ink);
            font-weight: 700;
        }

        .fl-notice__icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* ============================================================
           TOOLBAR (count + sort)
           ============================================================ */
        .fl-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .fl-toolbar__left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .fl-toolbar__count {
            font-size: .85rem;
            color: var(--fl-slate);
            display: inline-flex;
            align-items: baseline;
            gap: .25rem;
        }

        .fl-toolbar__count strong,
        .fl-toolbar__count-num {
            font-family: var(--fl-mono);
            color: var(--fl-ink);
            font-weight: 800;
            font-size: 1rem;
        }

        .fl-toolbar__count-num {
            color: var(--fl-brand);
        }

        .fl-toolbar__live {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
            padding: .15rem .55rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .fl-toolbar__dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--fl-emerald);
            box-shadow: 0 0 0 3px rgba(4, 120, 87, .22);
            animation: fl-pulse 1.8s ease-out infinite;
        }

        @keyframes fl-pulse {

            0%,
            100% {
                box-shadow: 0 0 0 3px rgba(4, 120, 87, .22);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(4, 120, 87, 0);
            }
        }

        .fl-toolbar__right {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .fl-toolbar__sortlabel {
            font-size: .65rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--fl-slate-2);
        }

        .fl-sort {
            display: inline-flex;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 999px;
            padding: .2rem;
            box-shadow: var(--fl-shadow-sm);
            flex-wrap: wrap;
        }

        .fl-sort__btn {
            border: none;
            background: transparent;
            font: inherit;
            color: var(--fl-slate);
            padding: .45rem .85rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .fl-sort__btn i {
            font-size: .95rem;
            opacity: .7;
        }

        .fl-sort__btn:hover {
            color: var(--fl-ink);
        }

        .fl-sort__btn.is-active {
            background: var(--fl-brand);
            color: #fff;
            box-shadow: 0 6px 14px rgba(205, 27, 79, .28);
        }

        .fl-sort__btn.is-active i {
            opacity: 1;
        }

        .fl-sort__dir {
            transition: transform .2s ease;
        }

        .fl-sort__btn.is-desc .fl-sort__dir {
            transform: rotate(180deg);
        }

        /* ============================================================
           GRID
           ============================================================ */
        .fl-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.25rem;
            align-items: flex-start;
        }

        /* ============================================================
           SIDEBAR
           ============================================================ */
        .fl-side {
            min-width: 0;
        }

        .fl-side__inner {
            position: sticky;
            top: 1rem;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 18px;
            padding: 1.05rem 1rem 1.2rem;
            box-shadow: var(--fl-shadow-md);
        }

        .fl-side__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            padding-bottom: .85rem;
            margin-bottom: .6rem;
            border-bottom: 1px solid var(--fl-line-soft);
        }

        .fl-side__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: var(--fl-brand);
            background: var(--fl-brand-soft);
            padding: .15rem .5rem;
            border-radius: 999px;
            margin-bottom: .35rem;
        }

        .fl-side__eyebrow-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--fl-brand);
        }

        .fl-side__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: var(--fl-ink);
            letter-spacing: -.01em;
        }

        .fl-side__reset {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            color: var(--fl-slate);
            padding: .4rem .65rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: .72rem;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .fl-side__reset:hover {
            background: var(--fl-brand);
            color: #fff;
            border-color: var(--fl-brand);
        }

        .fl-side__reset i {
            font-size: .9rem;
        }

        .fl-side__tabs {
            display: flex;
            gap: .25rem;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            border-radius: 999px;
            padding: .2rem;
            margin: .55rem 0 .25rem;
        }

        .fl-side__tab {
            flex: 1;
            background: transparent;
            border: none;
            padding: .4rem .55rem;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 700;
            color: var(--fl-slate);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .3rem;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .fl-side__tab i {
            font-size: .95rem;
        }

        .fl-side__tab.is-active {
            background: var(--fl-surface);
            color: var(--fl-brand);
            box-shadow: var(--fl-shadow-sm);
        }

        /* Leg-panel visibility driven by [data-fl-active="out|ret"] on #fl-side */
        .fl-side .fl-only-ret,
        .fl-side [data-fl-leg-panel="ret"] {
            display: none;
        }

        .fl-side[data-fl-active="ret"] .fl-only-ret,
        .fl-side[data-fl-active="ret"] [data-fl-leg-panel="ret"] {
            display: revert;
        }

        .fl-side[data-fl-active="ret"] [data-fl-leg-panel="out"] {
            display: none;
        }

        /* filter set */
        .fl-fset {
            border-top: 1px solid var(--fl-line-soft);
            padding: .85rem 0 .55rem;
        }

        .fl-fset:first-of-type {
            border-top: none;
            padding-top: .7rem;
        }

        .fl-fset__title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            margin: 0;
            padding: 0;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--fl-ink);
            user-select: none;
        }

        .fl-fset__title span {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .fl-fset__title i {
            font-size: 1rem;
            color: var(--fl-brand);
        }

        .fl-fset__caret {
            color: var(--fl-muted);
            transition: transform .2s ease;
        }

        .fl-fset.is-collapsed .fl-fset__caret {
            transform: rotate(-90deg);
        }

        .fl-fset__body {
            margin-top: .6rem;
        }

        .fl-fset.is-collapsed .fl-fset__body {
            display: none;
        }

        /* stops chips */
        .fl-stops {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .4rem;
        }

        .fl-chip-pick {
            position: relative;
            cursor: pointer;
        }

        .fl-chip-pick input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .fl-chip-pick span {
            display: block;
            text-align: center;
            padding: .4rem .25rem;
            font-size: .75rem;
            font-weight: 700;
            color: var(--fl-slate);
            border-radius: 10px;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .fl-chip-pick input:checked+span {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
            border-color: rgba(205, 27, 79, .4);
        }

        /* time chips */
        .fl-tgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .4rem;
        }

        .fl-tchip {
            position: relative;
            cursor: pointer;
        }

        .fl-tchip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .fl-tchip__inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .15rem;
            padding: .5rem .25rem;
            border-radius: 12px;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            color: var(--fl-slate);
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .fl-tchip__inner i {
            font-size: 1.05rem;
            color: var(--fl-slate-2);
        }

        .fl-tchip__inner strong {
            font-size: .78rem;
            font-weight: 800;
            color: var(--fl-ink);
        }

        .fl-tchip__inner small {
            font-family: var(--fl-mono);
            font-size: .65rem;
            color: var(--fl-muted);
            letter-spacing: .04em;
        }

        .fl-tchip input:checked+.fl-tchip__inner {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
            border-color: rgba(205, 27, 79, .4);
        }

        .fl-tchip input:checked+.fl-tchip__inner i,
        .fl-tchip input:checked+.fl-tchip__inner strong,
        .fl-tchip input:checked+.fl-tchip__inner small {
            color: var(--fl-brand);
        }

        /* check rows */
        .fl-check {
            display: flex;
            gap: .55rem;
            align-items: center;
            padding: .35rem .15rem;
            font-size: .82rem;
            color: var(--fl-ink-2);
            cursor: pointer;
            border-radius: 8px;
            transition: background-color .15s ease;
        }

        .fl-check:hover {
            background: var(--fl-surface-2);
        }

        .fl-check input {
            width: 16px;
            height: 16px;
            accent-color: var(--fl-brand);
            cursor: pointer;
        }

        .fl-check__label {
            flex: 1;
            min-width: 0;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .fl-check__label strong {
            font-weight: 700;
            color: var(--fl-ink);
        }

        .fl-check--al .fl-check__logo {
            width: 22px;
            height: 22px;
            object-fit: contain;
            background: #fff;
            border: 1px solid var(--fl-line);
            padding: 1px;
            border-radius: 6px;
        }

        .fl-check__count {
            font-family: var(--fl-mono);
            font-size: .7rem;
            font-weight: 700;
            color: var(--fl-slate-2);
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            border-radius: 999px;
            padding: .1rem .45rem;
            min-width: 28px;
            text-align: center;
        }

        .fl-scroll {
            max-height: 200px;
            overflow: auto;
            padding-right: .25rem;
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }

        .fl-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .fl-scroll::-webkit-scrollbar-thumb {
            background: var(--fl-line);
            border-radius: 999px;
        }

        /* price range */
        .fl-range {
            width: 100%;
            accent-color: var(--fl-brand);
        }

        .fl-range__labels {
            display: flex;
            justify-content: space-between;
            margin-top: .45rem;
            align-items: center;
            font-size: .75rem;
            color: var(--fl-slate);
        }

        .fl-range__min {
            font-family: var(--fl-mono);
            color: var(--fl-muted);
        }

        .fl-range__cap {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
            font-family: var(--fl-mono);
            padding: .2rem .55rem;
            border-radius: 999px;
            font-weight: 800;
            font-size: .8rem;
            display: inline-flex;
            gap: .25rem;
        }

        .fl-range__cap-cur {
            opacity: .7;
        }

        /* ============================================================
           CARD
           ============================================================ */
        .fl-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
        }

        .fl-card {
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 18px;
            box-shadow: var(--fl-shadow-md);
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .fl-card:hover {
            border-color: rgba(205, 27, 79, .3);
            box-shadow: var(--fl-shadow-lg);
            transform: translateY(-1px);
        }

        .fl-card.fl-hide {
            display: none !important;
        }

        .fl-card__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 1.1rem;
            background:
                linear-gradient(180deg, var(--fl-surface) 0%, var(--fl-surface-3) 100%);
            border-bottom: 1px solid var(--fl-line-soft);
        }

        .fl-card__head-id {
            display: flex;
            gap: .75rem;
            align-items: center;
        }

        .fl-card__index {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--fl-brand);
            color: #fff;
            font-family: var(--fl-mono);
            font-weight: 800;
            font-size: .82rem;
            letter-spacing: -.02em;
            box-shadow: 0 6px 14px rgba(205, 27, 79, .25);
        }

        .fl-card__eyebrow {
            display: block;
            font-size: .58rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--fl-slate-2);
            margin-bottom: .1rem;
        }

        .fl-card__title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 800;
            color: var(--fl-ink);
            letter-spacing: -.01em;
        }

        .fl-card__flightno {
            font-family: var(--fl-mono);
            font-weight: 700;
            color: var(--fl-slate);
            font-size: .8rem;
            margin-left: .15rem;
        }

        .fl-card__head-tags {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
        }

        .fl-tag {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            background: var(--fl-canvas);
            color: var(--fl-slate);
            border: 1px solid var(--fl-line-soft);
        }

        .fl-tag i {
            font-size: .82rem;
        }

        .fl-tag--vc {
            background: var(--fl-sky-soft);
            color: var(--fl-sky);
            border-color: rgba(2, 132, 199, .25);
        }

        .fl-tag--src {
            background: var(--fl-violet-soft);
            color: var(--fl-violet);
            border-color: rgba(124, 58, 237, .25);
        }

        .fl-tag--ok {
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
            border-color: rgba(4, 120, 87, .25);
        }

        .fl-tag--warn {
            background: var(--fl-rose-soft);
            color: var(--fl-rose);
            border-color: rgba(190, 18, 60, .25);
        }

        .fl-tag--bag {
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
            border-color: rgba(4, 120, 87, .15);
        }

        .fl-tag--seat {
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
            border-color: rgba(180, 83, 9, .2);
        }

        .fl-tag--desk {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
            border-color: rgba(205, 27, 79, .2);
        }

        /* ============================================================
           LEG
           ============================================================ */
        .fl-card__body {
            padding: .75rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .fl-leg {
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 14px;
            overflow: hidden;
            position: relative;
        }

        .fl-leg::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .fl-leg--out::before {
            background: linear-gradient(180deg, var(--fl-brand), var(--fl-brand-2));
        }

        .fl-leg--ret::before {
            background: linear-gradient(180deg, var(--fl-indigo), #312e81);
        }

        .fl-leg__head {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem 1rem;
            align-items: center;
            padding: .55rem .85rem .55rem 1.1rem;
            background: var(--fl-surface-2);
            border-bottom: 1px solid var(--fl-line-soft);
        }

        .fl-leg__tag {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .14em;
        }

        .fl-leg__tag i {
            font-size: .92rem;
        }

        .fl-leg--out .fl-leg__tag {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
        }

        .fl-leg--ret .fl-leg__tag {
            background: var(--fl-indigo-soft);
            color: var(--fl-indigo);
        }

        .fl-leg__hmeta {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .76rem;
            font-weight: 600;
            color: var(--fl-slate);
        }

        .fl-leg__hmeta i {
            font-size: .92rem;
            color: var(--fl-slate-2);
        }

        .fl-leg__hmeta--date {
            color: var(--fl-ink);
            font-weight: 700;
        }

        .fl-leg__stops {
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-left: auto;
        }

        .fl-leg__stops--direct {
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
        }

        .fl-leg__stops--via {
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
        }

        .fl-leg__row {
            display: grid;
            grid-template-columns:
                minmax(170px, .9fr) minmax(0, 1fr) minmax(140px, 1.1fr) minmax(0, 1fr);
            gap: 1rem;
            align-items: center;
            padding: .9rem 1.1rem .55rem 1.1rem;
        }

        .fl-leg__airline {
            display: flex;
            gap: .55rem;
            align-items: center;
            min-width: 0;
        }

        .fl-leg__logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: contain;
            padding: 3px;
            background: #fff;
            border: 1px solid var(--fl-line);
            flex-shrink: 0;
        }

        .fl-leg__airline-name {
            display: block;
            font-size: .9rem;
            font-weight: 800;
            color: var(--fl-ink);
            line-height: 1.2;
        }

        .fl-leg__airline-meta {
            display: block;
            font-family: var(--fl-mono);
            font-size: .72rem;
            color: var(--fl-slate-2);
            font-weight: 600;
            margin-top: .1rem;
        }

        .fl-leg__from,
        .fl-leg__to {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .fl-leg__to {
            text-align: right;
            align-items: flex-end;
        }

        .fl-leg__time {
            font-family: var(--fl-mono);
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--fl-ink);
            line-height: 1;
            letter-spacing: -.02em;
            display: inline-flex;
            align-items: baseline;
            gap: .35rem;
        }

        .fl-leg__redeye {
            font-size: .9rem;
            color: var(--fl-violet);
        }

        .fl-leg__nextday {
            font-family: var(--fl-mono);
            font-size: .65rem;
            font-weight: 800;
            color: var(--fl-amber);
            background: var(--fl-amber-soft);
            padding: .05rem .35rem;
            border-radius: 6px;
            letter-spacing: .04em;
        }

        .fl-leg__code {
            margin-top: .25rem;
            font-family: var(--fl-mono);
            font-size: .78rem;
            font-weight: 700;
            color: var(--fl-slate);
            letter-spacing: .04em;
        }

        .fl-leg__term {
            font-weight: 600;
            color: var(--fl-muted);
        }

        .fl-leg__city {
            font-size: .82rem;
            font-weight: 600;
            color: var(--fl-slate-2);
            margin-top: .1rem;
        }

        .fl-leg__bridge {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            position: relative;
        }

        .fl-leg__bridge-time {
            font-family: var(--fl-mono);
            font-size: .76rem;
            font-weight: 700;
            color: var(--fl-slate);
            letter-spacing: .04em;
        }

        .fl-leg__bridge-track {
            position: relative;
            width: 100%;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
        }

        .fl-leg__bridge-track::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 2px;
            background: linear-gradient(90deg, var(--fl-line) 0%, var(--fl-brand) 50%, var(--fl-line) 100%);
            border-radius: 2px;
        }

        .fl-leg__bridge-dot {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--fl-brand);
            box-shadow: 0 0 0 3px #fff;
            z-index: 2;
        }

        .fl-leg__bridge-dot--l {
            left: 0;
        }

        .fl-leg__bridge-dot--r {
            right: 0;
        }

        .fl-leg__bridge-plane {
            position: relative;
            z-index: 3;
            background: #fff;
            border: 1px solid var(--fl-line);
            color: var(--fl-brand);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
        }

        .fl-leg__bridge-mid {
            position: relative;
            z-index: 3;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            color: var(--fl-slate);
            font-family: var(--fl-mono);
            font-size: .62rem;
            font-weight: 800;
            padding: .1rem .4rem;
            border-radius: 999px;
            letter-spacing: .04em;
        }

        .fl-leg__bridge-meta {
            display: inline-flex;
        }

        .fl-stoppill {
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            padding: .2rem .55rem;
            border-radius: 999px;
        }

        .fl-stoppill--direct {
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
        }

        .fl-stoppill--via {
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
        }

        /* leg footer */
        .fl-leg__foot {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            align-items: center;
            padding: .55rem 1.1rem .85rem;
            border-top: 1px dashed var(--fl-line);
        }

        .fl-pillet {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: var(--fl-surface-2);
            color: var(--fl-slate);
            border: 1px solid var(--fl-line);
            font-family: var(--fl-mono);
            font-size: .7rem;
            font-weight: 700;
            padding: .12rem .5rem;
            border-radius: 6px;
        }

        .fl-pillet i {
            font-size: .85rem;
            color: var(--fl-slate-2);
        }

        .fl-pillet--cab {
            background: var(--fl-sky-soft);
            color: var(--fl-sky);
            border-color: rgba(2, 132, 199, .2);
        }

        .fl-pillet--cab i {
            color: var(--fl-sky);
        }

        .fl-leg__expand {
            margin-left: auto;
            background: transparent;
            border: none;
            color: var(--fl-brand);
            font: inherit;
            font-weight: 800;
            font-size: .78rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .7rem;
            border-radius: 8px;
            transition: background-color .15s ease;
        }

        .fl-leg__expand:hover {
            background: var(--fl-brand-soft);
        }

        .fl-leg__expand-caret {
            transition: transform .2s ease;
            font-size: 1rem;
        }

        .fl-leg__expand.is-open .fl-leg__expand-caret {
            transform: rotate(180deg);
        }

        /* details panel */
        .fl-leg__details {
            background: var(--fl-surface-2);
            border-top: 1px solid var(--fl-line);
            padding: .75rem .85rem;
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }

        .fl-leg__details[hidden] {
            display: none;
        }

        .fl-layover {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            align-self: center;
            background: var(--fl-violet-soft);
            color: var(--fl-violet);
            font-size: .76rem;
            font-weight: 700;
            padding: .35rem .8rem;
            border-radius: 999px;
            border: 1px dashed rgba(124, 58, 237, .35);
        }

        .fl-layover i {
            font-size: .95rem;
        }

        .fl-seg {
            display: grid;
            grid-template-columns: minmax(140px, .85fr) minmax(0, 2fr) minmax(0, .65fr);
            gap: .65rem;
            align-items: center;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 12px;
            padding: .65rem .8rem;
        }

        .fl-seg__air {
            display: flex;
            gap: .5rem;
            align-items: center;
            min-width: 0;
        }

        .fl-seg__logo {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            object-fit: contain;
            padding: 2px;
            background: #fff;
            border: 1px solid var(--fl-line);
            flex-shrink: 0;
        }

        .fl-seg__air-name {
            display: block;
            font-size: .8rem;
            font-weight: 800;
            color: var(--fl-ink);
            line-height: 1.2;
        }

        .fl-seg__air-meta {
            display: block;
            font-family: var(--fl-mono);
            font-size: .68rem;
            color: var(--fl-slate-2);
            font-weight: 600;
        }

        .fl-seg__route {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(60px, .5fr) minmax(0, 1fr);
            gap: .5rem;
            align-items: center;
        }

        .fl-seg__pt {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .fl-seg__pt strong {
            font-family: var(--fl-mono);
            font-size: 1rem;
            font-weight: 800;
            color: var(--fl-ink);
            letter-spacing: -.01em;
        }

        .fl-seg__pt span {
            font-size: .74rem;
            font-weight: 600;
            color: var(--fl-ink-2);
            margin-top: .1rem;
        }

        .fl-seg__pt small {
            font-size: .66rem;
            color: var(--fl-muted);
            margin-top: .05rem;
        }

        .fl-seg__pt--arr {
            text-align: right;
            align-items: flex-end;
        }

        .fl-seg__dur {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .25rem;
        }

        .fl-seg__dur span {
            font-family: var(--fl-mono);
            font-size: .72rem;
            font-weight: 700;
            color: var(--fl-slate);
        }

        .fl-seg__dur small {
            font-size: .62rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--fl-amber);
            font-weight: 700;
        }

        .fl-seg__line {
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--fl-brand), var(--fl-line));
            border-radius: 2px;
        }

        .fl-seg__chips {
            display: flex;
            justify-content: flex-end;
            gap: .25rem;
            flex-wrap: wrap;
        }

        .fl-chip {
            background: var(--fl-canvas);
            color: var(--fl-slate);
            border: 1px solid var(--fl-line-soft);
            font-family: var(--fl-mono);
            font-size: .66rem;
            font-weight: 700;
            padding: .1rem .4rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: .2rem;
        }

        .fl-chip--cab {
            background: var(--fl-sky-soft);
            color: var(--fl-sky);
            border-color: rgba(2, 132, 199, .2);
        }

        .fl-chip--seat {
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
            border-color: rgba(180, 83, 9, .2);
        }

        /* ============================================================
           CARD FOOTER
           ============================================================ */
        .fl-card__foot {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(150px, 1fr) auto;
            align-items: center;
            gap: 1rem;
            padding: .9rem 1.1rem;
            background: linear-gradient(180deg, var(--fl-surface) 30%, var(--fl-surface-3));
            border-top: 1px solid var(--fl-line);
        }

        .fl-card__fare {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .fl-card__fare-cabin {
            font-family: var(--fl-mono);
            font-weight: 800;
            font-size: .7rem;
            background: var(--fl-ink);
            color: #fff;
            padding: .2rem .55rem;
            border-radius: 6px;
            letter-spacing: .08em;
        }

        .fl-card__fare-rbd {
            font-size: .72rem;
            font-weight: 700;
            color: var(--fl-slate);
            background: var(--fl-canvas);
            padding: .2rem .6rem;
            border-radius: 999px;
        }

        .fl-card__fare-tag {
            font-size: .6rem;
            font-weight: 800;
            color: var(--fl-violet);
            background: var(--fl-violet-soft);
            padding: .2rem .55rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        .fl-card__price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
            gap: .1rem;
        }

        .fl-card__price-label {
            font-size: .6rem;
            font-weight: 800;
            color: var(--fl-brand);
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .fl-card__price-amount {
            font-family: var(--fl-mono);
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--fl-brand);
            letter-spacing: -.02em;
            line-height: 1.05;
        }

        .fl-card__price-cur {
            color: var(--fl-slate-2);
            font-weight: 700;
            font-size: .82rem;
            margin-right: .15rem;
        }

        .fl-card__price-sub {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .68rem;
            font-weight: 700;
            color: var(--fl-amber);
            margin-top: .1rem;
        }

        .fl-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .85rem 1.5rem;
            border-radius: 14px;
            background: linear-gradient(180deg, var(--fl-brand) 0%, var(--fl-brand-2) 100%);
            color: #fff !important;
            font-weight: 800;
            font-size: .92rem;
            box-shadow: 0 12px 24px rgba(205, 27, 79, .28);
            white-space: nowrap;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .fl-cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(205, 27, 79, .38);
            color: #fff !important;
        }

        .fl-cta i {
            font-size: 1.1rem;
        }

        /* ============================================================
           EMPTY STATES
           ============================================================ */
        .fl-empty {
            margin: 2rem auto;
            max-width: 520px;
            text-align: center;
            padding: 2.25rem 1.5rem;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 22px;
            box-shadow: var(--fl-shadow-md);
        }

        .fl-empty--filtered {
            margin: 0;
            box-shadow: var(--fl-shadow-sm);
        }

        .fl-empty__icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.1rem;
            color: var(--fl-brand);
            background: var(--fl-brand-soft);
        }

        .fl-empty__icon--idle {
            color: var(--fl-sky);
            background: var(--fl-sky-soft);
        }

        .fl-empty__title {
            margin: 0 0 .55rem;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--fl-ink);
        }

        .fl-empty__copy {
            color: var(--fl-slate);
            font-size: .9rem;
            margin: 0 0 1.2rem;
            line-height: 1.55;
        }

        .fl-empty__copy strong {
            color: var(--fl-ink);
        }

        .fl-empty__actions {
            display: flex;
            gap: .65rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .fl-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .65rem 1.15rem;
            border-radius: 12px;
            font-weight: 800;
            font-size: .86rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .fl-btn--primary {
            background: linear-gradient(180deg, var(--fl-brand), var(--fl-brand-2));
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(205, 27, 79, .28);
        }

        .fl-btn--primary:hover {
            transform: translateY(-1px);
            color: #fff !important;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 1199px) {
            .fl-leg__row {
                grid-template-columns: minmax(140px, 1fr) minmax(0, 1fr) minmax(120px, 1.1fr) minmax(0, 1fr);
            }
        }

        @media (max-width: 991px) {
            .fl-grid {
                grid-template-columns: 1fr;
            }

            .fl-side__inner {
                position: static;
            }

            .fl-leg__row {
                grid-template-columns: 1fr;
                gap: .5rem;
            }

            .fl-leg__airline {
                order: 1;
            }

            .fl-leg__from {
                order: 2;
            }

            .fl-leg__bridge {
                order: 3;
                margin: .35rem 0;
            }

            .fl-leg__to {
                order: 4;
                text-align: left;
                align-items: flex-start;
            }

            .fl-card__head {
                flex-direction: column;
                align-items: stretch;
            }

            .fl-card__head-tags {
                justify-content: flex-start;
            }

            .fl-card__foot {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .fl-card__price {
                align-items: flex-start;
                text-align: left;
            }

            .fl-cta {
                justify-self: stretch;
            }
        }

        @media (max-width: 640px) {
            .fl-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .fl-sort {
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
            }

            .fl-leg__head {
                gap: .35rem .65rem;
            }

            .fl-leg__stops {
                margin-left: 0;
            }

            .fl-seg {
                grid-template-columns: 1fr;
            }

            .fl-seg__chips {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        window.FL_CFG = @json([
            'trip' => $tripType,
            'priceMax' => (float) $priceRange['max'],
        ]);

        (function () {
            const list = document.getElementById('fl-list');
            if (!list) return;

            const side = document.getElementById('fl-side');
            const cfg = window.FL_CFG || {};
            const trip = cfg.trip || 'one_way';
            const PRICE_MAX = Number(cfg.priceMax ?? 999999);

            const AXIS_TO_META = {
                'stops-o': 'st_o',
                'stops-r': 'st_r',
                'dep-o': 'dba_o',
                'dep-r': 'dba_r',
                'arr-o': 'aba_o',
                'arr-r': 'aba_r',
                'airline': 'al',
                'depa-o': 'dep_o',
                'depa-r': 'dep_r',
                'conn-o': 'conn_o',
                'conn-r': 'conn_r',
                'fare': 'fare',
            };

            const ALL_AXIS_FOR_TRIP = (() => {
                const out = [
                    'stops-o', 'dep-o', 'arr-o', 'airline', 'depa-o', 'conn-o', 'fare'
                ];

                if (trip === 'round_trip') {
                    out.push('stops-r', 'dep-r', 'arr-r', 'depa-r', 'conn-r');
                }

                return out;
            })();

            const cards = () => [...list.querySelectorAll('.fl-card')];

            function parseMeta(card) {
                try {
                    return JSON.parse(card.dataset.flMeta || '{}');
                } catch (e) {
                    return {};
                }
            }

            /* ====================== TAB SWITCHING ====================== */
            const tabBtns = [...document.querySelectorAll('.fl-side__tab[data-fl-leg]')];

            function setLegTab(which) {
                tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.flLeg === which));
                if (side) side.setAttribute('data-fl-active', which);
            }

            tabBtns.forEach(b => b.addEventListener('click', () => setLegTab(b.dataset.flLeg)));

            if (trip === 'round_trip') {
                setLegTab('out');
            }

            /* ====================== FILTER STATE ====================== */
            const priceEl = document.getElementById('fl-price');
            const priceLabel = document.getElementById('fl-price-label');

            function getInputsForAxis(axis) {
                return [...side.querySelectorAll(`.fl-filter[data-fl-axis="${axis}"]`)];
            }

            function getChosen(axis) {
                return getInputsForAxis(axis)
                    .filter(i => i.checked)
                    .map(i => String(i.dataset.flBucket !== undefined ? i.dataset.flBucket : i.value));
            }

            function metaArr(meta, key) {
                const v = meta[key];

                if (Array.isArray(v)) return v.map(String);

                if (v === null || v === undefined) return null;

                return [String(v)];
            }

            function matches(meta) {
                /* Price cap */
                if (priceEl) {
                    const cap = Number(priceEl.value || PRICE_MAX);

                    if (Number(meta.price || 0) > cap + 0.01) return false;
                }

                /* Each axis */
                for (const axis of ALL_AXIS_FOR_TRIP) {
                    const inputs = getInputsForAxis(axis);

                    if (!inputs.length) continue;

                    const chosen = getChosen(axis);

                    if (!chosen.length) return false;

                    const metaKey = AXIS_TO_META[axis];

                    if (!metaKey) continue;

                    const cardVals = metaArr(meta, metaKey);

                    if (cardVals === null) continue;

                    /* Stops: scalar value, must be in chosen */
                    if (axis === 'stops-o' || axis === 'stops-r') {
                        if (!cardVals.length) continue;
                        if (!chosen.includes(cardVals[0])) return false;
                        continue;
                    }

                    /* Connections: all card mids must be allowed */
                    if (axis === 'conn-o' || axis === 'conn-r') {
                        if (!cardVals.length) continue;
                        if (!cardVals.every(v => chosen.includes(v))) return false;
                        continue;
                    }

                    /* Default: any match */
                    if (!cardVals.length) continue;
                    if (!cardVals.some(v => chosen.includes(v))) return false;
                }

                return true;
            }

            /* ====================== SORT ====================== */
            let currentSort = 'price-asc';

            function sortCards() {
                const items = [...list.querySelectorAll('.fl-card')];

                items.sort((a, b) => {
                    const ma = parseMeta(a),
                        mb = parseMeta(b);

                    switch (currentSort) {
                        case 'price-desc':
                            return (Number(mb.price) || 0) - (Number(ma.price) || 0);
                        case 'duration-o-asc':
                            return (Number(ma.dur_o) || 9e9) - (Number(mb.dur_o) || 9e9);
                        case 'departure-o':
                            return String(ma.first_dep_iso || '').localeCompare(String(mb
                                .first_dep_iso || ''));
                        case 'airline':
                            return String((ma.al || [])[0] || '').localeCompare(String((mb.al ||
                                [])[0] || ''));
                        case 'best-value': {
                            const va = (Number(ma.price) || 9e9) / Math.max(1, Number(ma.dur_o) || 1);
                            const vb = (Number(mb.price) || 9e9) / Math.max(1, Number(mb.dur_o) || 1);
                            return va - vb;
                        }
                        case 'price-asc':
                        default:
                            return (Number(ma.price) || 0) - (Number(mb.price) || 0);
                    }
                });

                items.forEach(el => list.appendChild(el));
            }

            const sortBtns = [...document.querySelectorAll('.fl-sort__btn[data-fl-sort]')];
            sortBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    let target = btn.dataset.flSort;
                    const canCycle = btn.hasAttribute('data-fl-cycle');

                    if (canCycle && btn.classList.contains('is-active')) {
                        if (target === 'price-asc') {
                            target = 'price-desc';
                            btn.classList.add('is-desc');
                        } else {
                            target = 'price-asc';
                            btn.classList.remove('is-desc');
                        }
                        btn.dataset.flSort = target;
                    } else {
                        if (canCycle) btn.classList.remove('is-desc');
                    }

                    sortBtns.forEach(b => b.classList.toggle('is-active', b === btn));
                    currentSort = target;
                    applyFilters();
                });
            });

            /* ====================== APPLY ====================== */
            const visibleCounter = document.getElementById('fl-visible');
            const emptyFiltered = document.getElementById('fl-empty-filtered');

            function applyFilters() {
                let visible = 0;

                cards().forEach(card => {
                    const ok = matches(parseMeta(card));
                    card.classList.toggle('fl-hide', !ok);
                    if (ok) visible++;
                });

                if (visibleCounter) visibleCounter.textContent = String(visible);

                if (emptyFiltered) {
                    emptyFiltered.hidden = visible !== 0 || cards().length === 0;
                }

                sortCards();
            }

            side?.addEventListener('change', applyFilters);

            priceEl?.addEventListener('input', () => {
                if (priceLabel) priceLabel.textContent = priceEl.value;
                applyFilters();
            });

            /* ====================== RESET ====================== */
            function doReset() {
                side?.querySelectorAll('.fl-filter').forEach(i => {
                    i.checked = true;
                });

                if (priceEl) {
                    priceEl.value = String(priceEl.max);
                    if (priceLabel) priceLabel.textContent = priceEl.value;
                }

                applyFilters();
            }

            document.getElementById('fl-reset')?.addEventListener('click', doReset);
            document.getElementById('fl-empty-reset')?.addEventListener('click', doReset);

            /* ====================== ACCORDION COLLAPSE ====================== */
            document.querySelectorAll('[data-fl-fset] .fl-fset__title').forEach(title => {
                title.addEventListener('click', () => {
                    const fset = title.closest('[data-fl-fset]');
                    if (fset) fset.classList.toggle('is-collapsed');
                });
            });

            /* ====================== EXPAND DETAILS ====================== */
            document.querySelectorAll('[data-fl-expand]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const leg = btn.closest('.fl-leg');
                    if (!leg) return;

                    const details = leg.querySelector('[data-fl-details]');
                    if (!details) return;

                    const open = btn.classList.toggle('is-open');
                    details.hidden = !open;

                    const lbl = btn.querySelector('[data-fl-expand-label]');
                    if (lbl) lbl.textContent = open ? 'Hide Details' : 'Flight Details';
                });
            });

            /* Initial */
            applyFilters();
        })();
    </script>
@endpush
