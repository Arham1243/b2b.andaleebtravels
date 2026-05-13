@extends('user.layouts.main')
@section('content')
    @php
        $adults   = (int) ($searchParams['adults']   ?? 1);
        $children = (int) ($searchParams['children'] ?? 0);
        $infants  = (int) ($searchParams['infants']  ?? 0);
        $currency = $currency ?? 'AED';
        $legs     = $itinerary['legs'] ?? [];
        $firstSeg = $legs[0]['segments'][0] ?? [];

        $taxAmount   = 0; // pulled from itinerary if available
        $baseAmount  = (float) ($itinerary['basePrice'] ?? ($totalAmount ?? 0));
        $taxAmount   = (float) ($itinerary['taxes']     ?? 0);
        if ($baseAmount + $taxAmount < 0.01) { $baseAmount = $totalAmount; }
        $paxCount    = max(1, $adults + $children + $infants);

        function hd_fmt(?int $mins): string {
            if (!$mins || $mins < 1) return '—';
            $h = intdiv($mins, 60); $r = $mins % 60;
            if ($h && !$r) return "{$h}h";
            if (!$h) return "{$r}m";
            return "{$h}h {$r}m";
        }
        function hd_logo(?string $c): string {
            return 'https://pics.avs.io/80/80/'.strtoupper(trim($c ?: 'XX')).'.png';
        }
        function hd_stops(int $n): string {
            return $n === 0 ? 'Non-stop' : ($n === 1 ? '1 stop' : "$n stops");
        }
    @endphp

    <div class="hp">
        <div class="container hp-shell">

            {{-- breadcrumb --}}
            <nav class="hp-crumb">
                <a href="{{ route('user.flights.index') }}"><i class="bx bx-home-alt"></i> Flights</a>
                <i class="bx bx-chevron-right"></i>
                <a href="javascript:history.back()">Search Results</a>
                <i class="bx bx-chevron-right"></i>
                <span>Hold Booking</span>
            </nav>

            <form id="holdForm" action="{{ route('user.flights.hold.process') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="itinerary_id" value="{{ $itineraryId }}">

                <div class="row g-4">

                    {{-- ===================================================
                         LEFT COLUMN  (col-8)
                         =================================================== --}}
                    <div class="col-lg-8">

                        {{-- ── FLIGHT SUMMARY CARD ── --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bxs-plane hp-card__head-icon"></i>
                                <div>
                                    <div class="hp-card__eyebrow">Flight Details</div>
                                    <div class="hp-card__title">
                                        {{ strtoupper($searchParams['from'] ?? '') }}
                                        @if(count($legs) > 1) ⇄ @else → @endif
                                        {{ strtoupper($searchParams['to'] ?? '') }}
                                    </div>
                                </div>
                                <div class="hp-card__head-tags ms-auto">
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
                                        $segs  = $leg['segments'] ?? [];
                                        $s0    = $segs[0] ?? [];
                                        $sLast = !empty($segs) ? $segs[array_key_last($segs)] : [];
                                        $conn  = max(0, count($segs) - 1);
                                        $tech  = collect($segs)->sum(fn($s)=>(int)($s['stop_count']??0));
                                        $stops = $conn + $tech;
                                        $dur   = hd_fmt(isset($leg['elapsedTime'])?(int)$leg['elapsedTime']:null);
                                        $nextDay = (bool)($sLast['next_day_hint']??false);

                                        $midApts = [];
                                        for ($mi=0; $mi < count($segs)-1; $mi++) $midApts[] = $segs[$mi]['to']??'';
                                    @endphp

                                    <div class="hp-leg {{ $li > 0 ? 'hp-leg--sep' : '' }}">
                                        <div class="hp-leg__label">
                                            <i class="bx {{ $li===0 ? 'bxs-plane-take-off' : 'bxs-plane-land' }}"></i>
                                            {{ $li===0 ? ($isRoundTrip??false ? 'Outbound' : 'Flight') : 'Return' }}
                                        </div>
                                        <div class="hp-leg__row">
                                            <div class="hp-leg__airline">
                                                <div class="hp-leg__logo-wrap">
                                                    <img class="hp-leg__logo"
                                                        src="{{ hd_logo($s0['carrier']??'') }}"
                                                        loading="lazy" alt="{{ $s0['carrier_display']??'' }}"
                                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($s0['carrier']??'FL') }}&background=cd1b4f&color=fff&size=80'">
                                                </div>
                                                <div>
                                                    <div class="hp-leg__aname">{{ $s0['carrier_display']??($s0['carrier']??'') }}</div>
                                                    <div class="hp-leg__aflight">{{ strtoupper((string)($s0['carrier']??'')) }}{{ $s0['flight_number']??'' }}</div>
                                                </div>
                                            </div>

                                            <div class="hp-leg__pt">
                                                <div class="hp-leg__time">{{ $s0['departure_clock']??'—' }}</div>
                                                <div class="hp-leg__dt">{{ $s0['departure_weekday']??'' }}, {{ $s0['departure_label']??'' }}</div>
                                                <div class="hp-leg__city">{{ $s0['from']??'' }}@if(!empty($s0['departure_terminal'])), T{{ $s0['departure_terminal'] }}@endif</div>
                                            </div>

                                            <div class="hp-leg__mid">
                                                <div class="hp-leg__dur">{{ $dur }}</div>
                                                <div class="hp-leg__track">
                                                    <span class="hp-leg__dot"></span>
                                                    @foreach($midApts as $ma)<span class="hp-leg__via">{{ $ma }}</span>@endforeach
                                                    <span class="hp-leg__line"></span>
                                                    <span class="hp-leg__dot"></span>
                                                </div>
                                                <div class="hp-leg__stops {{ $stops===0?'hp-leg__stops--ok':'hp-leg__stops--via' }}">{{ hd_stops($stops) }}</div>
                                            </div>

                                            <div class="hp-leg__pt hp-leg__pt--arr">
                                                <div class="hp-leg__time">
                                                    {{ $sLast['arrival_clock']??'—' }}
                                                    @if($nextDay)<span class="hp-nextday">+1</span>@endif
                                                </div>
                                                <div class="hp-leg__dt">{{ $sLast['arrival_weekday']??'' }}, {{ $sLast['arrival_label']??'' }}</div>
                                                <div class="hp-leg__city">{{ $sLast['to']??'' }}@if(!empty($sLast['arrival_terminal'])), T{{ $sLast['arrival_terminal'] }}@endif</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ── PASSENGER INFORMATION ── --}}
                        @php $pIndex = 0; @endphp

                        @for ($i = 0; $i < $adults; $i++)
                            <div class="hp-card mb-3" id="pax-card-{{ $pIndex }}">
                                <div class="hp-card__head">
                                    <i class="bx bx-user hp-card__head-icon"></i>
                                    <div>
                                        <div class="hp-card__eyebrow">Passenger</div>
                                        <div class="hp-card__title">Adult {{ $i + 1 }} <span class="hp-card__age">12+ years</span></div>
                                    </div>
                                </div>

                                <div class="hp-pax-note"><i class="bx bx-info-circle"></i> Traveller's passport should be valid for at least 6 months from the date of travel.</div>

                                {{-- saved passenger selector --}}
                                @if(!empty($savedPassengers))
                                    <div class="hp-saved-row">
                                        <label class="hp-label">Load from saved passengers</label>
                                        <select class="hp-select hp-saved-pick" data-pax-idx="{{ $pIndex }}">
                                            <option value="">— Select saved passenger —</option>
                                            @foreach($savedPassengers as $sp)
                                                <option value="{{ json_encode($sp) }}">{{ $sp['title'] }} {{ $sp['first_name'] }} {{ $sp['last_name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="ADT">
                                <div class="row g-3 mt-1">
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
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][first_name]" placeholder="Enter first name" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][last_name]" placeholder="Enter last name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Date of Birth</label>
                                        <input type="date" class="hp-input" name="passengers[{{ $pIndex }}][dob]">
                                        <span class="hp-hint">Age calculated as per travel date</span>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Nationality</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][nationality]" placeholder="e.g. AE, PK, IN" maxlength="4">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="hp-label">Passport Number</label>
                                        <input type="text" class="hp-input" name="passengers[{{ $pIndex }}][passport_no]" placeholder="Passport number">
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
                                        <div class="hp-card__eyebrow">Passenger</div>
                                        <div class="hp-card__title">Child {{ $i + 1 }} <span class="hp-card__age">2–11 years</span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="C06">
                                <div class="row g-3 mt-1">
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
                                        <div class="hp-card__eyebrow">Passenger</div>
                                        <div class="hp-card__title">Infant {{ $i + 1 }} <span class="hp-card__age">Under 2 years</span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="passengers[{{ $pIndex }}][type]" value="INF">
                                <div class="row g-3 mt-1">
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
                                    <div class="hp-card__eyebrow">Lead Passenger</div>
                                    <div class="hp-card__title">Contact Details</div>
                                </div>
                            </div>

                            <p class="hp-contact-note"><i class="bx bx-info-circle"></i> These details will be shared with the airline for booking confirmation.</p>

                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="hp-label">Title <span class="hp-req">*</span></label>
                                    <select class="hp-select" name="lead[title]" required>
                                        <option value="Mr">Mr.</option>
                                        <option value="Mrs">Mrs.</option>
                                        <option value="Ms">Ms.</option>
                                        <option value="Dr">Dr.</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="hp-label">First Name <span class="hp-req">*</span></label>
                                    <input type="text" class="hp-input" name="lead[first_name]" placeholder="First name" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="hp-label">Last Name <span class="hp-req">*</span></label>
                                    <input type="text" class="hp-input" name="lead[last_name]" placeholder="Last name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="hp-label">Phone <span class="hp-req">*</span></label>
                                    <div class="hp-phone-wrap">
                                        <input type="tel" id="lead-phone" class="hp-input" placeholder="Phone number" required>
                                        <input type="hidden" name="lead[phone]" id="lead-phone-full">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="hp-label">Email <span class="hp-req">*</span></label>
                                    <input type="email" class="hp-input" name="lead[email]" placeholder="email@example.com" required>
                                </div>
                            </div>
                        </div>

                        {{-- ── HOLD BOOKING RULES ── --}}
                        <div class="hp-card mb-3">
                            <div class="hp-card__head">
                                <i class="bx bx-time-five hp-card__head-icon hp-card__head-icon--amber"></i>
                                <div>
                                    <div class="hp-card__eyebrow">Important</div>
                                    <div class="hp-card__title">Hold Booking Rules &amp; Restrictions</div>
                                </div>
                            </div>

                            <div class="hp-rules">
                                <div class="hp-rules__row">
                                    <span class="hp-rules__key">Advanced flight booking amount</span>
                                    <span class="hp-rules__val">
                                        <span class="dirham">AED</span> 0.00
                                    </span>
                                </div>
                                <div class="hp-rules__row">
                                    <span class="hp-rules__key">Ticketing Time Limit</span>
                                    <span class="hp-rules__val hp-rules__val--amber" id="hp-ttl">
                                        {{ now()->addHour()->format('d/m/Y h:i A') }}
                                        <span class="hp-rules__ttl-note">(indicative — confirmed once PNR is created)</span>
                                    </span>
                                </div>
                            </div>

                            <div class="hp-rules__alerts">
                                <div class="hp-rules__alert hp-rules__alert--warn">
                                    <i class="bx bx-error-circle"></i>
                                    <p>Your booking will be placed on hold as per standard policy of <strong>1 hour</strong>. We will update the exact time limit once we receive confirmation from the airline, or you may contact our support center.</p>
                                </div>
                                <div class="hp-rules__alert">
                                    <i class="bx bx-chevron-right"></i>
                                    <p><strong>ALL FARES &amp; SEATS ARE SUBJECT TO AVAILABILITY AT THE TIME OF BOOKING.</strong></p>
                                </div>
                                <div class="hp-rules__alert">
                                    <i class="bx bx-chevron-right"></i>
                                    <p>Creating a "Hold PNR" under any airline can lead to an ADM which the agency will be charged as per the airline's circular if it is <strong>Not Ticketed</strong> or if it is <strong>Auto Cancelled</strong> after hold.</p>
                                </div>
                                <div class="hp-rules__alert">
                                    <i class="bx bx-chevron-right"></i>
                                    <p>Advance flight booking amount will <strong>not be refunded</strong> if the booking gets cancelled before issuance. If the ticket is booked, the amount will be adjusted in the final fare. Hold PNR will only create a booking at the airline end — no tickets will be issued until you confirm ticketing. We are not responsible for any changes made at the airline end.</p>
                                </div>
                                <div class="hp-rules__alert hp-rules__alert--critical">
                                    <i class="bx bx-user-check"></i>
                                    <p>Kindly check the spelling and reconfirm passenger name(s) before booking. <strong>Ticket name changes are not permitted once issued.</strong> The above purchases are subject to cancellation, date change fees; once purchased, tickets are non-transferable and name changes are not permitted.</p>
                                </div>
                            </div>

                            {{-- HOLD SUBMIT BUTTON --}}
                            <div class="hp-submit-row">
                                <div class="hp-submit-price">
                                    <span class="hp-submit-label">Hold Amount</span>
                                    <span class="hp-submit-amount">
                                        <span class="dirham">AED</span> 0.00
                                    </span>
                                </div>
                                <button type="submit" class="hp-btn-hold" id="holdSubmitBtn">
                                    <i class="bx bx-time-five"></i>
                                    Hold Booking
                                </button>
                            </div>
                        </div>

                    </div>{{-- /.col-lg-8 --}}

                    {{-- ===================================================
                         RIGHT COLUMN  (col-4) — sticky fare summary
                         =================================================== --}}
                    <div class="col-lg-4">
                        <div class="hp-summary" id="hp-summary-sticky">

                            <div class="hp-summary__head">
                                <i class="bx bx-receipt"></i>
                                Fare Summary
                            </div>

                            <div class="hp-summary__body">

                                {{-- per pax type rows --}}
                                @if($adults > 0)
                                    @php $adultBase = $baseAmount > 0 ? round($baseAmount / $paxCount, 2) : 0; @endphp
                                    <div class="hp-sum-row">
                                        <span>Adult × {{ $adults }}</span>
                                        <span>
                                            <span class="dirham">AED</span>
                                            {{ number_format($adultBase * $adults, 2) }}
                                        </span>
                                    </div>
                                @endif
                                @if($children > 0)
                                    @php $childBase = $baseAmount > 0 ? round($baseAmount / $paxCount, 2) : 0; @endphp
                                    <div class="hp-sum-row">
                                        <span>Child × {{ $children }}</span>
                                        <span>
                                            <span class="dirham">AED</span>
                                            {{ number_format($childBase * $children, 2) }}
                                        </span>
                                    </div>
                                @endif
                                @if($infants > 0)
                                    @php $infantBase = $baseAmount > 0 ? round($baseAmount / $paxCount * 0.1, 2) : 0; @endphp
                                    <div class="hp-sum-row">
                                        <span>Infant × {{ $infants }}</span>
                                        <span>
                                            <span class="dirham">AED</span>
                                            {{ number_format($infantBase * $infants, 2) }}
                                        </span>
                                    </div>
                                @endif

                                @if($taxAmount > 0)
                                    <div class="hp-sum-row">
                                        <span>Taxes &amp; Fees</span>
                                        <span>
                                            <span class="dirham">AED</span>
                                            {{ number_format($taxAmount, 2) }}
                                        </span>
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
                                    <span class="dirham">AED</span>
                                    {{ number_format($totalAmount, 2) }}
                                </span>
                            </div>

                            <div class="hp-summary__meta">
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-user"></i>
                                    <span>{{ $adults }} Adult{{ $adults>1?'s':'' }}
                                        @if($children>0), {{ $children }} Child{{ $children>1?'ren':'' }}@endif
                                        @if($infants>0), {{ $infants }} Infant{{ $infants>1?'s':'' }}@endif
                                    </span>
                                </div>
                                <div class="hp-summary__meta-row">
                                    <i class="bx bx-map"></i>
                                    <span>{{ strtoupper($searchParams['from']??'') }} → {{ strtoupper($searchParams['to']??'') }}</span>
                                </div>
                                <div class="hp-summary__meta-row">
                                    <i class="bx bxs-calendar"></i>
                                    <span>{{ $searchParams['departure_date']??'' }}
                                        @if(!empty($searchParams['return_date'])) — {{ $searchParams['return_date'] }}@endif
                                    </span>
                                </div>
                            </div>

                            <button type="submit" form="holdForm" class="hp-btn-hold hp-btn-hold--full">
                                <i class="bx bx-time-five"></i>
                                Hold Booking &nbsp;·&nbsp; <span class="dirham">AED</span> 0.00
                            </button>

                            <div class="hp-summary__secure">
                                <i class="bx bxs-lock-alt"></i> Secure &amp; encrypted submission
                            </div>
                        </div>
                    </div>

                </div>{{-- /.row --}}
            </form>

        </div>
    </div>
@endsection

@push('css')
{{-- intl-tel-input --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/css/intlTelInput.min.css">
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
    --c-red-soft:    #fff0f3;
    --c-shadow:      0 2px 8px rgba(26,37,64,.07);
    --c-shadow-hov:  0 6px 22px rgba(26,37,64,.12);
    --mono: "JetBrains Mono", ui-monospace, Menlo, monospace;
    --sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    font-family: var(--sans);
    color: var(--c-ink);
    background: var(--c-bg);
    padding-bottom: 3rem;
}
.hp * { box-sizing: border-box; }
.hp a { text-decoration: none; }

/* =========================================================
   SHELL & BREADCRUMB
   ========================================================= */
.hp-shell { padding-top: 1.25rem; }

.hp-crumb {
    display: flex; align-items: center; gap: .4rem;
    font-size: .8rem; color: var(--c-muted);
    margin-bottom: 1.25rem;
}
.hp-crumb a { color: var(--c-brand); font-weight: 600; }
.hp-crumb a:hover { text-decoration: underline; }
.hp-crumb span { color: var(--c-ink); font-weight: 600; }

/* =========================================================
   CARD
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
    padding: .85rem 1.1rem;
    border-bottom: 1px solid var(--c-line);
    background: linear-gradient(135deg, rgba(205,27,79,.04) 0%, transparent 70%);
}
.hp-card__head-icon {
    font-size: 1.4rem; color: var(--c-brand); flex-shrink: 0;
}
.hp-card__head-icon--amber { color: var(--c-amber); }
.hp-card__eyebrow {
    font-size: .6rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--c-muted);
}
.hp-card__title {
    font-size: .96rem; font-weight: 700; color: var(--c-ink); margin-top: .05rem;
}
.hp-card__age {
    font-size: .72rem; font-weight: 500; color: var(--c-muted);
    background: var(--c-bg); padding: .08rem .45rem; border-radius: 4px;
    margin-left: .35rem; vertical-align: middle;
}

.hp-badge {
    font-size: .62rem; font-weight: 700; padding: .2rem .55rem;
    border-radius: 4px; text-transform: uppercase; letter-spacing: .06em;
}
.hp-badge--ref { background: var(--c-green-soft); color: var(--c-green); }
.hp-badge--nr  { background: var(--c-red-soft); color: #c0143c; }

/* =========================================================
   FLIGHT SUMMARY
   ========================================================= */
.hp-flight { padding: 0; }

.hp-leg { padding: .85rem 1.1rem; }
.hp-leg--sep { border-top: 1px dashed var(--c-line); background: #fafbff; }

.hp-leg__label {
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
    color: var(--c-brand); margin-bottom: .55rem;
    display: flex; align-items: center; gap: .3rem;
}
.hp-leg__row {
    display: grid;
    grid-template-columns: 165px 1fr auto 1fr;
    align-items: center;
    gap: .5rem 1rem;
}

.hp-leg__airline { display: flex; gap: .6rem; align-items: center; min-width: 0; }
.hp-leg__logo-wrap {
    width: 46px; height: 46px; flex-shrink: 0; border-radius: 10px;
    border: 1.5px solid var(--c-line); background: #fff;
    box-shadow: 0 1px 5px rgba(26,37,64,.07);
    display: flex; align-items: center; justify-content: center; padding: 3px; overflow: hidden;
}
.hp-leg__logo { width: 100%; height: 100%; object-fit: contain; }
.hp-leg__aname { font-size: .83rem; font-weight: 700; color: var(--c-ink); line-height: 1.25; }
.hp-leg__aflight { font-family: var(--mono); font-size: .68rem; color: var(--c-muted); margin-top: .06rem; }

.hp-leg__pt { min-width: 0; }
.hp-leg__pt--arr { text-align: right; }
.hp-leg__time { font-family: var(--mono); font-size: 1.28rem; font-weight: 700; color: var(--c-ink); line-height: 1; }
.hp-leg__dt   { font-size: .68rem; color: var(--c-muted); margin-top: .15rem; white-space: nowrap; }
.hp-leg__city { font-size: .73rem; color: var(--c-slate); font-weight: 500; margin-top: .04rem; }
.hp-nextday {
    font-size: .57rem; font-weight: 700; background: var(--c-amber-soft); color: var(--c-amber);
    padding: .04rem .3rem; border-radius: 4px; margin-left: .2rem; font-family: var(--sans);
}

.hp-leg__mid { display: flex; flex-direction: column; align-items: center; gap: .2rem; }
.hp-leg__dur { font-size: .7rem; font-weight: 600; color: var(--c-slate); font-family: var(--mono); }
.hp-leg__track { display: flex; align-items: center; gap: .2rem; width: 100%; }
.hp-leg__dot { width: 6px; height: 6px; border-radius: 50%; background: var(--c-brand); flex-shrink: 0; }
.hp-leg__line { flex: 1; height: 1px; background: var(--c-muted); opacity: .35; }
.hp-leg__via {
    font-family: var(--mono); font-size: .58rem; font-weight: 700;
    color: #fff; background: var(--c-amber); padding: .07rem .3rem; border-radius: 4px; flex-shrink: 0;
}
.hp-leg__stops {
    font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
    padding: .1rem .42rem; border-radius: 4px;
}
.hp-leg__stops--ok  { background: var(--c-green-soft); color: var(--c-green); }
.hp-leg__stops--via { background: var(--c-amber-soft); color: var(--c-amber); }

/* =========================================================
   PASSENGER FORM
   ========================================================= */
.hp-card .row { padding: 1rem 1.1rem; }

.hp-pax-note {
    margin: 0; padding: .55rem 1.1rem;
    font-size: .75rem; color: var(--c-amber);
    background: var(--c-amber-soft);
    border-top: 1px solid rgba(217,119,6,.15);
    display: flex; align-items: flex-start; gap: .4rem;
}
.hp-pax-note i { font-size: .9rem; flex-shrink: 0; margin-top: .05rem; }

.hp-saved-row { padding: .75rem 1.1rem 0; }

.hp-label {
    font-size: .72rem; font-weight: 700; color: var(--c-slate);
    display: block; margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .06em;
}
.hp-req { color: var(--c-brand); }
.hp-hint { font-size: .65rem; color: var(--c-muted); margin-top: .2rem; display: block; }

.hp-input {
    width: 100%; padding: .58rem .75rem;
    border: 1.5px solid var(--c-line);
    border-radius: 8px; font: inherit; font-size: .86rem;
    color: var(--c-ink); background: #fff;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.hp-input:focus {
    border-color: var(--c-brand);
    box-shadow: 0 0 0 3px rgba(205,27,79,.1);
}

.hp-select {
    width: 100%; padding: .58rem .75rem;
    border: 1.5px solid var(--c-line); border-radius: 8px;
    font: inherit; font-size: .86rem; color: var(--c-ink); background: #fff;
    transition: border-color .15s; outline: none; appearance: auto;
}
.hp-select:focus { border-color: var(--c-brand); box-shadow: 0 0 0 3px rgba(205,27,79,.1); }

.hp-save-check {
    display: flex; align-items: center; gap: .5rem;
    font-size: .78rem; color: var(--c-slate); cursor: pointer;
    padding: .45rem .7rem;
    background: var(--c-bg);
    border: 1px dashed var(--c-line);
    border-radius: 8px;
}
.hp-save-check input[type=checkbox] { accent-color: var(--c-brand); width: 15px; height: 15px; flex-shrink: 0; }

/* =========================================================
   PHONE (intl-tel-input)
   ========================================================= */
.hp-phone-wrap { position: relative; }
.hp-phone-wrap .iti { width: 100%; }
.hp-phone-wrap .iti input {
    width: 100%; padding: .58rem .75rem !important;
    border: 1.5px solid var(--c-line) !important;
    border-radius: 8px !important; font-size: .86rem !important;
    color: var(--c-ink) !important;
    transition: border-color .15s !important;
}
.hp-phone-wrap .iti input:focus {
    border-color: var(--c-brand) !important;
    box-shadow: 0 0 0 3px rgba(205,27,79,.1) !important;
    outline: none !important;
}

/* =========================================================
   CONTACT NOTE
   ========================================================= */
.hp-contact-note {
    margin: 0; padding: .5rem 1.1rem;
    font-size: .74rem; color: var(--c-slate);
    background: var(--c-bg);
    border-bottom: 1px solid var(--c-line);
    display: flex; align-items: center; gap: .4rem;
}
.hp-contact-note i { color: var(--c-brand); }

/* =========================================================
   HOLD RULES
   ========================================================= */
.hp-rules {
    margin: 0; padding: .75rem 1.1rem;
    background: var(--c-bg);
    border-bottom: 1px solid var(--c-line);
    display: flex; flex-direction: column; gap: .45rem;
}
.hp-rules__row {
    display: flex; justify-content: space-between; align-items: baseline;
    gap: .5rem; flex-wrap: wrap;
}
.hp-rules__key { font-size: .78rem; font-weight: 600; color: var(--c-slate); }
.hp-rules__val { font-size: .82rem; font-weight: 700; color: var(--c-ink); font-family: var(--mono); }
.hp-rules__val--amber { color: var(--c-amber); }
.hp-rules__ttl-note { font-family: var(--sans); font-size: .65rem; font-weight: 400; color: var(--c-muted); margin-left: .35rem; }

.hp-rules__alerts { padding: .75rem 1.1rem; display: flex; flex-direction: column; gap: .55rem; }
.hp-rules__alert {
    display: flex; gap: .55rem; align-items: flex-start;
    font-size: .78rem; color: var(--c-slate); line-height: 1.5;
    padding: .6rem .8rem;
    background: #f8fafd;
    border: 1px solid var(--c-line);
    border-radius: 8px;
}
.hp-rules__alert i { font-size: .9rem; color: var(--c-muted); flex-shrink: 0; margin-top: .12rem; }
.hp-rules__alert p { margin: 0; }
.hp-rules__alert--warn {
    background: var(--c-amber-soft);
    border-color: rgba(217,119,6,.25);
}
.hp-rules__alert--warn i { color: var(--c-amber); }
.hp-rules__alert--critical {
    background: var(--c-red-soft);
    border-color: rgba(205,27,79,.2);
}
.hp-rules__alert--critical i { color: var(--c-brand); }

/* hold submit strip */
.hp-submit-row {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .75rem;
    padding: .85rem 1.1rem;
    background: linear-gradient(135deg, rgba(205,27,79,.05) 0%, transparent 70%);
    border-top: 1px solid var(--c-line);
}
.hp-submit-label {
    font-size: .6rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--c-brand); display: block; margin-bottom: .05rem;
}
.hp-submit-amount {
    font-family: var(--mono); font-size: 1.3rem; font-weight: 700; color: var(--c-brand);
}

/* =========================================================
   BUTTONS
   ========================================================= */
.hp-btn-hold {
    display: inline-flex; align-items: center; gap: .35rem;
    background: linear-gradient(180deg, var(--c-amber) 0%, #b45309 100%);
    color: #fff !important; font: inherit; font-size: .9rem; font-weight: 700;
    padding: .7rem 1.6rem; border: none; border-radius: 9px; cursor: pointer;
    box-shadow: 0 5px 16px rgba(217,119,6,.3);
    transition: transform .13s, box-shadow .13s;
    white-space: nowrap;
}
.hp-btn-hold:hover { transform: translateY(-1px); box-shadow: 0 9px 24px rgba(217,119,6,.4); }
.hp-btn-hold--full { width: 100%; justify-content: center; margin-top: .8rem; }

/* =========================================================
   FARE SUMMARY (sticky right)
   ========================================================= */
.hp-summary {
    background: var(--c-white);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    box-shadow: var(--c-shadow);
    overflow: hidden;
    position: sticky;
    top: 90px;
}
.hp-summary__head {
    padding: .85rem 1.1rem;
    background: linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color: #fff; font-size: .95rem; font-weight: 700;
    display: flex; align-items: center; gap: .5rem;
}
.hp-summary__head i { font-size: 1.15rem; }
.hp-summary__body { padding: .75rem 1.1rem; border-bottom: 1px solid var(--c-line); display: flex; flex-direction: column; gap: .4rem; }

.hp-sum-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .82rem; color: var(--c-slate);
}
.hp-sum-row span:last-child { font-family: var(--mono); font-weight: 600; color: var(--c-ink); }
.hp-sum-row--sep { border-top: 1px dashed var(--c-line); padding-top: .4rem; margin-top: .15rem; }
.hp-sum-free { font-size: .72rem; font-weight: 700; background: var(--c-green-soft); color: var(--c-green); padding: .1rem .42rem; border-radius: 4px; }

.hp-summary__total {
    display: flex; justify-content: space-between; align-items: center;
    padding: .85rem 1.1rem;
    font-size: .88rem; font-weight: 700; color: var(--c-ink);
    border-bottom: 1px solid var(--c-line);
}
.hp-summary__total-amount {
    font-family: var(--mono); font-size: 1.28rem; font-weight: 700;
    color: var(--c-brand);
    display: flex; align-items: baseline; gap: .06rem;
}

.hp-summary__meta { padding: .65rem 1.1rem; border-bottom: 1px solid var(--c-line); display: flex; flex-direction: column; gap: .3rem; }
.hp-summary__meta-row { display: flex; align-items: center; gap: .45rem; font-size: .76rem; color: var(--c-slate); }
.hp-summary__meta-row i { color: var(--c-brand); font-size: .9rem; flex-shrink: 0; }

.hp-summary__secure {
    padding: .55rem 1.1rem;
    font-size: .7rem; color: var(--c-muted); text-align: center;
    display: flex; align-items: center; justify-content: center; gap: .3rem;
}
.hp-summary__secure i { color: var(--c-green); }

/* dirham */
.dirham {
    font-family: "UAEDirham", "Segoe UI", sans-serif;
    font-size: .8em; font-weight: 400; color: inherit;
    margin-right: .04rem; vertical-align: baseline;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 991px) {
    .hp-leg__row { grid-template-columns: 1fr 1fr; grid-template-rows: auto auto; }
    .hp-leg__airline { grid-column: 1 / -1; }
    .hp-leg__mid { grid-column: 1 / -1; flex-direction: row; justify-content: center; }
    .hp-leg__track { flex: 1; max-width: 220px; }
    .hp-leg__pt--arr { text-align: left; }
    .hp-summary { position: static; }
}
</style>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/js/intlTelInput.min.js"></script>
<script>
(function () {
    /* ── intl-tel-input ── */
    const phoneEl = document.getElementById('lead-phone');
    const phoneFull = document.getElementById('lead-phone-full');

    if (phoneEl) {
        const iti = window.intlTelInput(phoneEl, {
            initialCountry: 'ae',
            separateDialCode: true,
            preferredCountries: ['ae', 'sa', 'pk', 'in', 'gb', 'us'],
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/js/utils.js',
        });

        // copy full number into hidden input before submit
        document.getElementById('holdForm').addEventListener('submit', function (e) {
            if (phoneFull) phoneFull.value = iti.getNumber();
        });
    }

    /* ── saved passenger auto-fill ── */
    document.querySelectorAll('.hp-saved-pick').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const idx = this.dataset.paxIdx;
            if (!this.value) return;

            let pax;
            try { pax = JSON.parse(this.value); } catch (e) { return; }

            function fill(name, val) {
                const el = document.querySelector('[name="passengers[' + idx + '][' + name + ']"]');
                if (!el || !val) return;
                el.value = val;
            }

            fill('title',        pax.title);
            fill('first_name',   pax.first_name);
            fill('last_name',    pax.last_name);
            fill('dob',          pax.dob          ? pax.dob.substring(0, 10) : '');
            fill('nationality',  pax.nationality);
            fill('passport_no',  pax.passport_no);
            fill('passport_exp', pax.passport_exp ? pax.passport_exp.substring(0, 10) : '');
        });
    });

    /* ── submit guard: disable button on submit to prevent double clicks ── */
    document.getElementById('holdForm').addEventListener('submit', function () {
        document.querySelectorAll('.hp-btn-hold').forEach(function (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing…';
        });
    });
})();
</script>
@endpush
