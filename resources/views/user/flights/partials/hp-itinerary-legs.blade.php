@foreach ($legs as $li => $leg)
    @php
        $segs  = $leg['segments'] ?? [];
        $s0    = $segs[0] ?? [];
        $sLast = !empty($segs) ? $segs[array_key_last($segs)] : [];
        $conn  = max(0, count($segs) - 1);
        $tech  = collect($segs)->sum(fn($s) => (int)($s['stop_count'] ?? 0));
        $stops = $conn + $tech;
        $et    = isset($leg['elapsedTime']) ? (int) $leg['elapsedTime'] : null;
        if ($et && $et >= 1) {
            $h = intdiv($et, 60); $r = $et % 60;
            $dur = $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$r}m";
        } else { $dur = ' - '; }
        $nextDay = (bool)($sLast['next_day_hint'] ?? false);
        $midApts = [];
        for ($mi = 0; $mi < count($segs) - 1; $mi++) {
            $midApts[] = $segs[$mi]['to'] ?? '';
        }
        $car = strtoupper(trim((string)($s0['carrier'] ?? 'XX')));
    @endphp
    <div class="hp-leg {{ $li > 0 ? 'hp-leg--ret' : '' }}">
        <div class="hp-leg__tag">
            <i class="bx {{ $li === 0 ? 'bxs-plane-take-off' : 'bxs-plane-land' }}"></i>
            <span>{{ $li === 0 ? ($isRound ? 'Outbound' : 'Flight') : 'Return' }}</span>
        </div>
        <div class="hp-leg__row">
            <div class="hp-leg__airline">
                <div class="hp-leg__logo-wrap">
                    <img class="hp-leg__logo"
                         src="https://pics.avs.io/80/80/{{ $car }}.png"
                         loading="lazy" alt="{{ $s0['carrier_display'] ?? '' }}"
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($s0['carrier'] ?? 'FL') }}&background=cd1b4f&color=fff&size=80'">
                </div>
                <div>
                    <div class="hp-leg__aname">{{ $s0['carrier_display'] ?? ($s0['carrier'] ?? '') }}</div>
                    <div class="hp-leg__aflight">{{ strtoupper((string)($s0['carrier'] ?? '')) }}{{ $s0['flight_number'] ?? '' }}</div>
                </div>
            </div>
            <div class="hp-leg__pt">
                <div class="hp-leg__time">{{ $s0['departure_clock'] ?? ($s0['departure_time'] ?? ' - ') }}</div>
                <div class="hp-leg__dt">{{ $s0['departure_weekday'] ?? '' }}{{ !empty($s0['departure_weekday']) && !empty($s0['departure_label']) ? ', ' : '' }}{{ $s0['departure_label'] ?? '' }}</div>
                <div class="hp-leg__city">{{ $s0['from'] ?? '' }}@if(!empty($s0['departure_terminal'])), T{{ $s0['departure_terminal'] }}@endif</div>
            </div>
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
                    <div class="hp-leg__bridge-stop hp-leg__bridge-stop--via">{{ $stops === 1 ? '1 Stop' : $stops.' Stops' }}</div>
                @endif
            </div>
            <div class="hp-leg__pt hp-leg__pt--arr">
                <div class="hp-leg__time">
                    {{ $sLast['arrival_clock'] ?? ($sLast['arrival_time'] ?? ' - ') }}
                    @if($nextDay)<span class="hp-nextday">+1</span>@endif
                </div>
                <div class="hp-leg__dt">{{ $sLast['arrival_weekday'] ?? '' }}{{ !empty($sLast['arrival_weekday']) && !empty($sLast['arrival_label']) ? ', ' : '' }}{{ $sLast['arrival_label'] ?? '' }}</div>
                <div class="hp-leg__city">{{ $sLast['to'] ?? '' }}@if(!empty($sLast['arrival_terminal'])), T{{ $sLast['arrival_terminal'] }}@endif</div>
            </div>
        </div>
    </div>
@endforeach
