@php
    $travelportExpanded = ($cardSupplier ?? '') === 'travelport' && ($result['travelport_fares_expanded'] ?? false);
    $travelportNeedsLoad = ($cardSupplier ?? '') === 'travelport' && ! $travelportExpanded;
    $defaultVisibleFares = $travelportExpanded
        ? count($fareOptions)
        : ((($cardSupplier ?? '') === 'travelport') ? 2 : 3);
    $extraFareCount = $travelportNeedsLoad ? 0 : max(0, count($fareOptions) - $defaultVisibleFares);
@endphp

@foreach ($fareOptions as $fi => $fare)
    @php
        $fareRulesRow = $fare['fare_rules'] ?? [];
        $nonRefund  = array_key_exists('refundable', $fareRulesRow)
            ? ! (bool) $fareRulesRow['refundable']
            : (bool) ($fare['non_refundable'] ?? false);
        $bagNote    = $fare['baggage_notes'] ?? '';
        $farePrice  = (float) ($fare['totalPrice'] ?? 0);
        $fareCur    = strtoupper((string) ($fare['currency'] ?? $cardCur));
        $fareChannel = flightFareChannel($fare);
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
        data-rc-fare-row="{{ $fi }}"
        data-rc-fare-channel="{{ $fareChannel }}"
        data-rc-fare-price="{{ $farePrice }}">
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

@if($travelportNeedsLoad)
    <button type="button"
        class="rc__more-fares rc__more-fares--travelport"
        data-rc-travelport-more-fares
        data-itinerary-id="{{ $lid }}"
        aria-expanded="false">
        <span class="rc__more-fares__label">View More Fares</span>
        <i class="bx bx-chevron-down"></i>
    </button>
@elseif($extraFareCount > 0)
    <button type="button"
        class="rc__more-fares"
        data-rc-more-fares
        aria-expanded="false">
        <span class="rc__more-fares__label">+{{ $extraFareCount }} More Fare{{ $extraFareCount === 1 ? '' : 's' }}</span>
        <i class="bx bx-chevron-down"></i>
    </button>
@endif
