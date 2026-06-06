@php
    $breakdown = $breakdown ?? flightFareBreakdown($itinerary ?? [], (float) ($fallbackTotal ?? 0));
    $currency = $breakdown['currency'] ?? 'AED';
    $adults = (int) ($adults ?? 1);
    $children = (int) ($children ?? 0);
    $infants = (int) ($infants ?? 0);
    $paxCount = max(1, $adults + $children + $infants);
    $baseAmount = (float) ($breakdown['base_fare'] ?? 0);
    $taxAmount = (float) ($breakdown['tax_charges'] ?? 0);
    $totalAmount = (float) ($breakdown['total_amount'] ?? 0);
@endphp

@if ($breakdown['has_breakdown'] ?? false)
    <div class="hp-sum-row">
        <span>Base Fare</span>
        <span><span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['base_fare'], 2) }}</span>
    </div>

    @if ($breakdown['show_discount'] ?? false)
        <div class="hp-sum-row hp-sum-row--discount">
            <span>Agency Discount</span>
            <span class="hp-sum-row__credit">− <span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['discount'], 2) }}</span>
        </div>
    @endif

    <div class="hp-sum-row">
        <span>Tax &amp; Charges</span>
        <span><span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['tax_charges'], 2) }}</span>
    </div>

    @if ($breakdown['show_you_earn'] ?? false)
        <div class="hp-sum-row hp-sum-row--earn">
            <span>You Earn</span>
            <span class="hp-sum-row__credit"><span class="dirham">{{ $currency }}</span> {{ number_format($breakdown['you_earn'], 2) }}</span>
        </div>
    @endif
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
