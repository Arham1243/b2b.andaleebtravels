@php
    $cancellation = $cancellation ?? ['can_cancel' => false, 'reason' => null];
    $status = $status ?? $booking->displayBookingStatus();
@endphp

@if ($status === 'cancelled')
    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
@elseif ($status === 'confirmed' && $booking->payment_status === 'paid')
    @if (!($cancellation['can_cancel'] ?? false))
        <p class="bkpd-no-action">
            <i class="bx bx-x-circle"></i>
            {{ $cancellation['reason'] ?? 'Cancellation is not available for this booking.' }}
        </p>
        @if (!empty($cancellation['policy_summary']))
            <p style="font-size:.72rem;color:#64748b;margin-top:8px;line-height:1.45;">{{ $cancellation['policy_summary'] }}</p>
        @endif
    @else
        <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn">
            <i class="bx bx-x"></i> Cancel Booking
        </button>
        @if (!empty($cancellation['policy_summary']))
            <p style="font-size:.72rem;color:#64748b;margin-top:10px;line-height:1.45;">{{ $cancellation['policy_summary'] }}</p>
        @else
            <p style="font-size:.72rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
                Cancellation is sent to the airline via our GDS. Airline penalties may apply based on fare rules.
            </p>
        @endif
    @endif
@else
    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No supplier cancel action available for this state.</p>
@endif
