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
                <div class="bff-toolbar mb-4">
                    <div class="bff-toolbar__stats">
                        <span class="bff-count"><strong>{{ $itineraryCount }}</strong> fares found</span>
                        <span class="bff-showing" id="bff-showing-meta">Filtering results…</span>
                    </div>
                    <div class="bff-toolbar__sort">
                        <label class="bff-sort-label" for="bff-sort">Sort by</label>
                        <select class="bff-select" id="bff-sort">
                            <option value="price-asc" selected>Price — low to high</option>
                            <option value="price-desc">Price — high to low</option>
                            <option value="duration-o-asc">Outward duration</option>
                            <option value="departure-o">Outward departure</option>
                        </select>
                    </div>
                </div>
            @else
                <div class="bff-toolbar mb-4">
                    <div class="bff-toolbar__stats">
                        <span class="bff-count"><strong>{{ $itineraryCount ?? 0 }}</strong> fares found</span>
                    </div>
                </div>
            @endif

            @if (($itineraryCount ?? 0) > 0)
                <div class="bff-layout row g-4">
                    <aside class="bff-sidebar col-xl-3 col-lg-4 col-md-12" id="bff-filters-sidebar">
                        <div class="bff-sidebar__inner">
                            <div class="bff-sidebar-title">Filter your search</div>

                            @if ($roundTripTabs)
                                <div class="bff-leg-tabs">
                                    <button type="button" class="bff-tab is-active"
                                        data-bff-tab-target="leg-out">Outbound</button>
                                    <button type="button" class="bff-tab" data-bff-tab-target="leg-ret">Return</button>
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
                                        foreach (($lg['segments'] ?? []) as $seg) {
                                            if (isset($seg['seats_available'])) {
                                                $seatsListed[] = (int) $seg['seats_available'];
                                            }
                                        }
                                    }
                                    $seatMin = !empty($seatsListed)
                                        ? min($seatsListed)
                                        : null;
                                @endphp
                                <article class="bff-card" id="bff-card-{{ $lid }}"
                                    data-bff-meta='@json($meta)' data-bff-id="{{ $lid }}">
                                    <div class="bff-card__ribbon">
                                        <div class="bff-card__ribbon-left">
                                            @if ($result['validating_carrier'])
                                                <span class="bff-pill bff-pill--vc">
                                                    VC {{ $result['validating_carrier'] }}</span>
                                            @endif

                                            @if (!empty($result['pricing_subsource']))
                                                <span class="bff-pill">{{ $result['pricing_subsource'] }}</span>
                                            @endif

                                            @if (($result['non_refundable'] ?? false))
                                                <span class="bff-pill bff-pill--warn">Non‑refundable</span>
                                            @endif
                                        </div>
                                        @if (($result['baggage_notes'] ?? null))
                                            <span class="bff-bag">{{ $result['baggage_notes'] }}</span>
                                        @endif
                                    </div>

                                    @foreach (($result['legs'] ?? []) as $legIndex => $leg)
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

                                            $stopsPhrase =
                                                $tierConn + $tierStop === 0
                                                ? 'Non‑stop'
                                                : ($tierConn + $tierStop === 1
                                                    ? '1 stop'
                                                    : $tierConn + $tierStop . ' stops');

                                            $legBadge =
                                                $legIndex === 0
                                                ? ($roundTripTabs
                                                    ? 'Outbound'
                                                    : 'Flight')
                                                : 'Return';

                                        @endphp

                                        @if (!$loop->first)
                                            <div class="bff-leg-divider"></div>
                                        @endif

                                        <div class="bff-leg-grid">
                                            <div class="bff-airline-zone">
                                                <img class="bff-logo"
                                                    src="https://pics.avs.io/112/112/{{ strtoupper(trim((string) ($seg0['carrier'] ?? 'XX'))) }}.png"
                                                    loading="lazy" alt=""
                                                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(trim((string) ($seg0['carrier'] ?? 'FL'))) }}&background=cd1b4f&color=fff'" />
                                                <div>
                                                    <div class="bff-airline">{{ $seg0['carrier_display'] ?? ($seg0['carrier'] ?? '') }}
                                                    </div>
                                                    @if (($seg0['operating_carrier'] ?? '') &&
                                                            ($seg0['operating_carrier'] ?? '') !== ($seg0['carrier'] ?? ''))
                                                        <div class="bff-op-note">
                                                            Op {{ $seg0['operating_carrier'] }}
                                                            @if (!empty($seg0['operating_flight_number']))
                                                                {{ $seg0['operating_flight_number'] }}
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if (!empty($result['supplier']))
                                                        <span class="bff-supplier">Desk:
                                                            {{ strtoupper((string) $result['supplier']) }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="bff-points">
                                                <div class="bff-point">
                                                    @if (($seg0['is_red_eye_segment'] ?? false))
                                                        <i class='bx bxs-moon bff-night' title="Night departure"></i>
                                                    @endif
                                                    <strong>{{ $seg0['departure_clock'] ?? '-' }}</strong>
                                                    <span class="dff-date">{{ $seg0['departure_weekday'] ?? '' }}, {{ $seg0['departure_label'] ?? '' }}</span>
                                                    <span class="bff-city">{{ bff_city_line($seg0, $seg0['from'] ?? '') }}</span>
                                                    <small class="bff-iata">{{ $seg0['from'] ?? '' }}@if (!empty($seg0['departure_terminal']))
                                                            · {{ $seg0['departure_terminal'] }}
                                                        @endif</small>
                                                </div>

                                                <div class="bff-journey-bridge">
                                                    <span class="bff-trip-time">{{ bff_format_hm(isset($leg['elapsedTime'])
                                                        ? (int) $leg['elapsedTime']
                                                        : null) }}</span>

                                                    <div class="bff-line"></div>

                                                    <span class="bff-st-chip">{{ $stopsPhrase }}</span>
                                                </div>

                                                <div class="bff-point text-end align-items-end">
                                                    <strong>{{ $lastSeg['arrival_clock'] ?? '-' }}</strong>
                                                    <span class="dff-date">{{ $lastSeg['arrival_weekday'] ?? '' }}, {{ $lastSeg['arrival_label'] ?? '' }}</span>
                                                    <span class="bff-city">{{ bff_city_line($lastSeg, $lastSeg['to'] ?? '') }}</span>

                                                    @if (($lastSeg['next_day_hint'] ?? false))
                                                        <span class="bff-next-day">Next day</span>
                                                    @endif

                                                    <small class="bff-iata">{{ $lastSeg['to'] ?? '' }}@if (!empty($lastSeg['arrival_terminal']))
                                                            · {{ $lastSeg['arrival_terminal'] }}
                                                        @endif</small>
                                                </div>
                                            </div>

                                            @if (($leg['segments'] ?? []) &&
                                                    count(($leg['segments'] ?? []) ) > 1)
                                                <div class="bff-conn-pane">
                                                    <div class="bff-leg-badge">{{ $legBadge }}</div>
                                                    <div class="bff-segments">
                                                        @foreach ($leg['segments'] as $sx)
                                                            <span class="bff-segment-chip">{{ $sx['from'] ?? '' }}
                                                                → {{ $sx['to'] ?? '' }}
                                                                {{ $sx['carrier'] ?? '' }}{{ $sx['flight_number'] ?? '' }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <div class="bff-leg-mini">
                                                    <div class="bff-leg-badge">{{ $legBadge }}</div>
                                                    <span>{{ $seg0['flight_label'] ?? '' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    <!-- Fare footprint -->
                                    <div class="bff-fare-pane">
                                        <div class="bff-fare-lines">
                                            <div class="bff-fare-line">
                                                @php $segA = (($result['legs'][0]['segments'][0]) ?? []); @endphp
                                                @if ($segA)
                                                    <span class="bff-fw">Outbound</span>
                                                    <span class="bff-cabin-chip">{{ $segA['cabin_code'] ?? 'Y' }}</span>
                                                    @if (($segA['booking_code'] ?? null))
                                                        <span class="bff-rbd">Class {{ $segA['booking_code'] }}</span>
                                                    @endif
                                                @else
                                                    <span>Fare footprint</span>
                                                @endif
                                            </div>

                                            @if (
                                                (($result['fare_tags'] ?? []) &&
                                                    count($result['fare_tags'])) )
                                                <div class="bff-tags">
                                                    @foreach (($result['fare_tags'] ?? []) as $ft)
                                                        <span>{{ strtoupper((string) $ft) }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <div class="bff-action-col">
                                            <div class="bff-price-band">
                                                <span class="bff-net">NET</span>
                                                <strong class="bff-price-num">
                                                    <span class="dirham"></span>{{ number_format($result['totalPrice'] ?? 0, 2) }}
                                                </strong>
                                                <span class="bff-curr">{{ strtoupper(($result['currency'] ?? '')) }}</span>

                                                @if ($seatMin !== null)
                                                    <div class="bff-seat-mini"><i class='bx bx-user'></i>{{ $seatMin }} seats
                                                    </div>
                                                @endif
                                            </div>

                                            <a href="{{ route('user.flights.checkout', ['itinerary' => $lid] + $query) }}"
                                                class="bff-book">Continue</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif (!empty(request()->except(['page'])) && empty(($results ?? [])))
                <!-- Empty State -->
                <div class="fc-empty-state mt-5">
                    <div class="fc-empty-icon"><i class='bx bx-search-alt'></i></div>
                    <h3>No flights found</h3>
                    <p>We couldn't find itineraries for this search matrix. Relax filters once results load, or widen
                        dates/airports.</p>
                    <div class="fc-empty-actions">
                        <a href="{{ route('user.flights.index') }}" class="fc-btn-outline">Modify Search</a>
                    </div>
                </div>
            @else
                <div class="fc-empty-state mt-5">
                    <div class="fc-empty-icon"><i class='bx bx-search-alt'></i></div>

                    <h3>Ready when you are</h3>

                    <p>Select cities and dates above, then we will query Sabre Bargain Finder for live availability.</p>
                    <div class="fc-empty-actions">

                        <a href="{{ route('user.flights.index') }}" class="fc-btn-outline">Open search</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('css')
    <style>
        :root {
            --bff-ink: #0f2744;
            --bff-muted: #5c6f85;
            --bff-line: #e4e9f5;
            --bff-navy: #10345f;
            --bff-lav: #eef1ff;
            --bff-accent: #cd1b4f;
            --bff-accent-soft: rgba(205, 27, 79, .14);
            --bff-lime-note: #0f9d74;
            --bff-surface: #f6f8fd;
            --bff-shadow: 0 16px 40px rgba(17, 40, 80, .08)
        }

        .bff-page {
            padding-bottom: 3rem;
        }

        .bff-toolbar {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(120deg, #fff, #f9fbff 45%, #f3f7ff);
            border: 1px solid var(--bff-line);
            border-radius: 18px;

            padding: .65rem .95rem .65rem 1rem;
            box-shadow: var(--bff-shadow)
        }

        .bff-toolbar__stats {
            display: flex;

            gap: .75rem;
            align-items: baseline;
            flex-wrap: wrap
        }

        .bff-count strong {
            color: var(--bff-navy);
            font-weight: 800;
            letter-spacing: -.015em;

            font-variant-numeric: tabular-nums
        }

        .bff-sort-label {

            margin-right: .45rem;

            font-size: .73rem;

            font-weight: 700;

            color: var(--bff-muted);

            text-transform: uppercase;

            letter-spacing: .06em
        }

        .bff-select {
            border: 1px solid var(--bff-line);

            border-radius: 12px;

            padding: .4rem .6rem;

            font-size: .85rem;

            background: #fff;

            color: var(--bff-navy)

        }

        .bff-sidebar__inner {

            border-radius: 18px;

            border: 1px solid var(--bff-line);

            background: repeating-linear-gradient(135deg,

                    #fdfefe 0 12px,

                    #f8faff 12px 24px);

            box-shadow: var(--bff-shadow);

            padding: 1rem .95rem;

            position: sticky;

            top: 1rem
        }

        .bff-sidebar-title {

            font-size: .95rem;

            font-weight: 800;

            color: var(--bff-navy);

            margin-bottom: .75rem
        }

        .bff-leg-tabs {

            display: flex;

            gap: .35rem;

            margin-bottom: .85rem;

            flex-wrap: wrap;

        }

        .bff-tab {
            flex: 1;
            padding: .45rem .55rem;

            border-radius: 999px;

            border: none;

            background: rgba(255, 255, 255, .9);

            color: var(--bff-muted);

            font-weight: 700;

            font-size: .74rem;

            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(16, 52, 95, .12)

        }

        .bff-tab.is-active {
            background: var(--bff-navy);
            color: #fff;

            box-shadow: 0 6px 16px rgba(16, 52, 95, .25)

        }

        details.bff-acc {
            margin: .5rem 0;
            padding: .5rem .25rem .75rem;

            border-top: 1px solid rgba(16, 52, 95, .08)

        }

        summary {
            cursor: pointer;
            list-style: none;
            font-size: .7rem;

            letter-spacing: .16em;

            font-weight: 800;

            text-transform: uppercase;

            display: flex;
            justify-content: space-between;

            align-items: center;

            color: var(--bff-muted)
        }

        summary::-webkit-details-marker {
            display: none
        }

        .bff-acc-body {
            margin-top: .55rem;
            display: flex;
            flex-direction: column;
            gap: .35rem
        }

        .bff-chip-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .35rem
        }

        .bff-chip-option {

            position: relative

        }

        .bff-chip-option input {
            position: absolute;
            opacity: 0
        }

        .bff-chip-option span {
            display: block;
            text-align: center;
            padding: .45rem .25rem;
            border-radius: 12px;
            background: #fff;
            font-size: .72rem;
            font-weight: 700;
            color: var(--bff-navy);
            box-shadow: inset 0 0 0 1px var(--bff-line)
        }

        .bff-chip-option input:checked+span {
            background: var(--bff-accent-soft);
            color: var(--bff-accent);
            box-shadow: inset 0 0 0 1px rgba(205, 27, 79, .45)
        }

        .bff-check {
            display: flex;
            gap: .45rem;
            align-items: center;
            font-size: .8rem;
            color: var(--bff-ink)
        }

        .bff-check input {
            width: 16px;
            height: 16px;
            accent-color: var(--bff-accent)
        }

        .bff-scroll {
            max-height: 160px;
            overflow: auto;
            padding-right: .25rem
        }

        .bff-range {
            width: 100%;
            accent-color: var(--bff-navy)
        }

        .bff-range-labels {
            display: flex;

            justify-content: space-between;
            margin-top: .35rem;

            font-size: .73rem;

            color: var(--bff-muted)

        }

        .bff-reset-btn {

            width: 100%;
            border-radius: 12px;

            border: dashed 1px rgba(205, 27, 79, .4);

            background: transparent;
            padding: .5rem .75rem;
            margin-top: .85rem;

            font-weight: 700;

            color: var(--bff-accent)

        }

        .bff-micro-label {

            font-size: .72rem;

            font-weight: 700;

            color: var(--bff-accent);

            margin: .55rem 0 .25rem
        }

        .bff-leg-panel--hidden[data-bff-leg="ret"]:not(.is-visible-panel),
        .bff-leg-only-ret:not(.is-visible-panel) {
            display: none !important;
        }

        .bff-muted {
            color: var(--bff-muted)

        }

        .bff-alert {
            padding: .55rem .75rem;
            border-radius: 14px;

            font-size: .82rem;

            font-weight: 600;

            border: 1px solid transparent;
            margin-bottom: .45rem;

        }

        .bff-alert--err {
            border-color: #fecaca;
            background: #fff1f2;
            color: #9f1239
        }

        .bff-alert--info {
            border-color: rgba(103, 160, 255, .55);
            background: #eaf2ff;
            color: #1f3ea3
        }

        .bff-hint {

            font-size: .71rem;

            color: var(--bff-muted)

        }

        .bff-card {
            margin-bottom: 1.05rem;

            border-radius: 22px;

            border: 1px solid rgba(16, 52, 95, .08);

            background: radial-gradient(circle at 20% -10%, rgba(205, 27, 79, .14), transparent 52%), #ffffff;

            box-shadow: var(--bff-shadow)

        }

        .bff-card__ribbon {
            display: flex;
            gap: .5rem;

            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: .55rem 1rem 0;

        }

        .bff-card__ribbon-left {
            display: flex;

            gap: .35rem;

            flex-wrap: wrap;

            align-items: center;

        }

        .bff-pill {
            padding: .15rem .55rem;
            border-radius: 999px;
            font-size: .62rem;

            font-weight: 800;

            letter-spacing: .05em;

            text-transform: uppercase;

            background: var(--bff-lav);
            color: var(--bff-navy)
        }

        .bff-pill--vc {

            background: rgba(16, 52, 95, .12);

            color: var(--bff-navy)
        }

        .bff-pill--warn {

            background: rgba(245, 158, 11, .15);

            color: #a16207
        }

        .bff-bag {
            font-size: .73rem;

            font-weight: 700;

            color: var(--bff-lime-note)
        }

        .bff-leg-divider {

            height: 1px;

            background: linear-gradient(90deg, transparent 0%, var(--bff-line), transparent);

            margin: .45rem .6rem .15rem;

        }

        .bff-leg-grid {
            padding: .75rem .95rem 1rem;

            display: grid;
            grid-template-columns: minmax(0, .85fr) minmax(0, 2fr) minmax(0, .65fr);

            gap: .75rem;
            align-items: start;

        }

        .bff-logo {
            width: 44px;

            height: 44px;

            border-radius: 12px;
            padding: .25rem;

            background: white;

            object-fit: contain;

            box-shadow: 0 10px 20px rgba(15, 39, 68, .08)

        }

        .bff-airline-zone {
            display: flex;

            gap: .6rem;

            align-items: center;

            min-height: 100%

        }

        .bff-airline {
            font-weight: 900;

            color: var(--bff-navy)
        }

        .bff-points {
            display: grid;

            grid-template-columns: minmax(0, 1fr) minmax(0, .82fr) minmax(0, 1fr);
            gap: .45rem;

            align-items: center;

        }

        .bff-point {
            display: flex;
            flex-direction: column;

            gap: .1rem;

        }

        .bff-point strong {

            font-size: 1.08rem;

            font-weight: 900;

            color: var(--bff-navy);

            letter-spacing: -.02em
        }

        .dff-date {
            font-size: .73rem;

            font-weight: 600;

            color: var(--bff-muted)

        }

        .bff-city {
            font-size: .9rem;

            font-weight: 700;

            color: #10223c
        }

        .bff-iata {
            font-size: .71rem;

            color: var(--bff-muted)

        }

        .bff-journey-bridge {
            padding: .15rem .2rem;

            text-align: center;

            position: relative
        }

        .bff-trip-time {
            font-size: .73rem;

            font-weight: 800;

            color: var(--bff-muted);

            letter-spacing: .02em;

            display: block;

            margin-bottom: .18rem;

        }

        .bff-line {
            border-top: 2px dashed rgba(16, 52, 95, .18);

            position: relative
        }

        .bff-line::before,
        .bff-line::after {
            content: '';
            width: 7px;

            height: 7px;

            background: rgba(205, 27, 79, .6);

            border-radius: 50%;

            position: absolute;

            top: -4px;

        }

        .bff-line::before {
            left: 0;

        }

        .bff-line::after {
            right: 0
        }

        .bff-st-chip {
            margin-top: .42rem;

            display: inline-block;

            padding: .12rem .38rem;

            border-radius: 999px;

            font-size: .63rem;

            font-weight: 800;

            letter-spacing: .08em;

            text-transform: uppercase;

            background: var(--bff-accent-soft);
            color: var(--bff-accent)

        }

        .bff-next-day {

            font-size: .68rem;

            font-weight: 800;

            color: #c2410c;

            letter-spacing: .02em;

            text-transform: uppercase

        }

        .bff-op-note {
            font-size: .71rem;

            color: var(--bff-muted)

        }

        .bff-supplier {
            margin-top: .15rem;

            display: inline-block;

            padding: .12rem .4rem;

            border-radius: 999px;

            font-size: .58rem;

            font-weight: 800;

            letter-spacing: .08em;

            text-transform: uppercase;

            background: rgba(205, 27, 79, .1);

            color: var(--bff-accent)

        }

        .bff-night {
            margin-right: .15rem;

            color: var(--bff-navy)
        }

        .bff-conn-pane {
            grid-column: 1 / -1;

            padding: .5rem .6rem .15rem .6rem;

        }

        .bff-leg-mini {
            grid-column: 3 / span 1;

            font-size: .78rem;

            font-weight: 700;

            color: var(--bff-muted)

        }

        .bff-leg-badge {
            display: inline-flex;

            margin-bottom: .25rem;

            padding: .1rem .4rem;

            border-radius: 8px;

            font-size: .58rem;

            font-weight: 900;

            letter-spacing: .1em;

            text-transform: uppercase;

            background: rgba(205, 27, 79, .09);

            color: var(--bff-accent)

        }

        .bff-segments {
            display: flex;

            flex-wrap: wrap;

            gap: .35rem;

        }

        .bff-segment-chip {
            background: rgba(16, 52, 95, .05);

            color: var(--bff-navy);
            padding: .25rem .45rem;

            border-radius: 10px;

            font-size: .68rem;

            font-weight: 700;

        }

        .bff-fare-pane {
            display: flex;

            flex-wrap: wrap;

            gap: .75rem;
            justify-content: space-between;
            align-items: center;

            padding: .75rem 1rem .95rem;

            border-top: 1px dashed rgba(16, 52, 95, .12)

        }

        .bff-fare-line {
            display: flex;

            gap: .4rem;

            align-items: center;

            flex-wrap: wrap;

            font-weight: 700;

            color: var(--bff-muted);

            font-size: .78rem

        }

        .bff-fw {

            letter-spacing: .04em;

            text-transform: uppercase;

            font-size: .72rem;

        }

        .bff-cabin-chip {
            background: rgba(16, 52, 95, .1);

            color: var(--bff-navy);
            padding: .08rem .45rem;

            border-radius: 8px;

            font-size: .7rem;

        }

        .bff-rbd {

            font-size: .7rem;

            color: var(--bff-muted)

        }

        .bff-tags {
            display: flex;

            gap: .35rem;

            flex-wrap: wrap;

            margin-top: .35rem;

        }

        .bff-tags span {

            font-size: .65rem;

            font-weight: 800;

            letter-spacing: .08em;

            text-transform: uppercase;

            padding: .12rem .4rem;

            border-radius: 999px;

            background: rgba(16, 52, 95, .08)

        }

        .bff-price-band {
            text-align: right;

            min-width: 130px;

        }

        .bff-price-num {

            font-size: 1.52rem;

            font-weight: 900;

            color: var(--bff-accent)

        }

        .bff-net {

            letter-spacing: .18em;

            font-size: .58rem;

            font-weight: 800;

            color: var(--bff-accent)

        }

        .bff-seat-mini {
            margin-top: .15rem;

            font-size: .72rem;

            color: var(--bff-muted)

        }

        .bff-book {
            align-self: center;

            padding: .65rem 1.15rem;

            border-radius: 14px;

            background: linear-gradient(180deg,
                    #fb7aa5 0%,
                    var(--bff-accent));

            color: #fff !important;
            font-weight: 900;

            text-decoration: none !important;

            box-shadow:
                0 12px 30px rgba(205, 27, 79, .32);

            white-space: nowrap;

        }

        .bff-card.bff-hide {
            display: none !important;

        }

        @media (max-width: 991px) {
            .bff-leg-grid {

                grid-template-columns: minmax(0, 1fr);

            }

            .bff-points {

                grid-template-columns: minmax(0, 1fr);

            }

            .bff-leg-mini {

                grid-column: 1;

            }

            .bff-fare-pane {
                align-items: flex-start;

            }

            .bff-sidebar__inner {
                position: static;

                margin-bottom: 1rem

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

                if (tag) tag.textContent = `${visible} itinerary${visible === 1 ? '' : 'ies'} match filters`;

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
