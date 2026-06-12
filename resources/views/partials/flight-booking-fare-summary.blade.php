@php
    $fareBreakdown = $fareBreakdown ?? flightFareBreakdownForBooking($booking);
    $isHold = $isHold ?? $booking->isOnHold();
@endphp

<div class="bkpd-fare">
    @if (!empty($showVendorDiscount))
        @include('admin.partials.vendor-discount-fare', ['booking' => $booking])
    @endif

    <div class="bkpd-fare__pax-breakdown">
        @include('user.flights.partials.fare-summary-breakdown', [
            'breakdown' => $fareBreakdown,
            'itinerary' => $booking->itinerary_data ?? [],
            'adults' => (int) $booking->adults,
            'children' => (int) $booking->children,
            'infants' => (int) $booking->infants,
            'fallbackTotal' => (float) $booking->total_amount,
        ])
    </div>

    @if (($booking->wallet_amount ?? 0) > 0)
        <div class="bkpd-fare__row">
            <span>Wallet applied</span>
            <span>− {!! formatPrice($booking->wallet_amount) !!}</span>
        </div>
    @endif

    @if ($isHold)
        <div class="bkpd-fare__row">
            <span>{{ ($holdDepositLabel ?? false) ? 'Hold deposit' : 'Hold Deposit' }}</span>
            <span style="color:#10b981;font-weight:800;">FREE</span>
        </div>
    @endif

    <div class="bkpd-fare__row bkpd-fare__row--total">
        <span>{{ $totalLabel ?? 'Total' }}</span>
        <span>{!! formatPrice($booking->total_amount) !!}</span>
    </div>
</div>
