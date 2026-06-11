@php
    $breakdown = $breakdown ?? flightFareBreakdown(
        $itinerary ?? [],
        (float) ($fallbackTotal ?? 0),
        (int) ($adults ?? 1),
        (int) ($children ?? 0),
        (int) ($infants ?? 0),
    );
    $currency = $breakdown['currency'] ?? 'AED';
    $baseLines = $breakdown['base_lines'] ?? [];
    $taxLines = $breakdown['tax_lines'] ?? [];
    $hasPaxLines = (bool) ($breakdown['has_pax_lines'] ?? false);
    $showBreakdown = (bool) ($breakdown['has_breakdown'] ?? false);
@endphp

@if ($showBreakdown && $hasPaxLines)
    <div class="hp-fare-acc">
        <details class="hp-fare-acc__item">
            <summary class="hp-fare-acc__head">
                <span class="hp-fare-acc__label">
                    <span>Base Fare</span>
                    <i class="bx bx-chevron-down hp-fare-acc__chev" aria-hidden="true"></i>
                </span>
                <span class="hp-fare-acc__amount">
                    <span class="dirham">{{ $currency }}</span> {{ number_format((float) ($breakdown['base_fare'] ?? 0), 2) }}
                </span>
            </summary>
            <div class="hp-fare-acc__body">
                @foreach ($baseLines as $line)
                    <div class="hp-fare-acc__line">
                        <span>{{ $line['label'] }} × {{ $line['count'] }} × <span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['unit'], 2) }}</span>
                        <span><span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['total'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        </details>

        @if ($breakdown['show_discount'] ?? false)
            <div class="hp-sum-row hp-sum-row--discount">
                <span>Agency Discount</span>
                <span class="hp-sum-row__credit">− <span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['discount'], 2) }}</span>
            </div>
        @endif

        <details class="hp-fare-acc__item">
            <summary class="hp-fare-acc__head">
                <span class="hp-fare-acc__label">
                    <span>Tax &amp; Charges</span>
                    <i class="bx bx-chevron-down hp-fare-acc__chev" aria-hidden="true"></i>
                </span>
                <span class="hp-fare-acc__amount">
                    <span class="dirham">{{ $currency }}</span> {{ number_format((float) ($breakdown['tax_charges'] ?? 0), 2) }}
                </span>
            </summary>
            <div class="hp-fare-acc__body">
                @foreach ($taxLines as $line)
                    <div class="hp-fare-acc__line">
                        <span>{{ $line['label'] }} × {{ $line['count'] }} × <span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['unit'], 2) }}</span>
                        <span><span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['total'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        </details>

        @if ($breakdown['show_you_earn'] ?? false)
            <div class="hp-sum-row hp-sum-row--earn">
                <span>You Earn</span>
                <span class="hp-sum-row__credit"><span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['you_earn'], 2) }}</span>
            </div>
        @endif
    </div>
@elseif ($showBreakdown)
    <div class="hp-fare-acc">
        <details class="hp-fare-acc__item">
            <summary class="hp-fare-acc__head">
                <span class="hp-fare-acc__label">
                    <span>Base Fare</span>
                    <i class="bx bx-chevron-down hp-fare-acc__chev" aria-hidden="true"></i>
                </span>
                <span class="hp-fare-acc__amount">
                    <span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['base_fare'], 2) }}
                </span>
            </summary>
        </details>

        @if ($breakdown['show_discount'] ?? false)
            <div class="hp-sum-row hp-sum-row--discount">
                <span>Agency Discount</span>
                <span class="hp-sum-row__credit">− <span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['discount'], 2) }}</span>
            </div>
        @endif

        <details class="hp-fare-acc__item">
            <summary class="hp-fare-acc__head">
                <span class="hp-fare-acc__label">
                    <span>Tax &amp; Charges</span>
                    <i class="bx bx-chevron-down hp-fare-acc__chev" aria-hidden="true"></i>
                </span>
                <span class="hp-fare-acc__amount">
                    <span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['tax_charges'], 2) }}
                </span>
            </summary>
        </details>

        @if ($breakdown['show_you_earn'] ?? false)
            <div class="hp-sum-row hp-sum-row--earn">
                <span>You Earn</span>
                <span class="hp-sum-row__credit"><span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['you_earn'], 2) }}</span>
            </div>
        @endif
    </div>
@else
    @php
        $adults = (int) ($adults ?? 1);
        $children = (int) ($children ?? 0);
        $infants = (int) ($infants ?? 0);
        $paxCount = max(1, $adults + $children + $infants);
        $baseAmount = (float) ($breakdown['base_fare'] ?? 0);
        $taxAmount = (float) ($breakdown['tax_charges'] ?? 0);
        $fallbackBaseLines = $baseLines;
        $fallbackTaxLines = $taxLines;
        if ($fallbackBaseLines === [] && $hasPaxLines === false) {
            $fallbackBaseLines = $breakdown['base_lines'] ?? [];
            $fallbackTaxLines = $breakdown['tax_lines'] ?? [];
        }
    @endphp

    @if ($fallbackBaseLines !== [])
        <div class="hp-fare-acc">
            <details class="hp-fare-acc__item">
                <summary class="hp-fare-acc__head">
                    <span class="hp-fare-acc__label">
                        <span>Base Fare</span>
                        <i class="bx bx-chevron-down hp-fare-acc__chev" aria-hidden="true"></i>
                    </span>
                    <span class="hp-fare-acc__amount">
                        <span class="dirham">{{ $currency }}</span> {{ number_format($baseAmount, 2) }}
                    </span>
                </summary>
                <div class="hp-fare-acc__body">
                    @foreach ($fallbackBaseLines as $line)
                        <div class="hp-fare-acc__line">
                            <span>{{ $line['label'] }} × {{ $line['count'] }} × <span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['unit'], 2) }}</span>
                            <span><span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['total'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </details>

            @if ($taxAmount > 0)
                <details class="hp-fare-acc__item">
                    <summary class="hp-fare-acc__head">
                        <span class="hp-fare-acc__label">
                            <span>Tax &amp; Charges</span>
                            <i class="bx bx-chevron-down hp-fare-acc__chev" aria-hidden="true"></i>
                        </span>
                        <span class="hp-fare-acc__amount">
                            <span class="dirham">{{ $currency }}</span> {{ number_format($taxAmount, 2) }}
                        </span>
                    </summary>
                    <div class="hp-fare-acc__body">
                        @foreach ($fallbackTaxLines as $line)
                            <div class="hp-fare-acc__line">
                                <span>{{ $line['label'] }} × {{ $line['count'] }} × <span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['unit'], 2) }}</span>
                                <span><span class="dirham">{{ $currency }}</span> {{ number_format((float) $line['total'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    @else
        @php $adultBase = $adults > 0 ? round($baseAmount / $paxCount, 2) : 0; @endphp
        @if ($adults > 0)
            <div class="hp-sum-row">
                <span>Adult × {{ $adults }}</span>
                <span><span class="dirham">{{ $currency }}</span> {{ number_format($adultBase * $adults, 2) }}</span>
            </div>
        @endif
        @if ($children > 0)
            @php $childBase = round($baseAmount / $paxCount, 2); @endphp
            <div class="hp-sum-row">
                <span>Child × {{ $children }}</span>
                <span><span class="dirham">{{ $currency }}</span> {{ number_format($childBase * $children, 2) }}</span>
            </div>
        @endif
        @if ($infants > 0)
            @php $infantBase = round($baseAmount / $paxCount * 0.1, 2); @endphp
            <div class="hp-sum-row">
                <span>Infant × {{ $infants }}</span>
                <span><span class="dirham">{{ $currency }}</span> {{ number_format($infantBase * $infants, 2) }}</span>
            </div>
        @endif
        @if ($taxAmount > 0)
            <div class="hp-sum-row">
                <span>Taxes &amp; Fees</span>
                <span><span class="dirham">{{ $currency }}</span> {{ number_format($taxAmount, 2) }}</span>
            </div>
        @endif
    @endif
@endif
