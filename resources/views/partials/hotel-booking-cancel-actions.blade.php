@php
    $cancellation = $cancellation ?? ['can_cancel' => false, 'reason' => null];
    $status = $status ?? ($booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status);
    $canCancel = (bool) ($cancellation['can_cancel'] ?? false);
    $isRefundable = $cancellation['is_refundable'] ?? null;
    $showPolicy = $isRefundable !== null
        || !empty($cancellation['policy_summary'])
        || !empty($cancellation['deadline_formatted']);
@endphp

@if ($status === 'cancelled')
    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
@elseif ($booking->booking_status === 'confirmed' && $booking->payment_status === 'paid')
    @if ($showPolicy)
        @include('partials.hotel-booking-cancellation-policy', ['cancellation' => $cancellation])
    @endif

    @if (!empty($cancellation['policy_fetch_failed']))
        <p class="bkpd-no-action"><i class="bx bx-error-circle"></i> Unable to load cancellation policy from the supplier. Please try again later or contact support.</p>
    @elseif ($canCancel && ($cancellation['flow'] ?? '') === 'tbo_direct')
        <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn-tbo" data-booking-id="{{ $booking->id }}">
            <i class="bx bx-x"></i> Cancel Booking
        </button>
    @elseif ($canCancel && ($cancellation['flow'] ?? '') === 'yalago_modal')
        <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn" data-booking-id="{{ $booking->id }}">
            <i class="bx bx-x"></i> Cancel Booking
        </button>
    @elseif (!$canCancel)
        <p class="bkpd-no-action">
            <i class="bx bx-x-circle"></i>
            {{ $cancellation['reason'] ?? 'Cancellation is not available for this booking.' }}
        </p>
    @else
        <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No actions available.</p>
    @endif
@else
    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No actions available.</p>
@endif
