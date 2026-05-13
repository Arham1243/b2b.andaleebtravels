@extends('user.layouts.main')
@section('content')
    @php
        $adults   = (int) ($searchParams['adults']   ?? 1);
        $children = (int) ($searchParams['children'] ?? 0);
        $infants  = (int) ($searchParams['infants']  ?? 0);
        $currency = $currency ?? 'AED';
        $legs     = $itinerary['legs'] ?? [];

        $baseAmount = (float) ($itinerary['basePrice'] ?? ($totalAmount ?? 0));
        $taxAmount  = (float) ($itinerary['taxes']     ?? 0);
        if ($baseAmount + $taxAmount < 0.01) { $baseAmount = $totalAmount; }
        $paxCount = max(1, $adults + $children + $infants);

        function hd_fmt(?int $mins): string {
            if (!$mins || $mins < 1) return ' - ';
            $h = intdiv($mins, 60); $r = $mins % 60;
            if ($h && !$r) return "{$h}h";
            if (!$h) return "{$r}m";
            return "{$h}h {$r}m";
        }
        function hd_logo(?string $c): string {
            return 'https://pics.avs.io/80/80/'.strtoupper(trim($c ?: 'XX')).'.png';
        }

        // Build a back URL to search results
        $searchBackUrl = route('user.flights.search') . '?' . http_build_query(array_filter($searchParams ?? []));
    @endphp

    <div class="hp">
        <div class="container hp-shell">

            {{-- breadcrumb --}}
            <nav class="hp-crumb" aria-label="breadcrumb">
                <a href="{{ route('user.flights.index') }}"><i class="bx bx-plane-take-off"></i> Flights</a>
                <i class="bx bx-chevron-right hp-crumb__sep"></i>
                <a href="{{ $searchBackUrl }}">Search Results</a>
                <i class="bx bx-chevron-right hp-crumb__sep"></i>
                <span>Hold Booking</span>
            </nav>

            <form id="holdForm" action="{{ route('user.flights.hold.process') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="itinerary_id" value="{{ $itineraryId }}">

                {{-- hidden lead fields mirrored from passenger[0] via JS --}}
                <input type="hidden" name="lead[title]"      id="lead-title-hidden"  value="Mr">
                <input type="hidden" name="lead[first_name]" id="lead-fname-hidden"  value="">
                <input type="hidden" name="lead[last_name]"  id="lead-lname-hidden"  value="">

                <div class="row g-4">

                    {{-- ===================================================
                         LEFT  col-8
                         =================================================== --}}
                    <div class="col-lg-8">

                        {{-- ── FLIGHT DETAILS CARD ── --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bxs-plane hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__eyebrow">Flight Summary</div>
                                    <div class="hp-card__title">
                                        {{ strtoupper($searchParams['from'] ?? '') }}
                                        @if(count($legs) > 1) ⇄ @else → @endif
                                        {{ strtoupper($searchParams['to'] ?? '') }}
                                    </div>
                                </div>
                                <div class="ms-auto">
                                    @if(!empty($itinerary['non_refundable']))
                                        <span class="hp-badge hp-badge--nr">Non-Refundable</span>
                                    @else
                                        <span class="hp-badge hp-badge--ref">Refundable</span>
                                    @endif
                                </div>
                            </div>

                            <div class="hp-flight">
                                @foreach ($legs as $li => $leg)
                                    @php
                                        $segs    = $leg['segments'] ?? [];
                                        $s0      = $segs[0] ?? [];
                                        $sLast   = !empty($segs) ? $segs[array_key_last($segs)] : [];
                                        $conn    = max(0, count($segs) - 1);
                                        $tech    = collect($segs)->sum(fn($s)=>(int)($s['stop_count']??0));
                                        $stops   = $conn + $tech;
                                        $dur     = hd_fmt(isset($leg['elapsedTime'])?(int)$leg['elapsedTime']:null);
                                        $nextDay = (bool)($sLast['next_day_hint'] ?? false);

                                        $midApts = [];
                                        for ($mi = 0; $mi < count($segs) - 1; $mi++) {
                                            $midApts[] = $segs[$mi]['to'] ?? '';
                                        }
                                    @endphp

                                    <div class="hp-leg {{ $li > 0 ? 'hp-leg--ret' : '' }}">

                                        {{-- leg type label --}}
                                        <div class="hp-leg__tag">
                                            <i class="bx {{ $li === 0 ? 'bxs-plane-take-off' : 'bxs-plane-land' }}"></i>
                                            <span>{{ $li === 0 ? ($adults > 0 && count($legs) > 1 ? 'Outbound' : 'Flight') : 'Return' }}</span>
                                        </div>

                                        <div class="hp-leg__row">

                                            {{-- airline --}}
                                            <div class="hp-leg__airline">
                                                <div class="hp-leg__logo-wrap">
                                                    <img class="hp-leg__logo"
                                                        src="{{ hd_logo($s0['carrier'] ?? '') }}"
                                                        loading="lazy" alt="{{ $s0['carrier_display'] ?? '' }}"
                                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($s0['carrier'] ?? 'FL') }}&background=cd1b4f&color=fff&size=80'">
                                                </div>
                                                <div>
                                                    <div class="hp-leg__aname">{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}</div>
                                                    <div class="hp-leg__aflight">{{ strtoupper((string)($s0['carrier'] ?? '')) }}{{ $s0['flight_number'] ?? '' }}</div>
                                                </div>
                                            </div>

                                            {{-- departure --}}
                                            <div class="hp-leg__pt">
                                                <div class="hp-leg__time">{{ $s0['departure_clock'] ?? ' - ' }}</div>
                                                <div class="hp-leg__dt">{{ $s0['departure_weekday'] ?? '' }}, {{ $s0['departure_label'] ?? '' }}</div>
                                                <div class="hp-leg__city">
                                                    {{ $s0['from'] ?? '' }}@if(!empty($s0['departure_terminal'])), T{{ $s0['departure_terminal'] }}@endif
                                                </div>
                                            </div>

                                            {{-- bridge --}}
                                            <div class="hp-leg__bridge">
                                                <div class="hp-leg__bridge-dur">{{ $dur }}</div>
                                                <div class="hp-leg__bridge-track">
                                                    <span class="hp-leg__bridge-dot"></span>
                                                    @foreach($midApts as $ma)
                                                        <span class="hp-leg__bridge-via">{{ $ma }}</span>
                                                    @endforeach
                                                    <span class="hp-leg__bridge-line"></span>
                                                    <span class="hp-leg__bridge-dot"></span>
                                                </div>
                                                @if($stops === 0)
                                                    <div class="hp-leg__bridge-stop hp-leg__bridge-stop--ok">Non-stop</div>
                                                @else
                                                    <div class="hp-leg__bridge-stop hp-leg__bridge-stop--via">
                                                        {{ $stops === 1 ? '1 Stop' : $stops.' Stops' }}
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- arrival --}}
                                            <div class="hp-leg__pt hp-leg__pt--arr">
                                                <div class="hp-leg__time">
                                                    {{ $sLast['arrival_clock'] ?? ' - ' }}
                                                    @if($nextDay)<span class="hp-nextday">+1</span>@endif
                                                </div>
                                                <div class="hp-leg__dt">{{ $sLast['arrival_weekday'] ?? '' }}, {{ $sLast['arrival_label'] ?? '' }}</div>
                                                <div class="hp-leg__city">
                                                    {{ $sLast['to'] ?? '' }}@if(!empty($sLast['arrival_terminal'])), T{{ $sLast['arrival_terminal'] }}@endif
                                                </div>
                                            </div>

                                        </div>{{-- /.hp-leg__row --}}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ── PASSENGER INFORMATION ── --}}
                        @php $pIndex = 0; @endphp

                        @for ($i = 0; $i < $adults; $i++)
                            <div class="hp-card mb-3">
                                <div class="hp-card__head">
                                    <i class="bx bx-user hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger Information</div>
                                        <div class="hp-card__title">Adult {{ $i + 1 }} <span class="hp-card__age">12+ years</span></div>
                                    </div>
                                </div>

                                <div class="hp-pax-note">
                                    <i class="bx bx-info-circle"></i>
                                    Traveller's passport should be valid for at least 6 months from the date of travel.
                                </div>

                                @if(!empty($savedPassengers))
                                    <div class="hp-saved-row">
                                        <label class="hp-label" for="saved-{{ $pIndex }}">Load from saved passengers</label>
                                        <select class="hp-select hp-saved-pick" id="saved-{{ $pIndex }}" data-pax-idx="{{ $pIndex }}">
                                            <option value=""> -  Select saved passenger  - </option>
                                            @foreach($savedPassengers as $sp)
                                                <option value="{{ json_encode($sp) }}">
                                                    {{ $sp['title'] }} {{ $sp['first_name'] }} {{ $sp['last_name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="ADT">
                                <div class="row g-3 hp-pax-fields">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mr">Mr.</option>
                                            <option value="Mrs">Mrs.</option>
                                            <option value="Ms">Ms.</option>
                                            <option value="Dr">Dr.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]"
                                            placeholder="Enter first name" required autocomplete="given-name">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]"
                                            placeholder="Enter last name" required autocomplete="family-name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                        <span class="hp-hint">Age calculated as per travel date</span>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Nationality</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][nationality]"
                                            placeholder="e.g. AE, PK, IN" maxlength="4" style="text-transform:uppercase;">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Number</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][passport_no]"
                                            placeholder="Passport number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Expiry</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][passport_exp]">
                                    </div>
                                    <div class="col-12">
                                        <label class="hp-save-check">
                                            <input type="checkbox" name="passengers[{{ $pIndex }}][save_profile]" value="1">
                                            <span>Save this passenger to my profile for future bookings</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        @for ($i = 0; $i < $children; $i++)
                            <div class="hp-card mb-3">
                                <div class="hp-card__head">
                                    <i class="bx bx-user hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger Information</div>
                                        <div class="hp-card__title">Child {{ $i + 1 }} <span class="hp-card__age">2–11 years</span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="C06">
                                <div class="row g-3 hp-pax-fields">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mstr">Mstr.</option>
                                            <option value="Miss">Miss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" placeholder="Enter first name" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" placeholder="Enter last name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Number</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][passport_no]" placeholder="Passport number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Expiry</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][passport_exp]">
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        @for ($i = 0; $i < $infants; $i++)
                            <div class="hp-card mb-3">
                                <div class="hp-card__head">
                                    <i class="bx bx-baby-carriage hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger Information</div>
                                        <div class="hp-card__title">Infant {{ $i + 1 }} <span class="hp-card__age">Under 2 years</span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="INF">
                                <div class="row g-3 hp-pax-fields">
                                    <div class="col-md-2">
                                        <label class="hp-label">Title <span class="hp-req">*</span></label>
                                        <select class="hp-select" name="passengers[{{ $pIndex }}][title]" required>
                                            <option value="Mstr">Mstr.</option>
                                            <option value="Miss">Miss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" placeholder="Enter first name" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" placeholder="Enter last name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                    </div>
                                </div>
                            </div>
                            @php $pIndex++; @endphp
                        @endfor

                        {{-- ── CONTACT DETAILS ── --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bx-phone hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__title" style="margin-top:0;">Contact Details</div>
                                </div>
                            </div>

                            <p class="hp-contact-note">
                                <i class="bx bx-info-circle"></i>
                                These details will be shared with the airline for booking confirmation.
                            </p>

                            <div class="row g-3 hp-pax-fields">
                                <div class="col-md-6">
                                    <label class="hp-label">Phone <span class="hp-req">*</span></label>
                                    <input type="tel" class="hp-input" name="lead[phone]"
                                        placeholder="+971 50 000 0000" required autocomplete="tel">
                                </div>
                                <div class="col-md-6">
                                    <label class="hp-label">Email <span class="hp-req">*</span></label>
                                    <input type="email" class="hp-input" name="lead[email]"
                                        placeholder="email@example.com" required autocomplete="email">
                                </div>
                            </div>
                        </div>

                    </div>{{-- /.col-lg-8 --}}

                    {{-- ===================================================
                         RIGHT  col-4  -  sticky fare summary
                         =================================================== --}}
                    <div class="col-lg-4">
                        <div class="hp-summary" id="hp-summary-sticky">

                            <div class="hp-summary__head">
                                <i class="bx bx-receipt"></i>
                                Fare Summary
                            </div>

                            <div class="hp-summary__body">
                                @php $adultBase = $adults > 0 ? round($baseAmount / $paxCount, 2) : 0; @endphp
                                @if($adults > 0)
                                    <div class="hp-sum-row">
                                        <span>Adult × {{ $adults }}</span>
                                        <span><span class="dirham">AED</span> {{ number_format($adultBase * $adults, 2) }}</span>
                                    </div>
                                @endif
                                @if($children > 0)
                                    @php $childBase = round($baseAmount / $paxCount, 2); @endphp
                                    <div class="hp-sum-row">
                                        <span>Child × {{ $children }}</span>
                                        <span><span class="dirham">AED</span> {{ number_format($childBase * $children, 2) }}</span>
                                    </div>
                                @endif
                                @if($infants > 0)
                                    @php $infantBase = round($baseAmount / $paxCount * 0.1, 2); @endphp
                                    <div class="hp-sum-row">
                                        <span>Infant × {{ $infants }}</span>
                                        <span><span class="dirham">AED</span> {{ number_format($infantBase * $infants, 2) }}</span>
                                    </div>
                                @endif
                                @if($taxAmount > 0)
                                    <div class="hp-sum-row">
                                        <span>Taxes &amp; Fees</span>
                                        <span><span class="dirham">AED</span> {{ number_format($taxAmount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="hp-sum-row hp-sum-row--sep">
                                    <span>Hold Deposit</span>
                                    <span class="hp-sum-free">FREE</span>
                                </div>
                            </div>

                            <div class="hp-summary__total">
                                <span>Total Fare</span>
                                <span class="hp-summary__total-amount">
                                    <span class="dirham">AED</span>{{ number_format($totalAmount, 2) }}
                                </span>
                            </div>

                            <div class="hp-summary__meta">
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-user"></i>
                                    <span>
                                        @php
                                            $paxStr = $adults . ' Adult' . ($adults > 1 ? 's' : '');
                                            if ($children > 0) $paxStr .= ', ' . $children . ' Child' . ($children > 1 ? 'ren' : '');
                                            if ($infants > 0)  $paxStr .= ', ' . $infants . ' Infant' . ($infants > 1 ? 's' : '');
                                        @endphp
                                        {{ $paxStr }}
                                    </span>
                                </div>
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-map"></i>
                                    <span>{{ strtoupper($searchParams['from'] ?? '') }} → {{ strtoupper($searchParams['to'] ?? '') }}</span>
                                </div>
                                @if(!empty($searchParams['departure_date']))
                                    <div class="hp-summary__meta-row">
                                        <i class="bx bxs-calendar"></i>
                                        <span>
                                            {{ $searchParams['departure_date'] }}
                                            @if(!empty($searchParams['return_date']))  -  {{ $searchParams['return_date'] }} @endif
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Hold rules (right side only) --}}
                            <div class="hp-rules-box">
                                <div class="hp-rules-box__head">
                                    <i class="bx bx-time-five"></i> Hold Booking Rules
                                </div>
                                <div class="hp-rules-box__rows">
                                    <div class="hp-rules-box__row">
                                        <span>Hold Deposit</span>
                                        <strong><span class="dirham">AED</span> 0.00</strong>
                                    </div>
                                    <div class="hp-rules-box__row">
                                        <span>Ticketing Time Limit</span>
                                        {{-- Not from Sabre on this screen; real TTL is set when the hold PNR is created --}}
                                        <strong class="hp-rules-box__ttl">On your PNR</strong>
                                    </div>
                                </div>
                                <div class="hp-rules-box__alerts">
                                    <p><i class="bx bx-error-circle"></i> Hold is valid for <strong>~1 hour</strong>. Exact limit confirmed after PNR creation.</p>
                                    <p><i class="bx bx-chevron-right"></i> All fares &amp; seats subject to availability at time of booking.</p>
                                    <p><i class="bx bx-chevron-right"></i> No tickets issued until you confirm ticketing.</p>
                                    <p><i class="bx bx-user-check"></i> <strong>Verify passenger names carefully  -  name changes not permitted once issued.</strong></p>
                                </div>
                            </div>

                            <div class="hp-summary__footer">
                                <button type="submit" form="holdForm" class="hp-btn-hold">
                                    <i class="bx bx-time-five"></i>
                                    Hold Booking &nbsp;·&nbsp; <span class="dirham">AED</span> 0.00
                                </button>
                                <div class="hp-summary__secure">
                                    <i class="bx bxs-lock-alt"></i> Secure &amp; encrypted
                                </div>
                            </div>

                        </div>
                    </div>

                </div>{{-- /.row --}}
            </form>

        </div>
    </div>
@endsection

@push('css')
<style>
/* =========================================================
   TOKENS
   ========================================================= */
.hp {
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
    --c-shadow:      0 2px 8px rgba(26,37,64,.07);
    --c-shadow-md:   0 6px 22px rgba(26,37,64,.11);
    --mono: "JetBrains Mono", ui-monospace, Menlo, monospace;
    --sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    font-family: var(--sans);
    color: var(--c-ink);
    background: var(--c-bg);
    padding-bottom: 3rem;
}
.hp * { box-sizing: border-box; }
.hp a { text-decoration: none; }

.hp-shell { padding-top: 1.25rem; }

/* =========================================================
   BREADCRUMB
   ========================================================= */
.hp-crumb {
    display: flex; align-items: center; gap: .35rem;
    font-size: .8rem; margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.hp-crumb a {
    color: var(--c-brand); font-weight: 600;
    display: inline-flex; align-items: center; gap: .22rem;
    padding: .2rem .45rem; border-radius: 6px;
    transition: background .12s;
}
.hp-crumb a:hover { background: var(--c-brand-soft); }
.hp-crumb span { color: var(--c-ink); font-weight: 700; padding: .2rem .45rem; }
.hp-crumb__sep { color: var(--c-muted); font-size: .85rem; }

/* =========================================================
   CARD SHELL
   ========================================================= */
.hp-card {
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    box-shadow: var(--c-shadow);
    overflow: hidden;
}

.hp-card__head {
    display: flex; align-items: center; gap: .75rem;
    padding: .8rem 1.1rem;
    border-bottom: 1px solid var(--c-line);
    background: linear-gradient(135deg, rgba(205,27,79,.035) 0%, transparent 70%);
}
.hp-card__head-icon { font-size: 1.35rem; color: var(--c-brand); flex-shrink: 0; }
.hp-card__eyebrow {
    font-size: .58rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--c-muted); line-height: 1;
}
.hp-card__title {
    font-size: .95rem; font-weight: 700; color: var(--c-ink); margin-top: .04rem;
}
.hp-card__age {
    font-size: .7rem; font-weight: 500; color: var(--c-muted);
    background: var(--c-bg); padding: .06rem .38rem; border-radius: 4px;
    margin-left: .3rem; vertical-align: middle;
}

.hp-badge {
    font-size: .6rem; font-weight: 700; padding: .18rem .5rem;
    border-radius: 4px; text-transform: uppercase; letter-spacing: .06em;
    white-space: nowrap;
}
.hp-badge--ref { background: var(--c-green-soft); color: var(--c-green); }
.hp-badge--nr  { background: #fff0f3; color: #c0143c; }

/* =========================================================
   FLIGHT CARD
   ========================================================= */
.hp-flight { padding: 0; }

.hp-leg { padding: .9rem 1.1rem; }
.hp-leg--ret {
    border-top: 1px dashed var(--c-line);
}

.hp-leg__tag {
    display: inline-flex; align-items: center; gap: .28rem;
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
    color: var(--c-brand); margin-bottom: .6rem;
    background: var(--c-brand-soft); padding: .2rem .6rem; border-radius: 20px;
}

.hp-leg__row {
    display: grid;
    grid-template-columns: 160px 1fr auto 1fr;
    align-items: center;
    gap: .5rem 1.1rem;
}

/* airline cell */
.hp-leg__airline { display: flex; gap: .6rem; align-items: center; min-width: 0; }
.hp-leg__logo-wrap {
    width: 48px; height: 48px; flex-shrink: 0; border-radius: 11px;
    border: 1.5px solid var(--c-line); background: #fff;
    box-shadow: 0 1px 6px rgba(26,37,64,.07);
    display: flex; align-items: center; justify-content: center;
    padding: 3px; overflow: hidden;
}
.hp-leg__logo { width: 100%; height: 100%; object-fit: contain; display: block; }
.hp-leg__aname  { font-size: .83rem; font-weight: 700; color: var(--c-ink); line-height: 1.25; }
.hp-leg__aflight { font-family: var(--mono); font-size: .67rem; color: var(--c-muted); margin-top: .06rem; }

/* departure / arrival point */
.hp-leg__pt { min-width: 0; }
.hp-leg__pt--arr { text-align: right; }
.hp-leg__time {
    font-family: var(--mono); font-size: 1.28rem; font-weight: 700;
    color: var(--c-ink); line-height: 1;
    display: inline-flex; align-items: center; gap: .25rem;
}
.hp-leg__dt   { font-size: .68rem; color: var(--c-muted); margin-top: .16rem; white-space: nowrap; }
.hp-leg__city { font-size: .73rem; color: var(--c-slate); font-weight: 500; margin-top: .05rem; }
.hp-nextday {
    font-size: .55rem; font-weight: 700;
    background: var(--c-amber-soft); color: var(--c-amber);
    padding: .03rem .28rem; border-radius: 4px; font-family: var(--sans);
}

/* bridge (center column)  -  mirrors listing page rc__bridge */
.hp-leg__bridge {
    display: flex; flex-direction: column; align-items: center; gap: .22rem;
    min-width: 120px;
}
.hp-leg__bridge-dur {
    font-size: .7rem; font-weight: 600; color: var(--c-slate); font-family: var(--mono);
}
.hp-leg__bridge-track {
    width: 100%; display: flex; align-items: center; gap: .2rem;
}
.hp-leg__bridge-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--c-brand); flex-shrink: 0;
}
.hp-leg__bridge-line {
    flex: 1; height: 1px; background: var(--c-muted); opacity: .35;
}
.hp-leg__bridge-via {
    font-family: var(--mono); font-size: .58rem; font-weight: 700;
    color: #fff; background: var(--c-amber);
    padding: .08rem .32rem; border-radius: 4px; flex-shrink: 0;
}
.hp-leg__bridge-stop {
    font-size: .63rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; padding: .1rem .42rem; border-radius: 4px;
}
.hp-leg__bridge-stop--ok  { background: var(--c-green-soft); color: var(--c-green); }
.hp-leg__bridge-stop--via { background: var(--c-amber-soft); color: var(--c-amber); }

/* =========================================================
   PASSENGER FORM
   ========================================================= */
.hp-pax-note {
    margin: 0; padding: .5rem 1.1rem;
    font-size: .74rem; color: var(--c-amber);
    background: var(--c-amber-soft);
    border-bottom: 1px solid rgba(217,119,6,.15);
    display: flex; align-items: center; gap: .35rem;
}
.hp-pax-note i { font-size: .88rem; flex-shrink: 0; }

.hp-saved-row { padding: .7rem 1.1rem .2rem; }

.hp-pax-fields { padding: .85rem 1.1rem 1rem; }

.hp-label {
    font-size: .68rem; font-weight: 700; color: var(--c-slate);
    display: block; margin-bottom: .28rem;
    text-transform: uppercase; letter-spacing: .07em;
}
.hp-req  { color: var(--c-brand); }
.hp-hint { font-size: .62rem; color: var(--c-muted); margin-top: .18rem; display: block; }

.hp-input {
    width: 100%; padding: .55rem .75rem;
    border: 1.5px solid var(--c-line); border-radius: 8px;
    font: inherit; font-size: .86rem; color: var(--c-ink); background: #fff;
    transition: border-color .14s, box-shadow .14s; outline: none;
}
.hp-input:focus { border-color: var(--c-brand); box-shadow: 0 0 0 3px rgba(205,27,79,.1); }
.hp-input::placeholder { color: var(--c-muted); }

.hp-select {
    width: 100%; padding: .55rem .75rem;
    border: 1.5px solid var(--c-line); border-radius: 8px;
    font: inherit; font-size: .86rem; color: var(--c-ink); background: #fff;
    transition: border-color .14s; outline: none; appearance: auto;
}
.hp-select:focus { border-color: var(--c-brand); box-shadow: 0 0 0 3px rgba(205,27,79,.1); }

.hp-save-check {
    display: flex; align-items: center; gap: .5rem;
    font-size: .76rem; color: var(--c-slate); cursor: pointer;
    padding: .45rem .7rem;
    background: var(--c-bg); border: 1px dashed var(--c-line); border-radius: 8px;
}
.hp-save-check input[type=checkbox] {
    accent-color: var(--c-brand); width: 15px; height: 15px; flex-shrink: 0;
}

/* =========================================================
   CONTACT DETAILS
   ========================================================= */
.hp-contact-note {
    margin: 0; padding: .45rem 1.1rem;
    font-size: .73rem; color: var(--c-slate);
    background: var(--c-bg); border-bottom: 1px solid var(--c-line);
    display: flex; align-items: center; gap: .35rem;
}
.hp-contact-note i { color: var(--c-brand); }

/* =========================================================
   RIGHT: SUMMARY
   ========================================================= */
.hp-summary {
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    box-shadow: var(--c-shadow);
    overflow: hidden;
    position: sticky; top: 90px;
}
.hp-summary__head {
    padding: .85rem 1.1rem;
    background: linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color: #fff; font-size: .92rem; font-weight: 700;
    display: flex; align-items: center; gap: .45rem;
}
.hp-summary__head i { font-size: 1.1rem; }

.hp-summary__body {
    padding: .75rem 1.1rem;
    border-bottom: 1px solid var(--c-line);
    display: flex; flex-direction: column; gap: .4rem;
}
.hp-sum-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .8rem; color: var(--c-slate);
}
.hp-sum-row span:last-child { font-family: var(--mono); font-weight: 600; color: var(--c-ink); }
.hp-sum-row--sep { border-top: 1px dashed var(--c-line); padding-top: .4rem; margin-top: .1rem; }
.hp-sum-free {
    font-size: .68rem; font-weight: 700;
    background: var(--c-green-soft); color: var(--c-green);
    padding: .08rem .38rem; border-radius: 4px;
}

.hp-summary__total {
    display: flex; justify-content: space-between; align-items: center;
    padding: .8rem 1.1rem; font-size: .86rem; font-weight: 700; color: var(--c-ink);
    border-bottom: 1px solid var(--c-line);
}
.hp-summary__total-amount {
    font-family: var(--mono); font-size: 1.22rem; font-weight: 700; color: var(--c-brand);
    display: flex; align-items: baseline; gap: .05rem;
}

.hp-summary__meta {
    padding: .6rem 1.1rem; border-bottom: 1px solid var(--c-line);
    display: flex; flex-direction: column; gap: .28rem;
}
.hp-summary__meta-row {
    display: flex; align-items: center; gap: .4rem;
    font-size: .74rem; color: var(--c-slate);
}
.hp-summary__meta-row i { color: var(--c-brand); font-size: .85rem; flex-shrink: 0; }

/* =========================================================
   HOLD RULES BOX (right)
   ========================================================= */
.hp-rules-box { border-bottom: 1px solid var(--c-line); }
.hp-rules-box__head {
    padding: .6rem 1.1rem;
    font-size: .72rem; font-weight: 700; color: var(--c-amber);
    background: var(--c-amber-soft);
    display: flex; align-items: center; gap: .35rem;
}
.hp-rules-box__rows {
    padding: .55rem 1.1rem; display: flex; flex-direction: column; gap: .35rem;
    border-bottom: 1px solid rgba(217,119,6,.12);
    background: #fffdf5;
}
.hp-rules-box__row {
    display: flex; justify-content: space-between; align-items: baseline;
    font-size: .76rem; color: var(--c-slate); gap: .5rem; flex-wrap: wrap;
}
.hp-rules-box__row strong { font-family: var(--mono); color: var(--c-ink); font-size: .77rem; }
.hp-rules-box__ttl { color: var(--c-amber) !important; }

.hp-rules-box__alerts {
    padding: .6rem 1.1rem; display: flex; flex-direction: column; gap: .3rem;
    background: #fffdf5;
}
.hp-rules-box__alerts p {
    margin: 0; font-size: .71rem; color: var(--c-slate); line-height: 1.45;
    display: flex; gap: .28rem; align-items: flex-start;
}
.hp-rules-box__alerts p i { color: var(--c-muted); font-size: .8rem; flex-shrink: 0; margin-top: .07rem; }

/* =========================================================
   SUMMARY FOOTER + BUTTON
   ========================================================= */
.hp-summary__footer { padding: .85rem 1.1rem; display: flex; flex-direction: column; gap: .5rem; }

.hp-btn-hold {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: .35rem;
    background: linear-gradient(180deg, var(--c-amber) 0%, #b45309 100%);
    color: #fff !important; font: inherit; font-size: .88rem; font-weight: 700;
    padding: .75rem 1rem; border: none; border-radius: 9px; cursor: pointer;
    box-shadow: 0 5px 16px rgba(217,119,6,.3);
    transition: transform .13s, box-shadow .13s;
}
.hp-btn-hold:hover { transform: translateY(-1px); box-shadow: 0 9px 24px rgba(217,119,6,.4); }

.hp-summary__secure {
    font-size: .68rem; color: var(--c-muted); text-align: center;
    display: flex; align-items: center; justify-content: center; gap: .28rem;
}
.hp-summary__secure i { color: var(--c-green); }

/* =========================================================
   DIRHAM
   ========================================================= */
.dirham {
    font-family: "UAEDirham", "Segoe UI", sans-serif;
    font-size: .8em; font-weight: 400; color: inherit;
    margin-right: .04rem; vertical-align: baseline;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 991px) {
    .hp-leg__row { grid-template-columns: 1fr 1fr; gap: .5rem; }
    .hp-leg__airline { grid-column: 1 / -1; }
    .hp-leg__bridge { grid-column: 1 / -1; flex-direction: row; justify-content: center; gap: .5rem; }
    .hp-leg__bridge-track { width: 160px; }
    .hp-leg__pt--arr { text-align: left; }
    .hp-summary { position: static; }
}
</style>
@endpush

@push('js')
<script>
(function () {
    /* Mirror passenger[0] into lead hidden fields */
    function syncLead() {
        const get = (name) => document.querySelector('[name="passengers[0][' + name + ']"]');
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };

        const titleEl = get('title');
        const fnEl    = get('first_name');
        const lnEl    = get('last_name');

        if (titleEl) set('lead-title-hidden', titleEl.value);
        if (fnEl)    set('lead-fname-hidden', fnEl.value);
        if (lnEl)    set('lead-lname-hidden', lnEl.value);
    }

    ['passengers[0][title]', 'passengers[0][first_name]', 'passengers[0][last_name]'].forEach(function (nm) {
        const el = document.querySelector('[name="' + nm + '"]');
        if (el) el.addEventListener('input', syncLead);
        if (el) el.addEventListener('change', syncLead);
    });

    /* Saved passenger auto-fill */
    document.querySelectorAll('.hp-saved-pick').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const idx = this.dataset.paxIdx;
            if (!this.value) return;
            let pax;
            try { pax = JSON.parse(this.value); } catch (e) { return; }

            function fill(name, val) {
                const el = document.querySelector('[name="passengers[' + idx + '][' + name + ']"]');
                if (!el || val == null) return;
                el.value = val;
                el.dispatchEvent(new Event('change'));
            }
            fill('title',        pax.title);
            fill('first_name',   pax.first_name);
            fill('last_name',    pax.last_name);
            fill('dob',          pax.dob          ? String(pax.dob).substring(0, 10) : '');
            fill('nationality',  pax.nationality);
            fill('passport_no',  pax.passport_no);
            fill('passport_exp', pax.passport_exp ? String(pax.passport_exp).substring(0, 10) : '');
        });
    });

    /* Disable button on submit */
    document.getElementById('holdForm').addEventListener('submit', function () {
        syncLead();
        document.querySelectorAll('.hp-btn-hold').forEach(function (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing…';
        });
    });
})();
</script>
@endpush
