@php
    $legRow = $legRow ?? [];
    $fareCabinTags = flightFareRowCabinLabels($legRow['cabin'] ?? null, $legRow['booking'] ?? null);
    $nonRefund = (bool) ($legRow['non_refundable'] ?? false);
    $fareSeats = $legRow['seats'] ?? null;
    $bagPills = $legRow['bag_pills'] ?? [];
@endphp
@if(($legRow['tag'] ?? '') !== '')
    <span class="rc__leg-tag rc__leg-tag--{{ strtolower($legRow['tag']) }}" title="{{ $legRow['tag_title'] ?? '' }}">{{ $legRow['tag'] }}</span>
@endif
@if(trim((string) ($legRow['brand'] ?? '')) !== '')
    <span class="rc__fbadge rc__fbadge--brand">{{ $legRow['brand'] }}</span>
@endif
@if(trim((string) ($legRow['basis'] ?? '')) !== '')
    <span class="rc__ftag rc__ftag--basis">({{ $legRow['basis'] }})</span>
@endif
@if($fareCabinTags['cabin'] !== '')
    <span class="rc__ftag">{{ $fareCabinTags['cabin'] }}</span>
@endif
@if($fareCabinTags['booking'] !== '')
    <span class="rc__ftag">{{ $fareCabinTags['booking'] }}</span>
@endif
@foreach($bagPills as $bagPill)
    <span class="rc__ftag rc__ftag--bag" title="{{ ($legRow['tag'] ?? '') === 'RT' ? 'Return baggage allowance' : 'Baggage allowance' }}"><i class="bx bx-briefcase-alt-2"></i> {{ $bagPill }}</span>
@endforeach
@if(!is_null($fareSeats))
    <span class="rc__ftag rc__ftag--seat" title="{{ $fareSeats }} {{ $fareSeats === 1 ? 'seat' : 'seats' }} available"><i class="bx bx-user"></i> {{ $fareSeats }}</span>
@endif
@if($nonRefund)
    <span class="rc__fbadge rc__fbadge--nr rc__fbadge--tick"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-custom-class="rc-fare-tip"
        data-bs-title="Non-Refundable">N</span>
@else
    <span class="rc__fbadge rc__fbadge--ref rc__fbadge--tick"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-custom-class="rc-fare-tip"
        data-bs-title="Refundable">R</span>
@endif
