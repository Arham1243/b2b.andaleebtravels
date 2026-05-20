@php
    $cancellation = $cancellation ?? ['can_cancel' => false, 'reason' => null];
    $status = $status ?? ($booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status);
@endphp

@if ($status === 'cancelled')
    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
@elseif ($booking->booking_status === 'confirmed' && $booking->payment_status === 'paid')
    @if (!empty($cancellation['policy_fetch_failed']))
        <p class="bkpd-no-action"><i class="bx bx-error-circle"></i> Unable to load cancellation policy from the supplier. Please try again later.</p>
    @elseif (!($cancellation['can_cancel'] ?? false))
        <p class="bkpd-no-action">
            <i class="bx bx-x-circle"></i>
            {{ $cancellation['reason'] ?? 'Cancellation is not available for this booking.' }}
        </p>
        @if (!empty($cancellation['policy_summary']))
            <p style="font-size:.72rem;color:#64748b;margin-top:8px;line-height:1.45;">{{ $cancellation['policy_summary'] }}</p>
        @endif
    @else
        <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn" data-booking-id="{{ $booking->id }}">
            <i class="bx bx-x"></i> Cancel Booking
        </button>
        @if (!empty($cancellation['policy_summary']))
            <p style="font-size:.72rem;color:#64748b;margin-top:10px;line-height:1.45;">{{ $cancellation['policy_summary'] }}</p>
        @else
            <p style="font-size:.72rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
                Cancellation is sent to the supplier. Wallet credit applies if payment was collected.
            </p>
        @endif
    @endif
@else
    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No actions available.</p>
@endif
