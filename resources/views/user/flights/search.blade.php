@extends('user.layouts.main')
@section('content')
    @php
        $query = request()->query();
        /** @var string $tripType */
        $tripType = $tripType ?? ($query['trip_type'] ?? 'one_way');
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
        $roundTripTabs = ($tripType === 'round_trip');

        function bff_city_line(array $segment, string $airportCode): string {
            $c = trim((string) ($segment['departure_city'] ?? ''));
            if ($c !== '') {
                return "{$c}";
            }

            return $airportCode;
        }

        function bff_format_hm(?int $mins): string {
            if ($mins === null || $mins < 1) {
                return '?';
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

        function bff_segment_minutes(array $seg): ?int {
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

        function bff_layover_minutes(array $prev, array $next): ?int {
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

        function bff_carrier_logo(?string $code): string {
            $c = strtoupper(trim((string) ($code ?: 'XX')));

            return "https://pics.avs.io/112/112/{$c}.png";
        }
    @endphp

    <div class="bff-page hl-page">
        <div class="container">
            <div class="hl-search-bar mb-3">
                @include('user.vue.main', [
                    'appId' => 'flights-search',
                    'appComponent' => 'flights-search',
                    'appJs' => 'flights-search',
                    'flightSearchListingMode' => true,
                ])
            </div>

            @if (!empty($messages))
                <div class="bff-alerts mb-3">
                    @foreach ($messages as $msg)
                        @php $sev = strtoupper(trim((string) ($msg['severity'] ?? ''))); @endphp
                        <div
                            class="bff-alert {{ $sev === 'ERROR' ? 'bff-alert--err' : 'bff-alert--info' }}">{{ $msg['text'] ?? '' }}</div>
                    @endforeach
                </div>
            @endif

            @if (($itineraryCount ?? 0) > 0)
                <div class="fl-note mb-3">
                    <span class="fl-note__icon"><i class='bx bxs-megaphone'></i></span>
                    <span><strong>Note:</strong> Creating multiple bookings for the same passenger on the
                        same airline may result in ADM and the same will be debited to the agent.</span>
                </div>

                <div class="fl-toolbar mb-4">
                    <div class="fl-toolbar__left">
                        <span class="fl-toolbar__count">
                            Showing <strong id="bff-showing-meta">{{ $itineraryCount }}</strong>
                            of <strong>{{ $itineraryCount }}</strong> fares
                        </span>
                    </div>

                    <div class="fl-toolbar__sort">
                        <span class="fl-toolbar__sort-label">Sort By:</span>
                        <div class="fl-sort-pills" role="tablist" aria-label="Sort flights">
                            <button type="button" class="fl-sort-pill" data-bff-sort="airline">Airline</button>
                            <button type="button" class="fl-sort-pill" data-bff-sort="departure-o">Departure</button>
                            <button type="button" class="fl-sort-pill" data-bff-sort="duration-o-asc">Duration</button>
                            <button type="button" class="fl-sort-pill" data-bff-sort="best-value">Best Value</button>
                            <button type="button" class="fl-sort-pill" data-bff-sort="arrival-o">Arrival</button>
                            <button type="button" class="fl-sort-pill is-active" data-bff-sort="price-asc"
                                data-bff-cycle="price-asc,price-desc">
                                Price <i class='bx bx-down-arrow-alt fl-sort-pill__dir'></i>
                            </button>
                        </div>
                        <select class="fl-sort-select-fallback" id="bff-sort" aria-hidden="true" tabindex="-1">
                            <option value="price-asc" selected>Price — low to high</option>
                            <option value="price-desc">Price — high to low</option>
                            <option value="duration-o-asc">Outward duration</option>
                            <option value="departure-o">Outward departure</option>
                            <option value="arrival-o">Outward arrival</option>
                            <option value="airline">Airline</option>
                            <option value="best-value">Best value</option>
                        </select>
                    </div>
                </div>
            @else
                <div class="fl-toolbar mb-4">
                    <div class="fl-toolbar__left">
                        <span class="fl-toolbar__count"><strong>{{ $itineraryCount ?? 0 }}</strong> fares found</span>
                    </div>
                </div>
            @endif

            @if (($itineraryCount ?? 0) > 0)
                <div class="bff-layout row g-4">
                    <aside class="bff-sidebar col-xl-3 col-lg-4 col-md-12" id="bff-filters-sidebar">
                        <div class="bff-sidebar__inner">
                            <div class="bff-sidebar-header">
                                <span class="fl-eyebrow">
                                    <span class="fl-eyebrow__dot"></span>
                                    <span>Refine</span>
                                </span>
                                <h3 class="bff-sidebar-title">Filter Your Search</h3>
                            </div>

                            @if ($roundTripTabs)
                                <div class="bff-leg-tabs">
                                    <button type="button" class="bff-tab is-active"
                                        data-bff-tab-target="leg-out">
                                        <i class='bx bxs-plane-take-off'></i> Onward
                                    </button>
                                    <button type="button" class="bff-tab" data-bff-tab-target="leg-ret">
                                        <i class='bx bxs-plane-land'></i> Return
                                    </button>
                                </div>
                            @endif

                            <div id="bff-advanced-filters">

                                <!-- Stops -->
                                <details class="bff-acc" open>
                                    <summary>Stops <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body bff-chip-grid">
                                        @foreach ([0 => 'Non-stop', 1 => '1 stop', 2 => '2+ stops'] as $tier => $label)
                                            <label class="bff-chip-option">
                                                <input type="checkbox" name="stops-o" value="{{ $tier }}"
                                                    class="bff-inp-stops-o" checked>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($roundTripTabs)
                                        <div class="bff-leg-panel bff-leg-panel--hidden" data-bff-leg="ret">
                                            <div class="bff-micro-label">Return</div>
                                            <div class="bff-acc-body bff-chip-grid">
                                                @foreach ([0 => 'Non-stop', 1 => '1 stop', 2 => '2+ stops'] as $tier => $label)
                                                    <label class="bff-chip-option">
                                                        <input type="checkbox" name="stops-r"
                                                            value="{{ $tier }}" class="bff-inp-stops-r"
                                                            checked>
                                                        <span>{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </details>

                                <!-- Depart times -->
                                <details class="bff-acc">
                                    <summary>Departure times <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body">
                                        @foreach ([1 => 'Morning 05‑12', 2 => 'Day 12‑18', 3 => 'Evening 18‑24', 4 => 'Night 24‑05'] as $b => $bl)
                                            <label class="bff-check">
                                                <input type="checkbox" class="bff-inp-dep-o"
                                                    data-bucket="{{ $b }}" checked>
                                                <span>{{ $bl }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($roundTripTabs)
                                        <div class="bff-leg-panel bff-leg-panel--hidden"
                                            data-bff-leg="ret">
                                            <div class="bff-micro-label">Return departures</div>
                                            @foreach ([1 => 'Morning', 2 => 'Day', 3 => 'Evening', 4 => 'Night'] as $b => $bl)
                                                <label class="bff-check">
                                                    <input type="checkbox" class="bff-inp-dep-r"
                                                        data-bucket="{{ $b }}" checked>
                                                    <span>{{ $bl }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </details>

                                <!-- Arrival times -->
                                <details class="bff-acc">
                                    <summary>Arrival times <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body">
                                        @foreach ([1 => 'Morning', 2 => 'Day', 3 => 'Evening', 4 => 'Night'] as $b => $bl)
                                            <label class="bff-check">
                                                <input type="checkbox" class="bff-inp-arr-o"
                                                    data-bucket="{{ $b }}" checked>
                                                <span>{{ $bl }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($roundTripTabs)
                                        <div class="bff-leg-panel bff-leg-panel--hidden"
                                            data-bff-leg="ret">
                                            <div class="bff-micro-label">Return arrivals</div>
                                            @foreach ([1 => 'Morning', 2 => 'Day', 3 => 'Evening', 4 => 'Night'] as $b => $bl)
                                                <label class="bff-check">
                                                    <input type="checkbox" class="bff-inp-arr-r"
                                                        data-bucket="{{ $b }}" checked>
                                                    <span>{{ $bl }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </details>

                                <!-- Price -->
                                <details class="bff-acc" open>
                                    <summary>Price range <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body">
                                        @php
                                            $pmax =
                                                max((float) $priceRange['max'], (float) $priceRange['min'] + 1);
                                            $pmin = (float) $priceRange['min'];
                                        @endphp
                                        <input type="range" class="bff-range"
                                            min="{{ floor($pmin) }}" max="{{ ceil($pmax) }}"
                                            value="{{ ceil($pmax) }}" id="bff-price-cap">
                                        <div class="bff-range-labels">
                                            <span>{{ strtoupper(($results[0]['currency'] ?? 'AED')) }}
                                                {{ number_format($pmin, 0) }}</span>
                                            <span id="bff-price-cap-label">{{ number_format($pmax, 0) }}</span>
                                        </div>
                                    </div>
                                </details>

                                <!-- Depart airports -->
                                <details class="bff-acc">
                                    <summary>Departure airports <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body bff-scroll">
                                        @foreach ($filterCatalog['dep_out'] ?? [] as $apt)
                                            <label class="bff-check">
                                                <input type="checkbox" class="bff-inp-depa-o"
                                                    value="{{ $apt }}" checked>
                                                <span>{{ $apt }}</span>
                                            </label>
                                        @endforeach
                                        @if ($roundTripTabs)
                                            @foreach ($filterCatalog['dep_ret'] ?? [] as $apt)
                                                <label class="bff-check bff-leg-only-ret">
                                                    <input type="checkbox" class="bff-inp-depa-r"
                                                        value="{{ $apt }}" checked>
                                                    <span>RT • {{ $apt }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                </details>

                                <!-- Fare type (Sabre heuristics) -->
                                <details class="bff-acc">
                                    <summary>Fare type <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body">
                                        <label class="bff-check">
                                            <input type="checkbox" class="bff-inp-fare" value="ndc"
                                                checked>NDC‑style feeds</label>
                                        <label class="bff-check">
                                            <input type="checkbox" class="bff-inp-fare"
                                                value="published" checked>ATPCO / published mirrors</label>
                                        <label class="bff-check">
                                            <input type="checkbox" class="bff-inp-fare" value="other"
                                                checked>Other sourcing</label>
                                        <p class="bff-hint">Tags follow Sabre <code>pricingSubsource</code>
                                            heuristics; refine as your PCC maps expand.</p>
                                    </div>
                                </details>

                                <!-- Airlines -->
                                <details class="bff-acc" open>
                                    <summary>Airlines <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body bff-scroll">
                                        @foreach ($filterCatalog['airlines'] ?? [] as $al)
                                            <label class="bff-check">
                                                <input type="checkbox" class="bff-inp-al"
                                                    value="{{ $al['code'] }}" checked>
                                                <span><strong>{{ $al['code'] }}</strong><span class="bff-muted">
                                                        ({{ $al['count'] }})</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>

                                <!-- Connections -->
                                <details class="bff-acc">
                                    <summary>Connecting airports <i class='bx bx-chevron-down'></i></summary>
                                    <div class="bff-acc-body bff-scroll">
                                        @foreach ($filterCatalog['conn_out'] ?? [] as $cx)
                                            <label class="bff-check">
                                                <input type="checkbox" class="bff-inp-conn-o"
                                                    value="{{ $cx }}" checked>
                                                <span>{{ $cx }}</span>
                                            </label>
                                        @endforeach
                                        @if ($roundTripTabs)
                                            @foreach ($filterCatalog['conn_ret'] ?? [] as $cx)
                                                <label class="bff-check bff-leg-only-ret">
                                                    <input type="checkbox" class="bff-inp-conn-r"
                                                        value="{{ $cx }}" checked>
                                                    <span>RT • {{ $cx }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                </details>

                                <button type="button" class="bff-reset-btn" id="bff-reset">
                                    Reset filters
                                </button>
                            </div>
                        </div>
                    </aside>

                    <div class="bff-main col-xl-9 col-lg-8 col-md-12">
                        <div class="bff-list" id="bff-results">
                            @foreach ($results as $result)
                                @php
                                    $lid = $result['id'];
                                    $meta = $result['listing_meta'] ?? [];

                                    $seatsListed = [];

                                    foreach ($result['legs'] ?? [] as $lg) {
                                        foreach ($lg['segments'] ?? [] as $seg) {
                                            if (isset($seg['seats_available'])) {
                                                $seatsListed[] = (int) $seg['seats_available'];
                                            }
                                        }
                                    }
                                    $seatMin = !empty($seatsListed) ? min($seatsListed) : null;

                                    $firstSeg = (($result['legs'][0]['segments'][0]) ?? []);
                                    $headlineCarrierCode = $firstSeg['carrier'] ?? '';
                                @endphp
                                <article class="bff-card flcard" id="bff-card-{{ $lid }}"
                                    data-bff-meta='@json($meta)' data-bff-id="{{ $lid }}">
                                    <header class="flcard__head">
                                        <div class="flcard__head-left">
                                            <span class="fl-eyebrow">
                                                <span class="fl-eyebrow__dot"></span>
                                                <span>Itinerary #{{ $loop->iteration }}</span>
                                            </span>
                                            <div class="flcard__head-pills">
                                                @if (!empty($result['validating_carrier']))
                                                    <span class="flpill flpill--vc">
                                                        <i class='bx bxs-shield-x'></i>
                                                        VC · {{ strtoupper($result['validating_carrier']) }}
                                                    </span>
                                                @endif

                                                @if (!empty($result['pricing_subsource']))
                                                    <span class="flpill flpill--src">
                                                        {{ strtoupper((string) $result['pricing_subsource']) }}
                                                    </span>
                                                @endif

                                                @if ($result['non_refundable'] ?? false)
                                                    <span class="flpill flpill--warn">
                                                        <i class='bx bx-x-circle'></i> Non-Refundable
                                                    </span>
                                                @else
                                                    <span class="flpill flpill--ok">
                                                        <i class='bx bx-check-circle'></i> Refundable
                                                    </span>
                                                @endif

                                                @if (!empty($result['supplier']))
                                                    <span class="flpill flpill--desk">
                                                        Desk · {{ strtoupper((string) $result['supplier']) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flcard__head-right">
                                            @if (!empty($result['baggage_notes']))
                                                <span class="flchip flchip--bag">
                                                    <i class='bx bx-briefcase-alt-2'></i>
                                                    {{ $result['baggage_notes'] }}
                                                </span>
                                            @endif
                                            @if ($seatMin !== null)
                                                <span class="flchip flchip--seats">
                                                    <i class='bx bx-group'></i>
                                                    {{ $seatMin }} seats
                                                </span>
                                            @endif
                                        </div>
                                    </header>

                                    <div class="flcard__body">
                                        @foreach ($result['legs'] ?? [] as $legIndex => $leg)
                                            @php
                                                $segments = isset($leg['segments']) && is_array($leg['segments'])
                                                    ? $leg['segments']
                                                    : [];
                                                $seg0 = $segments[0] ?? [];
                                                $lastSeg = [];
                                                if ($segments !== []) {
                                                    $lk = array_key_last($segments);
                                                    $lastSeg =
                                                        isset($segments[$lk]) && is_array($segments[$lk])
                                                            ? $segments[$lk]
                                                            : [];
                                                }
                                                $tierConn = max(0, count($segments) - 1);
                                                $tierStop = collect($segments)->sum(
                                                    fn ($s) => (int) ($s['stop_count'] ?? 0),
                                                );
                                                $stopsTotal = $tierConn + $tierStop;
                                                $stopsPhrase =
                                                    $stopsTotal === 0
                                                        ? 'Non-stop'
                                                        : ($stopsTotal === 1
                                                            ? '1 stop'
                                                            : $stopsTotal . ' stops');

                                                $legBadge =
                                                    $legIndex === 0
                                                        ? ($roundTripTabs ? 'Onward' : 'Flight')
                                                        : 'Return';

                                                $legBadgeMod = $legIndex === 0 ? 'fl-leg-tag--out' : 'fl-leg-tag--ret';
                                            @endphp

                                            <section class="flleg {{ $legBadgeMod }}"
                                                data-leg-index="{{ $legIndex }}">
                                                <aside class="flleg__rail">
                                                    <span class="flleg__rail-num">{{ $legIndex + 1 }}</span>
                                                    <span class="flleg__rail-line"></span>
                                                </aside>

                                                <div class="flleg__main">
                                                    <header class="flleg__head">
                                                        <div class="flleg__airline">
                                                            <img class="flleg__logo"
                                                                src="{{ bff_carrier_logo($seg0['carrier'] ?? '') }}"
                                                                loading="lazy" alt=""
                                                                onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(trim((string) ($seg0['carrier'] ?? 'FL'))) }}&background=cd1b4f&color=fff'" />
                                                            <div>
                                                                <div class="flleg__airline-name">
                                                                    {{ $seg0['carrier_display'] ?? ($seg0['carrier'] ?? '') }}
                                                                </div>
                                                                <div class="flleg__airline-meta">
                                                                    {{ $seg0['carrier'] ?? '' }}{{ $seg0['flight_number'] ?? '' }}
                                                                    @if (($seg0['operating_carrier'] ?? '') &&
                                                                            ($seg0['operating_carrier'] ?? '') !== ($seg0['carrier'] ?? ''))
                                                                        <span class="flleg__op">· Op
                                                                            {{ $seg0['operating_carrier'] }}@if (!empty($seg0['operating_flight_number']))
                                                                                {{ $seg0['operating_flight_number'] }}
                                                                            @endif
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <span class="fl-leg-tag {{ $legBadgeMod }}">
                                                            @if ($legIndex === 0)
                                                                <i class='bx bxs-plane-take-off'></i>
                                                            @else
                                                                <i class='bx bxs-plane-land'></i>
                                                            @endif
                                                            {{ $legBadge }}
                                                        </span>
                                                    </header>

                                                    <div class="flleg__timeline">
                                                        <div class="flleg__point flleg__point--dep">
                                                            <strong class="flleg__time">
                                                                {{ $seg0['departure_clock'] ?? '-' }}
                                                                @if ($seg0['is_red_eye_segment'] ?? false)
                                                                    <i class='bx bxs-moon flleg__redeye'
                                                                        title="Red-eye"></i>
                                                                @endif
                                                            </strong>
                                                            <span class="flleg__date">
                                                                {{ $seg0['departure_weekday'] ?? '' }},
                                                                {{ $seg0['departure_label'] ?? '' }}
                                                            </span>
                                                            <span class="flleg__city">
                                                                {{ bff_city_line($seg0, $seg0['from'] ?? '') }}
                                                            </span>
                                                            <span class="flleg__airport">
                                                                {{ $seg0['from'] ?? '' }}@if (!empty($seg0['departure_terminal']))
                                                                    · T{{ $seg0['departure_terminal'] }}
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="flleg__bridge">
                                                            <span class="flleg__bridge-time">
                                                                {{ bff_format_hm(isset($leg['elapsedTime']) ? (int) $leg['elapsedTime'] : null) }}
                                                            </span>
                                                            <div class="flleg__bridge-line">
                                                                <span class="flleg__bridge-plane"><i
                                                                        class='bx bxs-plane'></i></span>
                                                            </div>
                                                            <span class="flleg__bridge-stops">
                                                                @if ($stopsTotal === 0)
                                                                    <span class="fl-stop fl-stop--direct">Non-stop</span>
                                                                @else
                                                                    <span class="fl-stop fl-stop--via">{{ $stopsPhrase }}</span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="flleg__point flleg__point--arr">
                                                            <strong class="flleg__time">
                                                                {{ $lastSeg['arrival_clock'] ?? '-' }}
                                                                @if ($lastSeg['next_day_hint'] ?? false)
                                                                    <span class="flleg__nextday"
                                                                        title="Arrives next day">+1</span>
                                                                @endif
                                                            </strong>
                                                            <span class="flleg__date">
                                                                {{ $lastSeg['arrival_weekday'] ?? '' }},
                                                                {{ $lastSeg['arrival_label'] ?? '' }}
                                                            </span>
                                                            <span class="flleg__city">
                                                                {{ bff_city_line($lastSeg, $lastSeg['to'] ?? '') }}
                                                            </span>
                                                            <span class="flleg__airport">
                                                                {{ $lastSeg['to'] ?? '' }}@if (!empty($lastSeg['arrival_terminal']))
                                                                    · T{{ $lastSeg['arrival_terminal'] }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="flleg__foot">
                                                        @if (count($segments) > 1)
                                                            <div class="flleg__chain">
                                                                @foreach ($segments as $sx)
                                                                    <span class="flleg__chip">{{ $sx['from'] ?? '' }}
                                                                        →
                                                                        {{ $sx['to'] ?? '' }}</span>
                                                                    @if (!$loop->last)
                                                                        <i class='bx bx-chevron-right flleg__chain-sep'></i>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        <button type="button" class="flleg__toggle"
                                                            data-fl-toggle>
                                                            <i class='bx bx-list-ul'></i>
                                                            <span class="flleg__toggle-label">Flight Details</span>
                                                            <i class='bx bx-chevron-down flleg__toggle-arrow'></i>
                                                        </button>
                                                    </div>

                                                    <div class="flleg__details" data-fl-details hidden>
                                                        @foreach ($segments as $segIdx => $sx)
                                                            @php
                                                                $sxMins = bff_segment_minutes($sx);
                                                                $sxLayover =
                                                                    $segIdx > 0
                                                                        ? bff_layover_minutes($segments[$segIdx - 1], $sx)
                                                                        : null;
                                                            @endphp

                                                            @if ($sxLayover !== null)
                                                                <div class="flseg-layover">
                                                                    <i class='bx bx-time-five'></i>
                                                                    <strong>{{ bff_format_hm($sxLayover) }}</strong>
                                                                    layover at
                                                                    <strong>{{ $sx['from'] ?? '' }}</strong>
                                                                    @if (!empty($sx['departure_city']))
                                                                        · {{ $sx['departure_city'] }}
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            <div class="flseg">
                                                                <div class="flseg__air">
                                                                    <img class="flseg__logo"
                                                                        src="{{ bff_carrier_logo($sx['carrier'] ?? '') }}"
                                                                        loading="lazy" alt=""
                                                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode(trim((string) ($sx['carrier'] ?? 'FL'))) }}&background=cd1b4f&color=fff'" />
                                                                    <div>
                                                                        <div class="flseg__air-name">
                                                                            {{ $sx['carrier_display'] ?? ($sx['carrier'] ?? '') }}
                                                                        </div>
                                                                        <div class="flseg__air-meta">
                                                                            {{ $sx['carrier'] ?? '' }}{{ $sx['flight_number'] ?? '' }}
                                                                            @if (($sx['operating_carrier'] ?? '') &&
                                                                                    ($sx['operating_carrier'] ?? '') !== ($sx['carrier'] ?? ''))
                                                                                · Op
                                                                                {{ $sx['operating_carrier'] }}{{ $sx['operating_flight_number'] ?? '' }}
                                                                            @endif
                                                                            @if (!empty($sx['equipment']))
                                                                                · {{ $sx['equipment'] }}
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="flseg__route">
                                                                    <div class="flseg__pt">
                                                                        <strong>{{ $sx['departure_clock'] ?? '-' }}</strong>
                                                                        <span>{{ $sx['from'] ?? '' }}
                                                                            @if (!empty($sx['departure_city']))
                                                                                · {{ $sx['departure_city'] }}
                                                                            @endif
                                                                        </span>
                                                                        <small>{{ $sx['departure_label'] ?? '' }}@if (!empty($sx['departure_terminal']))
                                                                                · T{{ $sx['departure_terminal'] }}
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                    <div class="flseg__dur">
                                                                        <span>{{ bff_format_hm($sxMins) }}</span>
                                                                        <div class="flseg__dur-line"></div>
                                                                        @if ((int) ($sx['stop_count'] ?? 0) > 0)
                                                                            <small>{{ (int) $sx['stop_count'] }}
                                                                                tech stop</small>
                                                                        @endif
                                                                    </div>
                                                                    <div class="flseg__pt flseg__pt--arr">
                                                                        <strong>{{ $sx['arrival_clock'] ?? '-' }}</strong>
                                                                        <span>{{ $sx['to'] ?? '' }}
                                                                            @if (!empty($sx['arrival_city']))
                                                                                · {{ $sx['arrival_city'] }}
                                                                            @endif
                                                                        </span>
                                                                        <small>{{ $sx['arrival_label'] ?? '' }}@if (!empty($sx['arrival_terminal']))
                                                                                · T{{ $sx['arrival_terminal'] }}
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                </div>

                                                                <div class="flseg__meta">
                                                                    @if (!empty($sx['cabin_code']))
                                                                        <span class="flseg__tag flseg__tag--cab">
                                                                            {{ $sx['cabin_code'] }}
                                                                        </span>
                                                                    @endif
                                                                    @if (!empty($sx['booking_code']))
                                                                        <span class="flseg__tag">
                                                                            Class {{ $sx['booking_code'] }}
                                                                        </span>
                                                                    @endif
                                                                    @if (isset($sx['seats_available']))
                                                                        <span class="flseg__tag flseg__tag--seat">
                                                                            <i class='bx bx-group'></i>
                                                                            {{ (int) $sx['seats_available'] }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </section>
                                        @endforeach
                                    </div>

                                    <footer class="flcard__foot">
                                        <div class="flcard__fare">
                                            @if ($firstSeg)
                                                <span class="flcard__fare-cabin">
                                                    {{ $firstSeg['cabin_code'] ?? 'Y' }}
                                                </span>
                                                @if (!empty($firstSeg['booking_code']))
                                                    <span class="flcard__fare-rbd">Class
                                                        {{ $firstSeg['booking_code'] }}</span>
                                                @endif
                                            @endif

                                            @foreach ($result['fare_tags'] ?? [] as $ft)
                                                <span class="flcard__fare-tag">{{ strtoupper((string) $ft) }}</span>
                                            @endforeach
                                        </div>

                                        <div class="flcard__price">
                                            <span class="flcard__price-label">Net Fare</span>
                                            <span class="flcard__price-amount">
                                                <span class="flcard__price-currency">{{ strtoupper(($result['currency'] ?? 'AED')) }}</span>
                                                {{ number_format($result['totalPrice'] ?? 0, 2) }}
                                            </span>
                                            @if ($seatMin !== null)
                                                <span class="flcard__price-seats">
                                                    <i class='bx bx-user'></i> {{ $seatMin }} seats
                                                </span>
                                            @endif
                                        </div>

                                        <a href="{{ route('user.flights.checkout', ['itinerary' => $lid] + $query) }}"
                                            class="flcard__cta">
                                            Continue
                                            <i class='bx bx-right-arrow-alt'></i>
                                        </a>
                                    </footer>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif (!empty(request()->except(['page'])) && empty($results ?? []))
                <div class="fl-empty">
                    <div class="fl-empty__icon"><i class='bx bx-search-alt-2'></i></div>
                    <h3 class="fl-empty__title">No flights match this search</h3>
                    <p class="fl-empty__copy">We couldn't find itineraries for the current route, dates and
                        passengers. Widen the dates or try nearby airports, then run the search again.</p>
                    <div class="fl-empty__actions">
                        <a href="{{ route('user.flights.index') }}" class="fl-btn fl-btn--primary">
                            <i class='bx bx-edit'></i> Modify Search
                        </a>
                    </div>
                </div>
            @else
                <div class="fl-empty">
                    <div class="fl-empty__icon fl-empty__icon--idle"><i class='bx bxs-plane-take-off'></i></div>
                    <h3 class="fl-empty__title">Ready when you are</h3>
                    <p class="fl-empty__copy">Choose your trip type, cities and dates above. We'll query live
                        Sabre inventory and surface consolidator-grade pricing.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('css')
    <style>
        .bff-page {
            --fl-brand: #cd1b4f;
            --fl-brand-2: #b41642;
            --fl-brand-soft: #fce7ef;
            --fl-brand-tint: rgba(205, 27, 79, .08);
            --fl-ink: #15233f;
            --fl-ink-2: #1f2937;
            --fl-slate: #475569;
            --fl-slate-2: #64748b;
            --fl-muted: #94a3b8;
            --fl-line: #e5e9f1;
            --fl-line-soft: #eef2f7;
            --fl-surface: #ffffff;
            --fl-surface-2: #f7f9fc;
            --fl-canvas: #f1f4f9;
            --fl-emerald: #047857;
            --fl-emerald-soft: #ecfdf5;
            --fl-amber: #d97706;
            --fl-amber-soft: #fff8e8;
            --fl-blue: #2563eb;
            --fl-blue-soft: #e8f1ff;
            --fl-violet: #8b5cf6;
            --fl-violet-soft: #ede7fe;
            --fl-lavender: #eef0ff;
            --fl-shadow-sm: 0 1px 2px rgba(15, 23, 42, .05);
            --fl-shadow-md: 0 6px 18px rgba(15, 23, 42, .06);
            --fl-shadow-lg: 0 18px 38px -22px rgba(15, 23, 42, .28);
            --fl-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

            font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                "Helvetica Neue", Arial, sans-serif;
            color: var(--fl-ink);
            padding-bottom: 3rem;
        }

        .bff-page * {
            box-sizing: border-box;
        }

        /* ============================================================
           NOTE BANNER + TOOLBAR
           ============================================================ */
        .fl-note {
            display: flex;
            gap: .75rem;
            align-items: center;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-left: 4px solid var(--fl-amber);
            border-radius: 14px;
            padding: .65rem .9rem;
            font-size: .78rem;
            color: var(--fl-slate);
            box-shadow: var(--fl-shadow-sm);
        }

        .fl-note strong {
            color: var(--fl-ink);
            font-weight: 700;
        }

        .fl-note__icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .fl-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem 1rem;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 16px;
            padding: .65rem .95rem;
            box-shadow: var(--fl-shadow-md);
        }

        .fl-toolbar__left {
            display: flex;
            gap: .75rem;
            align-items: baseline;
        }

        .fl-toolbar__count {
            font-size: .82rem;
            color: var(--fl-slate-2);
            font-weight: 600;
        }

        .fl-toolbar__count strong {
            color: var(--fl-ink);
            font-variant-numeric: tabular-nums;
            font-weight: 800;
        }

        .fl-toolbar__sort {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .fl-toolbar__sort-label {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--fl-brand);
        }

        .fl-sort-pills {
            display: flex;
            gap: .25rem;
            flex-wrap: wrap;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            border-radius: 999px;
            padding: .2rem;
        }

        .fl-sort-pill {
            border: none;
            background: transparent;
            font: inherit;
            cursor: pointer;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            color: var(--fl-slate);
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            transition: color .15s ease, background-color .15s ease, box-shadow .15s ease;
        }

        .fl-sort-pill:hover {
            color: var(--fl-ink);
        }

        .fl-sort-pill.is-active {
            background: var(--fl-surface);
            color: var(--fl-brand);
            box-shadow: var(--fl-shadow-sm);
        }

        .fl-sort-pill__dir {
            font-size: 1rem;
            transition: transform .2s ease;
        }

        .fl-sort-pill.is-desc .fl-sort-pill__dir {
            transform: rotate(180deg);
        }

        .fl-sort-select-fallback {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
            pointer-events: none;
        }

        /* ============================================================
           EYEBROW (re-used for sidebar header and card eyebrow)
           ============================================================ */
        .fl-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .12rem .55rem;
            border-radius: 999px;
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
            font-size: .58rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .14em;
        }

        .fl-eyebrow__dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--fl-brand);
            box-shadow: 0 0 0 3px rgba(205, 27, 79, .18);
        }

        /* ============================================================
           SIDEBAR
           ============================================================ */
        .bff-sidebar__inner {
            position: sticky;
            top: 1rem;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 18px;
            padding: 1.1rem 1rem;
            box-shadow: var(--fl-shadow-md);
        }

        .bff-sidebar-header {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            margin-bottom: .9rem;
            padding-bottom: .75rem;
            border-bottom: 1px solid var(--fl-line-soft);
        }

        .bff-sidebar-title {
            font-size: 1.02rem;
            font-weight: 800;
            color: var(--fl-ink);
            margin: 0;
            letter-spacing: -.01em;
        }

        .bff-leg-tabs {
            display: flex;
            gap: .3rem;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            border-radius: 999px;
            padding: .2rem;
            margin-bottom: 1rem;
        }

        .bff-tab {
            flex: 1;
            border: none;
            background: transparent;
            padding: .4rem .55rem;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 700;
            color: var(--fl-slate);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .3rem;
            transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .bff-tab i {
            font-size: .95rem;
        }

        .bff-tab.is-active {
            background: var(--fl-surface);
            color: var(--fl-brand);
            box-shadow: var(--fl-shadow-sm);
        }

        details.bff-acc {
            border: none;
            border-top: 1px solid var(--fl-line-soft);
            margin: 0;
            padding: .65rem 0;
        }

        details.bff-acc:first-of-type {
            border-top: none;
            padding-top: .25rem;
        }

        .bff-acc summary {
            list-style: none;
            cursor: pointer;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--fl-ink);
            padding: .15rem 0;
        }

        .bff-acc summary i {
            color: var(--fl-muted);
            transition: transform .2s ease;
            font-size: 1rem;
        }

        .bff-acc[open] summary i {
            transform: rotate(180deg);
            color: var(--fl-brand);
        }

        .bff-acc summary::-webkit-details-marker {
            display: none;
        }

        .bff-acc-body {
            margin-top: .55rem;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .bff-chip-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .4rem;
        }

        .bff-chip-option {
            position: relative;
        }

        .bff-chip-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .bff-chip-option span {
            display: block;
            text-align: center;
            padding: .45rem .25rem;
            border-radius: 12px;
            background: var(--fl-surface-2);
            font-size: .75rem;
            font-weight: 700;
            color: var(--fl-slate);
            border: 1px solid var(--fl-line);
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .bff-chip-option input:checked+span {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
            border-color: rgba(205, 27, 79, .35);
        }

        .bff-check {
            display: flex;
            gap: .55rem;
            align-items: center;
            font-size: .82rem;
            color: var(--fl-ink-2);
            cursor: pointer;
            padding: .15rem 0;
        }

        .bff-check input {
            width: 16px;
            height: 16px;
            accent-color: var(--fl-brand);
            cursor: pointer;
        }

        .bff-scroll {
            max-height: 180px;
            overflow: auto;
            padding-right: .25rem;
        }

        .bff-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .bff-scroll::-webkit-scrollbar-thumb {
            background: var(--fl-line);
            border-radius: 999px;
        }

        .bff-range {
            width: 100%;
            accent-color: var(--fl-brand);
        }

        .bff-range-labels {
            display: flex;
            justify-content: space-between;
            margin-top: .35rem;
            font-size: .75rem;
            color: var(--fl-slate);
            font-family: var(--fl-mono);
            font-weight: 700;
        }

        .bff-reset-btn {
            width: 100%;
            border-radius: 999px;
            border: 1px solid rgba(205, 27, 79, .35);
            background: transparent;
            padding: .55rem .85rem;
            margin-top: .95rem;
            font-weight: 700;
            color: var(--fl-brand);
            font-size: .82rem;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease;
        }

        .bff-reset-btn:hover {
            background: var(--fl-brand);
            color: #fff;
        }

        .bff-micro-label {
            font-size: .65rem;
            font-weight: 800;
            color: var(--fl-brand);
            letter-spacing: .14em;
            text-transform: uppercase;
            margin: .55rem 0 .25rem;
        }

        .bff-leg-panel--hidden[data-bff-leg="ret"]:not(.is-visible-panel),
        .bff-leg-only-ret:not(.is-visible-panel) {
            display: none !important;
        }

        .bff-muted {
            color: var(--fl-muted);
        }

        .bff-hint {
            font-size: .73rem;
            color: var(--fl-muted);
            margin: .35rem 0 0;
        }

        .bff-hint code {
            font-family: var(--fl-mono);
            background: var(--fl-canvas);
            color: var(--fl-slate);
            padding: .05rem .3rem;
            border-radius: 6px;
            font-size: .68rem;
        }

        /* ============================================================
           ALERTS
           ============================================================ */
        .bff-alert {
            padding: .65rem .85rem;
            border-radius: 12px;
            font-size: .85rem;
            font-weight: 600;
            border: 1px solid transparent;
            margin-bottom: .5rem;
        }

        .bff-alert--err {
            border-color: rgba(244, 63, 94, .35);
            background: #fff1f2;
            color: #9f1239;
        }

        .bff-alert--info {
            border-color: rgba(37, 99, 235, .25);
            background: var(--fl-blue-soft);
            color: #1d4ed8;
        }

        /* ============================================================
           CARD
           ============================================================ */
        .flcard {
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 18px;
            box-shadow: var(--fl-shadow-md);
            margin-bottom: 1.1rem;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .flcard:hover {
            border-color: rgba(205, 27, 79, .35);
            box-shadow: var(--fl-shadow-lg);
            transform: translateY(-1px);
        }

        .flcard.bff-hide {
            display: none !important;
        }

        .flcard__head {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: .85rem 1rem;
            background: linear-gradient(180deg, var(--fl-surface-2), var(--fl-surface));
            border-bottom: 1px solid var(--fl-line-soft);
        }

        .flcard__head-left {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            min-width: 0;
        }

        .flcard__head-pills {
            display: flex;
            gap: .3rem;
            flex-wrap: wrap;
        }

        .flcard__head-right {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .flpill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: var(--fl-lavender);
            color: var(--fl-ink);
            border: 1px solid transparent;
        }

        .flpill i {
            font-size: .8rem;
        }

        .flpill--vc {
            background: var(--fl-blue-soft);
            color: var(--fl-blue);
        }

        .flpill--src {
            background: var(--fl-violet-soft);
            color: var(--fl-violet);
        }

        .flpill--warn {
            background: #fff1f2;
            color: #be123c;
        }

        .flpill--ok {
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
        }

        .flpill--desk {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
        }

        .flchip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            background: var(--fl-surface-2);
            color: var(--fl-slate);
            border: 1px solid var(--fl-line);
        }

        .flchip i {
            font-size: .9rem;
            color: var(--fl-brand);
        }

        .flchip--bag i {
            color: var(--fl-emerald);
        }

        .flchip--seats i {
            color: var(--fl-amber);
        }

        /* ============================================================
           CARD BODY: LEG
           ============================================================ */
        .flcard__body {
            padding: .25rem 0;
        }

        .flleg {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 0;
            padding: 1rem 1rem 0;
        }

        .flleg+.flleg {
            padding-top: 0;
        }

        .flleg__rail {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
        }

        .flleg__rail-num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--fl-brand);
            color: #fff;
            font-size: .68rem;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(205, 27, 79, .35);
        }

        .flleg--fl-leg-tag--ret .flleg__rail-num,
        .flleg.fl-leg-tag--ret .flleg__rail-num {
            background: var(--fl-blue);
            box-shadow: 0 4px 12px rgba(37, 99, 235, .35);
        }

        .flleg__rail-line {
            flex: 1;
            width: 2px;
            background: linear-gradient(180deg, var(--fl-line), transparent);
            min-height: 40px;
        }

        .flleg__main {
            padding-bottom: 1rem;
            border-bottom: 1px dashed var(--fl-line);
        }

        .flcard__body .flleg:last-child .flleg__main {
            border-bottom: none;
            padding-bottom: .5rem;
        }

        .flleg__head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: center;
            margin-bottom: .65rem;
            flex-wrap: wrap;
        }

        .flleg__airline {
            display: flex;
            gap: .65rem;
            align-items: center;
            min-width: 0;
        }

        .flleg__logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            padding: 3px;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            object-fit: contain;
            flex-shrink: 0;
        }

        .flleg__airline-name {
            font-size: .92rem;
            font-weight: 800;
            color: var(--fl-ink);
            line-height: 1.2;
        }

        .flleg__airline-meta {
            font-size: .73rem;
            color: var(--fl-slate-2);
            font-weight: 600;
            font-family: var(--fl-mono);
        }

        .flleg__op {
            color: var(--fl-muted);
            font-family: inherit;
            margin-left: .15rem;
        }

        .fl-leg-tag {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        .fl-leg-tag--out {
            background: var(--fl-brand-soft);
            color: var(--fl-brand);
        }

        .fl-leg-tag--ret {
            background: var(--fl-blue-soft);
            color: var(--fl-blue);
        }

        .fl-leg-tag i {
            font-size: .9rem;
        }

        /* timeline */
        .flleg__timeline {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(110px, 1.1fr) minmax(0, 1fr);
            gap: .85rem;
            align-items: center;
            padding: .35rem 0;
        }

        .flleg__point {
            display: flex;
            flex-direction: column;
            gap: .12rem;
            min-width: 0;
        }

        .flleg__point--arr {
            text-align: right;
            align-items: flex-end;
        }

        .flleg__time {
            font-family: var(--fl-mono);
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--fl-ink);
            letter-spacing: -.02em;
            line-height: 1;
            display: inline-flex;
            align-items: baseline;
            gap: .4rem;
        }

        .flleg__date {
            font-size: .75rem;
            font-weight: 600;
            color: var(--fl-slate);
        }

        .flleg__city {
            font-size: .92rem;
            font-weight: 700;
            color: var(--fl-ink-2);
        }

        .flleg__airport {
            font-size: .72rem;
            color: var(--fl-muted);
            font-family: var(--fl-mono);
            letter-spacing: .04em;
        }

        .flleg__redeye {
            font-size: .9rem;
            color: var(--fl-violet);
        }

        .flleg__nextday {
            font-family: var(--fl-mono);
            font-size: .65rem;
            font-weight: 800;
            color: var(--fl-amber);
            background: var(--fl-amber-soft);
            padding: .05rem .35rem;
            border-radius: 6px;
            margin-left: .25rem;
            letter-spacing: .04em;
        }

        .flleg__bridge {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            position: relative;
        }

        .flleg__bridge-time {
            font-size: .73rem;
            font-weight: 700;
            color: var(--fl-slate);
            font-family: var(--fl-mono);
            letter-spacing: .04em;
        }

        .flleg__bridge-line {
            position: relative;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--fl-line) 10%, var(--fl-line) 90%, transparent);
        }

        .flleg__bridge-line::before,
        .flleg__bridge-line::after {
            content: "";
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--fl-brand);
        }

        .flleg__bridge-line::before {
            left: 4px;
        }

        .flleg__bridge-line::after {
            right: 4px;
        }

        .flleg__bridge-plane {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            color: var(--fl-brand);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
        }

        .flleg__bridge-stops {
            display: inline-flex;
        }

        .fl-stop {
            display: inline-flex;
            padding: .18rem .6rem;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .fl-stop--direct {
            background: var(--fl-emerald-soft);
            color: var(--fl-emerald);
        }

        .fl-stop--via {
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
        }

        /* leg foot: connection chain + details toggle */
        .flleg__foot {
            margin-top: .65rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
            padding-top: .55rem;
            border-top: 1px dashed var(--fl-line-soft);
        }

        .flleg__chain {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            flex-wrap: wrap;
            font-family: var(--fl-mono);
        }

        .flleg__chip {
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            color: var(--fl-slate);
            padding: .15rem .5rem;
            border-radius: 8px;
            font-size: .7rem;
            font-weight: 700;
        }

        .flleg__chain-sep {
            color: var(--fl-muted);
            font-size: .95rem;
        }

        .flleg__toggle {
            border: none;
            background: transparent;
            color: var(--fl-brand);
            font-weight: 700;
            font-size: .78rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .55rem;
            border-radius: 8px;
            transition: background-color .15s ease;
            margin-left: auto;
        }

        .flleg__toggle:hover {
            background: var(--fl-brand-soft);
        }

        .flleg__toggle-arrow {
            transition: transform .2s ease;
            font-size: 1rem;
        }

        .flleg__toggle.is-open .flleg__toggle-arrow {
            transform: rotate(180deg);
        }

        /* details */
        .flleg__details {
            margin-top: .65rem;
            background: var(--fl-surface-2);
            border: 1px solid var(--fl-line);
            border-radius: 14px;
            padding: .85rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .flleg__details[hidden] {
            display: none;
        }

        .flseg {
            display: grid;
            grid-template-columns: minmax(150px, .9fr) minmax(0, 2fr) minmax(0, .8fr);
            gap: .85rem;
            align-items: center;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 12px;
            padding: .65rem .85rem;
        }

        .flseg__air {
            display: flex;
            gap: .5rem;
            align-items: center;
            min-width: 0;
        }

        .flseg__logo {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            padding: 2px;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            object-fit: contain;
        }

        .flseg__air-name {
            font-size: .82rem;
            font-weight: 800;
            color: var(--fl-ink);
            line-height: 1.2;
        }

        .flseg__air-meta {
            font-size: .68rem;
            color: var(--fl-slate-2);
            font-family: var(--fl-mono);
        }

        .flseg__route {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(60px, .55fr) minmax(0, 1fr);
            gap: .5rem;
            align-items: center;
        }

        .flseg__pt {
            display: flex;
            flex-direction: column;
        }

        .flseg__pt strong {
            font-family: var(--fl-mono);
            font-size: .98rem;
            color: var(--fl-ink);
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .flseg__pt span {
            font-size: .76rem;
            color: var(--fl-ink-2);
            font-weight: 600;
        }

        .flseg__pt small {
            font-size: .68rem;
            color: var(--fl-muted);
        }

        .flseg__pt--arr {
            text-align: right;
            align-items: flex-end;
        }

        .flseg__dur {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .25rem;
            font-family: var(--fl-mono);
        }

        .flseg__dur span {
            font-size: .72rem;
            font-weight: 700;
            color: var(--fl-slate);
        }

        .flseg__dur small {
            font-size: .62rem;
            color: var(--fl-amber);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .flseg__dur-line {
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--fl-brand) 0, var(--fl-brand) 50%, var(--fl-line) 50%, var(--fl-line));
            position: relative;
        }

        .flseg__meta {
            display: flex;
            gap: .3rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .flseg__tag {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            background: var(--fl-canvas);
            color: var(--fl-slate);
            padding: .15rem .45rem;
            border-radius: 6px;
            font-size: .68rem;
            font-weight: 700;
        }

        .flseg__tag--cab {
            background: var(--fl-blue-soft);
            color: var(--fl-blue);
        }

        .flseg__tag--seat {
            background: var(--fl-amber-soft);
            color: var(--fl-amber);
        }

        .flseg-layover {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin: 0 auto;
            padding: .35rem .75rem;
            border-radius: 999px;
            background: var(--fl-violet-soft);
            color: var(--fl-violet);
            font-size: .75rem;
            font-weight: 700;
            align-self: center;
        }

        .flseg-layover i {
            font-size: .9rem;
        }

        .flseg-layover strong {
            font-family: var(--fl-mono);
            font-weight: 800;
        }

        /* ============================================================
           CARD FOOTER
           ============================================================ */
        .flcard__foot {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(160px, .9fr) minmax(0, auto);
            gap: 1rem;
            align-items: center;
            padding: .85rem 1rem;
            background: linear-gradient(180deg, var(--fl-surface) 30%, var(--fl-surface-2));
            border-top: 1px solid var(--fl-line);
        }

        .flcard__fare {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .flcard__fare-cabin {
            font-family: var(--fl-mono);
            font-weight: 800;
            font-size: .7rem;
            background: var(--fl-ink);
            color: #fff;
            padding: .2rem .5rem;
            border-radius: 6px;
            letter-spacing: .08em;
        }

        .flcard__fare-rbd {
            font-size: .72rem;
            font-weight: 700;
            color: var(--fl-slate);
            background: var(--fl-canvas);
            padding: .18rem .55rem;
            border-radius: 999px;
        }

        .flcard__fare-tag {
            font-size: .6rem;
            font-weight: 800;
            color: var(--fl-violet);
            background: var(--fl-violet-soft);
            padding: .2rem .55rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        .flcard__price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .1rem;
            text-align: right;
        }

        .flcard__price-label {
            font-size: .58rem;
            font-weight: 800;
            color: var(--fl-brand);
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .flcard__price-amount {
            font-family: var(--fl-mono);
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--fl-brand);
            line-height: 1.05;
            letter-spacing: -.02em;
        }

        .flcard__price-currency {
            font-size: .85rem;
            color: var(--fl-slate-2);
            font-weight: 700;
            margin-right: .15rem;
        }

        .flcard__price-seats {
            font-size: .72rem;
            font-weight: 700;
            color: var(--fl-slate);
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-top: .1rem;
        }

        .flcard__price-seats i {
            color: var(--fl-amber);
        }

        .flcard__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .8rem 1.5rem;
            border-radius: 14px;
            background: linear-gradient(180deg, var(--fl-brand) 0%, var(--fl-brand-2) 100%);
            color: #fff !important;
            font-weight: 800;
            font-size: .92rem;
            text-decoration: none !important;
            box-shadow: 0 12px 24px rgba(205, 27, 79, .28);
            white-space: nowrap;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .flcard__cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(205, 27, 79, .38);
        }

        .flcard__cta i {
            font-size: 1.1rem;
        }

        /* ============================================================
           EMPTY STATE
           ============================================================ */
        .fl-empty {
            margin: 3rem auto;
            max-width: 520px;
            text-align: center;
            padding: 2.25rem 1.5rem;
            background: var(--fl-surface);
            border: 1px solid var(--fl-line);
            border-radius: 22px;
            box-shadow: var(--fl-shadow-md);
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
            color: var(--fl-blue);
            background: var(--fl-blue-soft);
        }

        .fl-empty__title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--fl-ink);
            margin: 0 0 .55rem;
        }

        .fl-empty__copy {
            color: var(--fl-slate);
            font-size: .92rem;
            margin: 0 0 1.2rem;
            line-height: 1.55;
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
            text-decoration: none !important;
            border: 1px solid transparent;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease, transform .15s ease;
        }

        .fl-btn--primary {
            background: linear-gradient(180deg, var(--fl-brand), var(--fl-brand-2));
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(205, 27, 79, .28);
        }

        .fl-btn--primary:hover {
            transform: translateY(-1px);
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 991px) {
            .bff-sidebar__inner {
                position: static;
                margin-bottom: 1rem;
            }

            .flleg__timeline {
                grid-template-columns: minmax(0, 1fr) minmax(80px, .9fr) minmax(0, 1fr);
                gap: .55rem;
            }

            .flcard__foot {
                grid-template-columns: minmax(0, 1fr);
                gap: .65rem;
            }

            .flcard__price {
                align-items: flex-start;
                text-align: left;
            }

            .flcard__cta {
                justify-self: stretch;
            }
        }

        @media (max-width: 640px) {
            .flleg__head {
                flex-direction: column;
                align-items: flex-start;
            }

            .flleg__timeline {
                grid-template-columns: minmax(0, 1fr);
            }

            .flleg__bridge {
                flex-direction: row;
                justify-content: center;
            }

            .flleg__bridge-line {
                flex: 1;
                margin: 0 .5rem;
            }

            .flleg__point--arr {
                text-align: left;
                align-items: flex-start;
            }

            .flseg {
                grid-template-columns: minmax(0, 1fr);
            }

            .flseg__meta {
                justify-content: flex-start;
            }

            .flseg__route {
                grid-template-columns: minmax(0, 1fr) minmax(50px, .5fr) minmax(0, 1fr);
            }

            .fl-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .fl-toolbar__sort {
                justify-content: space-between;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        window.BFF_CONFIG = @json([
            'trip' => $tripType,
            'price' => $priceRange,
        ]);

        (function () {
            const ROOT = document.getElementById('bff-results');
            if (!ROOT) return;

            const cards = () => [...ROOT.querySelectorAll('.bff-card')];
            const trip = window.BFF_CONFIG.trip || 'one_way';
            const PRICE_MAX_DEFAULT = Number(window.BFF_CONFIG.price?.max ?? 999999);

            const tabButtons = [...document.querySelectorAll('[data-bff-tab-target]')];
            const retPanels = [...document.querySelectorAll('.bff-leg-panel[data-bff-leg="ret"], .bff-leg-only-ret')];

            function setActiveTab(which) {

                tabButtons.forEach(btn => {
                    btn.classList.toggle('is-active', btn.dataset.bffTabTarget === which);
                });

                const showRet = which === 'leg-ret';

                retPanels.forEach(el => el.classList.toggle('is-visible-panel', showRet));

            }

            tabButtons.forEach(btn => btn.addEventListener('click', () =>
                setActiveTab(btn.dataset.bffTabTarget)));

            if (trip === 'round_trip') {
                setActiveTab('leg-out');
            }

            const priceCapEl = document.getElementById('bff-price-cap');
            const priceLabel = document.getElementById('bff-price-cap-label');

            priceCapEl?.addEventListener('input', () => {
                if (priceLabel) priceLabel.textContent = priceCapEl.value;
                applyFilters();
            });

            const filterRoot = document.getElementById('bff-advanced-filters');

            filterRoot?.addEventListener('change', () => applyFilters());

            document.getElementById('bff-reset')?.addEventListener('click', () => {
                filterRoot?.querySelectorAll('input[type=checkbox]').forEach(i => {

                    i.checked = true;

                });

                if (priceCapEl) {
                    priceCapEl.value = String(Math.ceil(PRICE_MAX_DEFAULT));
                    if (priceLabel) priceLabel.textContent = priceCapEl.value;
                }

                applyFilters();

            });

            document.getElementById('bff-sort')?.addEventListener('change', () => reorder());

            /* Sort pills bound to the hidden <select id="bff-sort"> */
            const sortPills = [...document.querySelectorAll('.fl-sort-pill[data-bff-sort]')];
            const sortSelect = document.getElementById('bff-sort');

            sortPills.forEach(pill => {
                pill.addEventListener('click', () => {
                    let target = pill.dataset.bffSort;

                    /* Price pill toggles asc <-> desc on repeat click */
                    if (pill.dataset.bffCycle && pill.classList.contains('is-active')) {
                        const cycle = pill.dataset.bffCycle.split(',');
                        const idx = cycle.indexOf(target);
                        target = cycle[(idx + 1) % cycle.length];
                        pill.dataset.bffSort = target;
                        pill.classList.toggle('is-desc', target.endsWith('-desc'));
                    } else {
                        pill.classList.toggle('is-desc', target.endsWith('-desc'));
                    }

                    sortPills.forEach(p => p.classList.toggle('is-active', p === pill));

                    if (sortSelect) {
                        sortSelect.value = target;
                        sortSelect.dispatchEvent(new Event('change'));
                    }
                });
            });

            /* "Flight Details" expandable per-leg panels */
            document.querySelectorAll('[data-fl-toggle]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const main = btn.closest('.flleg__main');
                    if (!main) return;
                    const panel = main.querySelector('[data-fl-details]');
                    if (!panel) return;
                    const isOpen = btn.classList.toggle('is-open');
                    panel.hidden = !isOpen;
                    const label = btn.querySelector('.flleg__toggle-label');
                    if (label) label.textContent = isOpen ? 'Hide Details' : 'Flight Details';
                });
            });

            function readCheckedValues(selector, attrVal = '') {
                const out = [];

                [...filterRoot.querySelectorAll(selector)].filter(i => i.checked)

                    .forEach(i => {
                        const v =
                            attrVal ? i.dataset[attrVal] : i.value;
                        out.push(String(v ?? ''));

                    });

                return out;

            }

            function parseMeta(card) {

                try {
                    return JSON.parse(card.dataset.bffMeta || '{}');
                }

                catch (e) {

                    return {};

                }

            }

            function matches(meta) {

                const cap = Number(priceCapEl?.value || PRICE_MAX_DEFAULT);

                if (Number(meta.price || 0) > cap + 1e-9) {
                    return false;
                }

                const stopsOBoxes = [...filterRoot.querySelectorAll('.bff-inp-stops-o')];
                const stOChosen = stopsOBoxes.filter(i => i.checked).map(i => Number(i.value));
                if (stopsOBoxes.length) {
                    if (!stOChosen.length) return false;
                    if (!stOChosen.includes(Number(meta.st_o))) return false;
                }

                if (trip === 'round_trip') {
                    const stopsRBoxes = [...filterRoot.querySelectorAll('.bff-inp-stops-r')];
                    const stopsRChosen = stopsRBoxes.filter(i => i.checked).map(i => Number(i.value));
                    if (stopsRBoxes.length && meta.st_r != null) {
                        if (!stopsRChosen.length) return false;
                        if (!stopsRChosen.includes(Number(meta.st_r))) return false;
                    }
                }

                /* departure time buckets onward */
                const depOBuds = [...filterRoot.querySelectorAll('.bff-inp-dep-o')];
                const depOBucketsChosen = depOBuds.filter(i => i.checked).map(i => Number(i.dataset.bucket));
                if (depOBuds.length && !depOBucketsChosen.length) return false;
                if (meta.dba_o && meta.dba_o.length &&
                    !meta.dba_o.some(b => depOBucketsChosen.includes(Number(b)))) return false;

                if (trip === 'round_trip') {
                    const depRBuds = [...filterRoot.querySelectorAll('.bff-inp-dep-r')];
                    const depRBucketsChosen = depRBuds.filter(i => i.checked).map(i => Number(i.dataset.bucket));
                    if (depRBuds.length && !depRBucketsChosen.length) return false;
                    if (
                        meta.dba_r &&
                        meta.dba_r.length &&
                        !meta.dba_r.some(b => depRBucketsChosen.includes(Number(b)))
                    )
                        return false;
                }

                /* arrival buckets onward */
                const arrOBuds = [...filterRoot.querySelectorAll('.bff-inp-arr-o')];
                const arrOBucketsChosen = arrOBuds.filter(i => i.checked).map(i => Number(i.dataset.bucket));
                if (arrOBuds.length && !arrOBucketsChosen.length) return false;
                if (meta.aba_o && meta.aba_o.length &&
                    !meta.aba_o.some(b => arrOBucketsChosen.includes(Number(b)))) return false;

                if (trip === 'round_trip') {
                    const arrRBuds = [...filterRoot.querySelectorAll('.bff-inp-arr-r')];
                    const arrRBucketsChosen = arrRBuds.filter(i => i.checked).map(i => Number(i.dataset.bucket));
                    if (arrRBuds.length && !arrRBucketsChosen.length) return false;
                    if (
                        meta.aba_r &&
                        meta.aba_r.length &&
                        !meta.aba_r.some(b => arrRBucketsChosen.includes(Number(b)))
                    )
                        return false;
                }

                const alBoxes = [...filterRoot.querySelectorAll('.bff-inp-al')];
                if (alBoxes.length) {
                    const alChosen = alBoxes.filter(i => i.checked).map(i => i.value);
                    if (!alChosen.length) return false;
                    if (meta.al &&
                        meta.al.length &&
                        !meta.al.some(c => alChosen.includes(c)))
                        return false;
                }

                const depAptBoxesO = [...filterRoot.querySelectorAll('.bff-inp-depa-o')];
                if (depAptBoxesO.length) {
                    const chosen = depAptBoxesO.filter(i => i.checked).map(i => i.value);
                    if (!chosen.length) return false;
                    if (meta.dep_o &&
                        meta.dep_o.length &&
                        !meta.dep_o.some(a => chosen.includes(a)))
                        return false;
                }

                if (trip === 'round_trip') {
                    const depAptBoxesR = [...filterRoot.querySelectorAll('.bff-inp-depa-r')];
                    if (depAptBoxesR.length) {
                        const chosenR = depAptBoxesR.filter(i => i.checked).map(i => i.value);
                        if (!chosenR.length) return false;
                        if (meta.dep_r &&
                            meta.dep_r.length &&
                            !meta.dep_r.some(a => chosenR.includes(a)))
                            return false;
                    }
                }

                /* Connecting hubs: unchecked all ⇒ only non-stop itineraries pass */
                const connBoxesO = [...filterRoot.querySelectorAll('.bff-inp-conn-o')];
                if (connBoxesO.length && meta.conn_o && meta.conn_o.length) {
                    const connChosenO = connBoxesO.filter(i => i.checked).map(i => i.value);
                    if (!connChosenO.length) return false;
                    if (!meta.conn_o.every(mid => connChosenO.includes(mid))) return false;
                }

                if (trip === 'round_trip') {
                    const connBoxesR = [...filterRoot.querySelectorAll('.bff-inp-conn-r')];
                    if (connBoxesR.length && meta.conn_r && meta.conn_r.length) {
                        const connChosenR = connBoxesR.filter(i => i.checked).map(i => i.value);
                        if (!connChosenR.length) return false;
                        if (!meta.conn_r.every(mid => connChosenR.includes(mid))) return false;
                    }
                }

                const fareBoxes = [...filterRoot.querySelectorAll('.bff-inp-fare')];
                const fareChosen = fareBoxes.filter(i => i.checked).map(i => i.value);
                if (fareBoxes.length && !fareChosen.length) return false;
                if (
                    fareChosen.length &&
                    meta.fare &&
                    meta.fare.length &&
                    !meta.fare.some(tag => fareChosen.includes(String(tag)))
                )
                    return false;

                return true;

            }

            function reorder() {

                const mode =
                    document.getElementById('bff-sort').value || 'price-asc';

                const els = [...ROOT.children];

                els.sort((a, b) => {

                    const pa = parseMeta(a),

                        pb =
                        parseMeta(b);

                    switch (mode) {

                        case 'price-desc':
                            return (pb.price || 0) - (pa.price || 0);

                        case 'duration-o-asc':
                            return (Number(a.dataset.durFirst || 9e9) - Number(b.dataset.durFirst || 9e9));

                        case 'departure-o':
                            return String(a.dataset.firstDepISO || '').localeCompare(
                                String(b.dataset.firstDepISO || ''));

                        case 'price-asc': default: return (pa.price ||
                            0) - (pb.price || 0);

                    }

                });

                els.forEach(el => ROOT.appendChild(el));

            }

            function applyFilters() {

                let visible = 0;

                cards().forEach(card => {

                    const ok = matches(parseMeta(card));

                    card.classList.toggle('bff-hide', !ok);

                    if (
                        ok) visible++;

                });

                const tag = document.getElementById('bff-showing-meta');

                if (tag) tag.textContent = String(visible);

                reorder();

            }

            /* enrich cards with duration & first iso for sorting */

            cards().forEach(card => {
                const meta = parseMeta(card);
                card.dataset.durFirst = meta.dur_o != null ? String(Number(meta.dur_o)) : '999999';
                card.dataset.firstDepISO = meta.first_dep_iso || '';
            });

            applyFilters();

        })();
    </script>
@endpush
