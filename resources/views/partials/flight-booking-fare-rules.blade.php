@php
    $booking = $booking ?? null;
    $itinerary = is_array($booking?->itinerary_data) ? $booking->itinerary_data : [];
    $fareRules = $itinerary['fare_rules'] ?? [];
    $fareBrand = trim((string) ($itinerary['fare_brand'] ?? ''));
    $nonRefund = (bool) ($itinerary['non_refundable'] ?? false);

    $legs = $itinerary['legs'] ?? [];
    $firstLeg = $legs[0] ?? [];
    $segments = $firstLeg['segments'] ?? [];
    $firstSeg = $segments[0] ?? [];
    $lastSeg = $segments !== [] ? $segments[array_key_last($segments)] : [];
    $routeLabel = strtoupper(trim((string) ($firstSeg['from'] ?? ($booking->from_airport ?? ''))))
        . ' → '
        . strtoupper(trim((string) ($lastSeg['to'] ?? ($booking->to_airport ?? ''))));
@endphp

<div class="bkpd-card mb-3"
     data-flight-fare-rules
     data-fare-rules-url="{{ route('admin.flight-bookings.fare-rules', $booking) }}">
    <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-list-check"></i> Fare Rules</div>
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
