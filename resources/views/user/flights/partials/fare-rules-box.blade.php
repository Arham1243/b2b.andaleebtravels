@php
    $itineraryId = (int) ($itineraryId ?? 0);
    $selectedFareIndex = (int) ($selectedFareIndex ?? 0);
    $itinerary = $itinerary ?? [];
    $searchParams = $searchParams ?? [];
    $fareRules = $itinerary['fare_rules'] ?? [];
    $fareBrand = trim((string) ($itinerary['fare_brand'] ?? ''));
    $nonRefund = (bool) ($itinerary['non_refundable'] ?? false);

    $legs = $itinerary['legs'] ?? [];
    $firstLeg = $legs[0] ?? [];
    $segments = $firstLeg['segments'] ?? [];
    $firstSeg = $segments[0] ?? [];
    $lastSeg = $segments !== [] ? $segments[array_key_last($segments)] : [];
    $routeLabel = strtoupper(trim((string) ($firstSeg['from'] ?? ($searchParams['from'] ?? ''))))
        . ' → '
        . strtoupper(trim((string) ($lastSeg['to'] ?? ($searchParams['to'] ?? ''))));
@endphp

<div class="hp-card mb-3 hp-fare-rules-card"
     data-flight-fare-rules
     data-itinerary-id="{{ $itineraryId }}"
     data-fare-index="{{ $selectedFareIndex }}">
    <div class="hp-card__head">
        <i class="bx bx-list-check hp-card__head-icon"></i>
        <div>
            <div class="hp-card__title" style="margin-top:0;">Fare Rules</div>
        </div>
    </div>

    <div class="fd-rules fd-rules--page">
        @include('user.flights.partials.fare-rules-summary', [
            'fareRules' => $fareRules,
            'fareBrand' => $fareBrand,
            'nonRefund' => $nonRefund,
            'routeLabel' => $routeLabel,
        ])
        @include('user.flights.partials.fare-rules-full')
    </div>
</div>
