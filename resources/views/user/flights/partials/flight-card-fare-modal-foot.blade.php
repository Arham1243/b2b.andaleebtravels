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
