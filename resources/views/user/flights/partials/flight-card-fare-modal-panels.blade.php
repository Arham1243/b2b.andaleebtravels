@if(count($fareOptions) > 1)
    <div class="fd-fare-tabs">
        @foreach($fareOptions as $fi => $fare)
            @php $tabBrand = trim((string) ($fare['fare_brand'] ?? ('Fare ' . ($fi + 1)))); @endphp
            <button type="button"
                class="fd-fare-tab {{ $fi === 0 ? 'fd-fare-tab--active' : '' }}"
                data-fd-fare-tab="{{ $fi }}">
                {{ $tabBrand }}
            </button>
        @endforeach
    </div>
@endif

@foreach($fareOptions as $fi => $fare)
    @php
        $bagDetails = $fare['baggage_details'] ?? [];
        $fareRules  = $fare['fare_rules'] ?? [];
        $nonRefund  = (bool) ($fare['non_refundable'] ?? false);
        $fareBrand  = trim((string) ($fare['fare_brand'] ?? ''));
        $bagNote    = $fare['baggage_notes'] ?? '';
        $checkedRows = $bagDetails['checked'] ?? [];
        $cabinRows = $bagDetails['cabin'] ?? [];
        $paxTable = $bagDetails['pax_table'] ?? [];
        $cabinNotes = $bagDetails['cabin_notes'] ?? [];
    @endphp

    <div class="fd-panel fd-panel--hidden fd-fare-panel {{ $fi === 0 ? 'fd-fare-panel--active' : '' }}"
        data-fd-panel="baggage"
        data-fd-fare-panel="{{ $fi }}">
        <div class="fd-bag">
            @if(!empty($paxTable))
                <div class="fd-bag__section">
                    <div class="fd-bag__section-title">Baggage Allowance</div>
                    <div class="fd-bag__table-wrap">
                        <table class="fd-bag__table">
                            <thead>
                                <tr>
                                    <th>Pax Type</th>
                                    <th>Check-in Baggage</th>
                                    <th>Cabin Baggage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paxTable as $paxRow)
                                    <tr>
                                        <td>{{ $paxRow['pax_type'] ?? 'Passenger' }}</td>
                                        <td>
                                            @include('user.flights.partials.baggage-allowance-display', [
                                                'friendly' => $paxRow['checked_friendly'] ?? null,
                                                'fallback' => $paxRow['checked'] ?? 'Not included',
                                            ])
                                        </td>
                                        <td>
                                            @include('user.flights.partials.baggage-allowance-display', [
                                                'friendly' => $paxRow['cabin_friendly'] ?? null,
                                                'fallback' => $paxRow['cabin'] ?? 'Not included',
                                            ])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($cabinNotes))
                        <div class="fd-bag__footnote">
                            <i class="bx bx-info-circle"></i>
                            <span>{{ implode(' · ', $cabinNotes) }}</span>
                        </div>
                    @endif
                </div>
            @elseif(!empty($bagNote))
                <div class="fd-bag__row">
                    <i class="bx bx-briefcase-alt-2 fd-bag__icon"></i>
                    <div>
                        <div class="fd-bag__label">Checked Baggage</div>
                        <div class="fd-bag__val">{{ $bagNote }}</div>
                    </div>
                </div>
            @endif

            @if(!empty($checkedRows))
                <div class="fd-bag__section">
                    <div class="fd-bag__section-title">Checked Baggage</div>
                    @foreach($checkedRows as $bagRow)
                        <div class="fd-bag__row">
                            <i class="bx bx-briefcase-alt-2 fd-bag__icon"></i>
                            <div>
                                <div class="fd-bag__label">{{ $bagRow['route'] ?? 'Segment' }}</div>
                                <div class="fd-bag__val">
                                    @include('user.flights.partials.baggage-allowance-display', [
                                        'friendly' => $bagRow['friendly'] ?? null,
                                        'fallback' => $bagRow['allowance'] ?? 'Not included',
                                    ])
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($cabinRows))
                <div class="fd-bag__section">
                    <div class="fd-bag__section-title">Cabin / Hand Baggage</div>
                    @foreach($cabinRows as $bagRow)
                        <div class="fd-bag__row">
                            <i class="bx bx-shopping-bag fd-bag__icon"></i>
                            <div>
                                <div class="fd-bag__label">{{ $bagRow['route'] ?? 'Segment' }}</div>
                                <div class="fd-bag__val">
                                    @include('user.flights.partials.baggage-allowance-display', [
                                        'friendly' => $bagRow['friendly'] ?? null,
                                        'fallback' => $bagRow['allowance'] ?? 'Not included',
                                    ])
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="fd-panel fd-panel--hidden fd-fare-panel {{ $fi === 0 ? 'fd-fare-panel--active' : '' }}"
        data-fd-panel="fare-rules"
        data-fd-fare-panel="{{ $fi }}">
        <div class="fd-rules">
            @include('user.flights.partials.fare-rules-summary', [
                'fareRules' => $fareRules,
                'fareBrand' => $fareBrand,
                'nonRefund' => $nonRefund,
                'routeLabel' => ($legRoutes[0]['from'] ?? '') . ' → ' . ($legRoutes[0]['to'] ?? ''),
            ])
            @include('user.flights.partials.fare-rules-full')
        </div>
    </div>
@endforeach
