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
            if ($mins === null || $mins < 1) return '—';
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
            return "https://pics.avs.io/80/80/{$c}.png";
        }

        function fl_city_label(array $seg, bool $isDep = true): string
        {
            $k = $isDep ? 'departure_city' : 'arrival_city';
            $c = trim((string) ($seg[$k] ?? ''));
            if ($c !== '') return $c;
            $c2 = trim((string) ($isDep ? ($seg['from'] ?? '') : ($seg['to'] ?? '')));
            return $c2;
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
                            $lid      = $result['id'];
                            $meta     = $result['listing_meta'] ?? [];
                            $legs     = $result['legs'] ?? [];

                            $firstSeg = $legs[0]['segments'][0] ?? [];

                            $seatList = [];
                            foreach ($legs as $lg) {
                                foreach ($lg['segments'] ?? [] as $sx) {
                                    if (isset($sx['seats_available'])) $seatList[] = (int) $sx['seats_available'];
                                }
                            }
                            $seatMin   = !empty($seatList) ? min($seatList) : null;
                            $nonRefund = (bool) ($result['non_refundable'] ?? false);
                            $bagNote   = $result['baggage_notes'] ?? '';
                            $totalPrice = $result['totalPrice'] ?? 0;
                            $cardCur   = strtoupper((string) ($result['currency'] ?? $currencyCode));
                            $cabinTop  = $firstSeg['cabin_code'] ?? 'Y';
                            $rbdTop    = $firstSeg['booking_code'] ?? '';
                        @endphp

                        <div class="rc" data-rp-meta='@json($meta)'>

                            {{-- ── per-leg rows ── --}}
                            @foreach ($legs as $li => $leg)
                                @php
                                    $segs  = $leg['segments'] ?? [];
                                    $s0    = $segs[0] ?? [];
                                    $sLast = [];
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

                                    // build via-chip labels (connection airports)
                                    $midApts = [];
                                    for ($mi = 0; $mi < count($segs) - 1; $mi++) {
                                        $midApts[] = $segs[$mi]['to'] ?? '';
                                    }
                                @endphp

                                <div class="rc__leg {{ $li === 0 ? 'rc__leg--first' : 'rc__leg--ret' }}">

                                    {{-- airline --}}
                                    <div class="rc__airline">
                                        <img class="rc__logo"
                                            src="{{ fl_carrier_logo($s0['carrier'] ?? '') }}"
                                            loading="lazy" alt=""
                                            onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($s0['carrier']??'FL') }}&background=cd1b4f&color=fff&size=80'">
                                        <div>
                                            <div class="rc__aname">{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}</div>
                                            <div class="rc__aflight">{{ strtoupper((string)($s0['carrier']??'')) }}{{ $s0['flight_number']??'' }}</div>
                                        </div>
                                    </div>

                                    {{-- departure --}}
                                    <div class="rc__point">
                                        <div class="rc__time">
                                            {{ $s0['departure_clock'] ?? '—' }}
                                            @if ($isRedEye)<i class="bx bxs-moon rc__moon"></i>@endif
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
                                            {{ $sLast['arrival_clock'] ?? '—' }}
                                            @if($nextDay)<span class="rc__nextday">Next Day</span>@endif
                                        </div>
                                        <div class="rc__dt">{{ $sLast['arrival_weekday']??'' }}, {{ $sLast['arrival_label']??'' }}</div>
                                        <div class="rc__city">
                                            {{ fl_city_label($sLast, false) }}@if(!empty($sLast['arrival_terminal'])), Terminal {{ $sLast['arrival_terminal'] }}@endif
                                        </div>
                                    </div>

                                    {{-- night moon far right --}}
                                    @if($isRedEye)
                                        <div class="rc__redeyecol"><i class="bx bxs-moon"></i></div>
                                    @else
                                        <div class="rc__redeyecol"></div>
                                    @endif
                                </div>
                            @endforeach

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

                                    {{-- Flight details expand toggle --}}
                                    <button type="button" class="rc__details-btn" data-rp-expand>
                                        <i class="bx bx-list-ul"></i>
                                        <span data-rp-expand-label>Flight Details</span>
                                        <i class="bx bx-chevron-down rc__details-caret"></i>
                                    </button>
                                </div>

                                <div class="rc__fare-right">
                                    <div class="rc__price">
                                        <div class="rc__price-label">Net Fare</div>
                                        <div class="rc__price-amount">
                                            <span class="rc__price-cur">{{ $cardCur }}</span>{{ number_format($totalPrice, 2) }}
                                        </div>
                                    </div>
                                    <a href="{{ route('user.flights.checkout', ['itinerary' => $lid] + $query) }}"
                                        class="rc__cta">
                                        Book Now <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                </div>
                            </div>

                            {{-- ── flight details panel ── --}}
                            <div class="rc__details-panel" data-rp-details hidden>
                                @foreach($legs as $li => $leg)
                                    @php $segs = $leg['segments'] ?? []; @endphp
                                    @foreach($segs as $sIdx => $sx)
                                        @php
                                            $sxMins   = fl_segment_minutes($sx);
                                            $layover  = $sIdx > 0 ? fl_layover_minutes($segs[$sIdx-1], $sx) : null;
                                        @endphp

                                        @if($layover !== null)
                                            <div class="rcd__layover">
                                                <i class="bx bx-time-five"></i>
                                                <strong>{{ fl_format_hm($layover) }}</strong> layover at
                                                <strong>{{ $sx['from']??'' }}</strong>
                                                @if(!empty($sx['departure_city'])) · {{ $sx['departure_city'] }}@endif
                                            </div>
                                        @endif

                                        <div class="rcd__seg">
                                            <div class="rcd__seg-air">
                                                <img class="rcd__seg-logo"
                                                    src="{{ fl_carrier_logo($sx['carrier']??'') }}"
                                                    loading="lazy" alt=""
                                                    onerror="this.style.visibility='hidden'">
                                                <div>
                                                    <div class="rcd__seg-aname">{{ $sx['carrier_display']??($sx['carrier']??'') }}</div>
                                                    <div class="rcd__seg-ameta">
                                                        {{ strtoupper((string)($sx['carrier']??'')) }}{{ $sx['flight_number']??'' }}
                                                        @if(($sx['operating_carrier']??'') && ($sx['operating_carrier']??'') !== ($sx['carrier']??''))
                                                            · Op {{ $sx['operating_carrier'] }}{{ $sx['operating_flight_number']??'' }}
                                                        @endif
                                                        @if(!empty($sx['equipment'])) · {{ $sx['equipment'] }}@endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="rcd__seg-route">
                                                <div class="rcd__seg-pt">
                                                    <strong>{{ $sx['departure_clock']??'—' }}</strong>
                                                    <span>{{ $sx['from']??'' }}@if(!empty($sx['departure_city'])) · {{ $sx['departure_city'] }}@endif</span>
                                                    <small>{{ $sx['departure_label']??'' }}@if(!empty($sx['departure_terminal'])) · T{{ $sx['departure_terminal'] }}@endif</small>
                                                </div>
                                                <div class="rcd__seg-dur">
                                                    <span>{{ fl_format_hm($sxMins) }}</span>
                                                    <div class="rcd__seg-line"></div>
                                                    @if((int)($sx['stop_count']??0)>0)
                                                        <small>{{ (int)$sx['stop_count'] }} tech stop</small>
                                                    @endif
                                                </div>
                                                <div class="rcd__seg-pt rcd__seg-pt--arr">
                                                    <strong>{{ $sx['arrival_clock']??'—' }}</strong>
                                                    <span>{{ $sx['to']??'' }}@if(!empty($sx['arrival_city'])) · {{ $sx['arrival_city'] }}@endif</span>
                                                    <small>{{ $sx['arrival_label']??'' }}@if(!empty($sx['arrival_terminal'])) · T{{ $sx['arrival_terminal'] }}@endif</small>
                                                </div>
                                            </div>
                                            <div class="rcd__seg-chips">
                                                @if(!empty($sx['cabin_code']))<span class="rcd__chip rcd__chip--cab">{{ $sx['cabin_code'] }}</span>@endif
                                                @if(!empty($sx['booking_code']))<span class="rcd__chip">Class {{ $sx['booking_code'] }}</span>@endif
                                                @if(isset($sx['seats_available']))<span class="rcd__chip rcd__chip--seat"><i class="bx bx-group"></i> {{ (int)$sx['seats_available'] }}</span>@endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>

                        </div>{{-- /.rc --}}
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
    --c-brand:      #cd1b4f;
    --c-brand2:     #a8173f;
    --c-brand-soft: #fdeef3;
    --c-ink:        #1a2540;
    --c-slate:      #4a5568;
    --c-muted:      #8492a6;
    --c-line:       #dde3ef;
    --c-bg:         #f3f5fb;
    --c-white:      #ffffff;
    --c-green:      #0f9d58;
    --c-green-soft: #e8f9f1;
    --c-amber:      #d97706;
    --c-amber-soft: #fef3c7;
    --c-blue:       #2563eb;
    --c-blue-soft:  #eff6ff;
    --c-violet:     #7c3aed;
    --c-violet-soft:#ede9fe;
    --c-shadow:     0 2px 8px rgba(26,37,64,.07);
    --c-shadow-hov: 0 6px 22px rgba(26,37,64,.12);
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
.rp-swrap {
    background: #fff;
    border-bottom: 1px solid var(--c-line);
}

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

.rp-bar__count {
    font-size: .82rem;
    color: var(--c-slate);
}
.rp-bar__count strong {
    color: var(--c-ink);
    font-weight: 700;
}

.rp-bar__sort {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
}
.rp-bar__sortlabel {
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .12em;
    color: var(--c-muted);
    text-transform: uppercase;
}

.rp-sortrow {
    display: flex;
    gap: .2rem;
    flex-wrap: wrap;
}
.rp-sortbtn {
    border: none;
    background: transparent;
    font: inherit;
    font-size: .78rem;
    font-weight: 600;
    color: var(--c-slate);
    padding: .3rem .65rem;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    transition: background .12s, color .12s;
}
.rp-sortbtn:hover { background: var(--c-bg); color: var(--c-ink); }
.rp-sortbtn--active,
.rp-sortbtn.is-active {
    background: var(--c-brand);
    color: #fff;
}
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
    transition: box-shadow .15s ease, border-color .15s ease;
}
.rc:hover {
    border-color: rgba(205,27,79,.25);
    box-shadow: var(--c-shadow-hov);
}

/* ── leg row ── */
.rc__leg {
    display: grid;
    grid-template-columns: 160px 1fr 160px 1fr 32px;
    align-items: center;
    gap: .5rem 1rem;
    padding: .85rem 1rem;
    border-bottom: 1px solid var(--c-line);
}
.rc__leg--ret {
    border-bottom: 1px solid var(--c-line);
    background: #fafbff;
}
.rc__leg:last-of-type { border-bottom: none; }

/* airline cell */
.rc__airline {
    display: flex;
    gap: .55rem;
    align-items: center;
    min-width: 0;
}
.rc__logo {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: contain;
    background: #fff;
    border: 1px solid var(--c-line);
    padding: 2px;
    flex-shrink: 0;
}
.rc__aname {
    font-size: .82rem;
    font-weight: 700;
    color: var(--c-ink);
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rc__aflight {
    font-family: var(--mono);
    font-size: .68rem;
    color: var(--c-muted);
    margin-top: .08rem;
}

/* departure / arrival point */
.rc__point {
    min-width: 0;
}
.rc__point--arr { text-align: right; }

.rc__time {
    font-family: var(--mono);
    font-size: 1.28rem;
    font-weight: 700;
    color: var(--c-ink);
    line-height: 1;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
}
.rc__moon {
    font-size: .85rem;
    color: #6366f1;
}
.rc__nextday {
    font-size: .6rem;
    font-weight: 700;
    background: var(--c-amber-soft);
    color: var(--c-amber);
    padding: .05rem .35rem;
    border-radius: 4px;
    margin-left: .25rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    font-family: var(--sans);
}

.rc__dt {
    font-size: .72rem;
    color: var(--c-muted);
    margin-top: .18rem;
    white-space: nowrap;
}
.rc__city {
    font-size: .75rem;
    color: var(--c-slate);
    font-weight: 500;
    margin-top: .06rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* bridge */
.rc__bridge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .25rem;
}
.rc__btime {
    font-size: .72rem;
    font-weight: 600;
    color: var(--c-slate);
    font-family: var(--mono);
}
.rc__btrack {
    width: 100%;
    display: flex;
    align-items: center;
    gap: .25rem;
    position: relative;
}
.rc__bline {
    flex: 1;
    height: 1px;
    background: var(--c-muted);
    opacity: .4;
}
.rc__bdot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--c-brand);
    flex-shrink: 0;
}
.rc__bvia {
    font-family: var(--mono);
    font-size: .6rem;
    font-weight: 700;
    color: var(--c-white);
    background: var(--c-amber);
    padding: .1rem .35rem;
    border-radius: 4px;
    flex-shrink: 0;
}
.rc__bstop {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    padding: .12rem .45rem;
    border-radius: 4px;
}
.rc__bstop--direct {
    background: var(--c-green-soft);
    color: var(--c-green);
}
.rc__bstop--via {
    background: var(--c-amber-soft);
    color: var(--c-amber);
}

/* red-eye col */
.rc__redeyecol {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #6366f1;
}

/* ── fare row ── */
.rc__fare {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .55rem 1rem;
    padding: .65rem 1rem;
    background: #f8fafd;
    border-top: 1px solid var(--c-line);
}

.rc__fare-left {
    display: flex;
    align-items: center;
    gap: .4rem;
    flex-wrap: wrap;
}
.rc__fare-right {
    display: flex;
    align-items: center;
    gap: .75rem;
}

/* refund badge */
.rc__fbadge {
    font-size: .62rem;
    font-weight: 700;
    padding: .18rem .55rem;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.rc__fbadge--ref {
    background: var(--c-green-soft);
    color: var(--c-green);
}
.rc__fbadge--nr {
    background: #fff0f3;
    color: #c0143c;
}

/* fare tags */
.rc__ftag {
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    font-size: .7rem;
    font-weight: 600;
    background: #edf0f7;
    color: var(--c-slate);
    padding: .15rem .5rem;
    border-radius: 4px;
}
.rc__ftag i { font-size: .82rem; }
.rc__ftag--seat { background: var(--c-amber-soft); color: var(--c-amber); }

/* flight details toggle */
.rc__details-btn {
    border: none;
    background: transparent;
    color: var(--c-brand);
    font: inherit;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    padding: .2rem .45rem;
    border-radius: 5px;
    transition: background .12s;
}
.rc__details-btn:hover { background: var(--c-brand-soft); }
.rc__details-caret { transition: transform .2s ease; }
.rc__details-btn.is-open .rc__details-caret { transform: rotate(180deg); }

/* price block */
.rc__price-label {
    font-size: .58rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--c-brand);
    margin-bottom: .08rem;
}
.rc__price-amount {
    font-family: var(--mono);
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--c-brand);
    line-height: 1;
}
.rc__price-cur {
    font-size: .72rem;
    color: var(--c-slate);
    font-weight: 600;
    margin-right: .1rem;
}

/* CTA */
.rc__cta {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .6rem 1.2rem;
    border-radius: 8px;
    background: linear-gradient(180deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color: #fff !important;
    font-weight: 700;
    font-size: .84rem;
    white-space: nowrap;
    box-shadow: 0 6px 16px rgba(205,27,79,.25);
    transition: transform .14s, box-shadow .14s;
}
.rc__cta:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(205,27,79,.35);
}

/* ── details panel ── */
.rc__details-panel {
    border-top: 1px solid var(--c-line);
    background: #f8fafd;
    padding: .75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: .65rem;
}
.rc__details-panel[hidden] { display: none; }

/* layover badge */
.rcd__layover {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: var(--c-violet-soft);
    color: var(--c-violet);
    font-size: .73rem;
    font-weight: 600;
    padding: .28rem .65rem;
    border-radius: 999px;
    align-self: center;
    border: 1px dashed rgba(124,58,237,.3);
}

/* segment row */
.rcd__seg {
    display: grid;
    grid-template-columns: minmax(130px,.85fr) minmax(0,2fr) minmax(0,.7fr);
    gap: .65rem;
    align-items: center;
    background: #fff;
    border: 1px solid var(--c-line);
    border-radius: 10px;
    padding: .55rem .75rem;
}

.rcd__seg-air { display: flex; gap: .45rem; align-items: center; min-width: 0; }
.rcd__seg-logo {
    width: 26px; height: 26px; border-radius: 6px;
    object-fit: contain; background: #fff;
    border: 1px solid var(--c-line); padding: 1px; flex-shrink: 0;
}
.rcd__seg-aname { font-size: .78rem; font-weight: 700; color: var(--c-ink); }
.rcd__seg-ameta { font-family: var(--mono); font-size: .65rem; color: var(--c-muted); }

.rcd__seg-route {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: .5rem;
    align-items: center;
}
.rcd__seg-pt { display: flex; flex-direction: column; }
.rcd__seg-pt strong { font-family: var(--mono); font-size: .9rem; color: var(--c-ink); font-weight: 700; }
.rcd__seg-pt span { font-size: .72rem; color: var(--c-ink); font-weight: 500; margin-top: .05rem; }
.rcd__seg-pt small { font-size: .64rem; color: var(--c-muted); }
.rcd__seg-pt--arr { text-align: right; align-items: flex-end; }

.rcd__seg-dur {
    display: flex; flex-direction: column; align-items: center; gap: .2rem;
    font-family: var(--mono); font-size: .68rem; color: var(--c-muted); font-weight: 600;
}
.rcd__seg-line {
    width: 100%; height: 1px;
    background: linear-gradient(90deg, var(--c-brand), var(--c-line));
}
.rcd__seg-dur small { font-size: .6rem; color: var(--c-amber); font-weight: 600; text-transform: uppercase; }

.rcd__seg-chips {
    display: flex; flex-direction: column; align-items: flex-end; gap: .2rem;
}
.rcd__chip {
    display: inline-flex; align-items: center; gap: .15rem;
    background: #edf0f7; color: var(--c-slate);
    font-family: var(--mono); font-size: .62rem; font-weight: 600;
    padding: .1rem .38rem; border-radius: 4px;
}
.rcd__chip--cab { background: var(--c-blue-soft); color: var(--c-blue); }
.rcd__chip--seat { background: var(--c-amber-soft); color: var(--c-amber); }

/* =========================================================
   EMPTY STATES
   ========================================================= */
.rp-empty {
    margin: 3rem auto;
    max-width: 480px;
    text-align: center;
    padding: 2rem 1.5rem;
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 16px;
    box-shadow: var(--c-shadow);
}
.rp-empty__icon {
    font-size: 2.4rem;
    color: var(--c-brand);
    display: block;
    margin-bottom: .75rem;
}
.rp-empty__icon--idle { color: var(--c-blue); }
.rp-empty h3 { font-size: 1.15rem; font-weight: 700; margin: 0 0 .5rem; color: var(--c-ink); }
.rp-empty p { font-size: .88rem; color: var(--c-slate); margin: 0 0 1.1rem; line-height: 1.55; }
.rp-emptybtn {
    display: inline-flex; align-items: center; gap: .3rem;
    background: var(--c-brand); color: #fff !important;
    padding: .6rem 1.2rem; border-radius: 8px;
    font-weight: 700; font-size: .86rem;
    box-shadow: 0 6px 16px rgba(205,27,79,.25);
}

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 991px) {
    .rc__leg {
        grid-template-columns: 140px 1fr 130px 1fr 24px;
        gap: .4rem .65rem;
    }
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
    .rcd__seg { grid-template-columns: 1fr; }
    .rcd__seg-chips { align-items: flex-start; }
}
</style>
@endpush

@push('js')
<script>
(function(){
    const list = document.getElementById('rp-list');
    if (!list) return;

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
                case 'best-value':     {
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
            if(canCycle && (btn.classList.contains('rp-sortbtn--active')||btn.classList.contains('is-active'))){
                if(target==='price-asc'){ target='price-desc'; btn.classList.add('is-desc'); }
                else { target='price-asc'; btn.classList.remove('is-desc'); }
                btn.dataset.rpSort = target;
            } else if(canCycle){
                btn.classList.remove('is-desc');
            }
            document.querySelectorAll('.rp-sortbtn').forEach(b=>{
                b.classList.remove('rp-sortbtn--active','is-active');
            });
            btn.classList.add('is-active');
            currentSort = target;
            sortCards();
        });
    });

    document.querySelectorAll('[data-rp-expand]').forEach(btn=>{
        btn.addEventListener('click',()=>{
            const card = btn.closest('.rc');
            if(!card) return;
            const panel = card.querySelector('[data-rp-details]');
            if(!panel) return;
            const open = btn.classList.toggle('is-open');
            panel.hidden = !open;
            const lbl = btn.querySelector('[data-rp-expand-label]');
            if(lbl) lbl.textContent = open ? 'Hide Details' : 'Flight Details';
        });
    });

    sortCards();
})();
</script>
@endpush
