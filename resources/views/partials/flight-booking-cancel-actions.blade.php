@php
    $cancellation = $cancellation ?? ['can_cancel' => false, 'reason' => null];
    $status = $status ?? $booking->displayBookingStatus();
    $isHold = $isHold ?? $booking->isOnHold();
    $holdPnr = trim((string) ($booking->sabre_record_locator ?? ''));
@endphp

@if ($status === 'cancelled')
    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
@elseif ($isHold)
    <a href="{{ route('user.flights.hold.confirm', $booking->id) }}"
       class="bkp-btn bkp-btn--primary w-100 mb-2">
        <i class="bx bx-lock-alt"></i> Confirm &amp; Pay
    </a>
    <p style="font-size:.7rem;color:#8492a6;margin-bottom:12px;text-align:center;">
        Pay and issue the ticket before the hold expires.
    </p>
    @if ($holdPnr === '')
        <p class="bkpd-no-action"><i class="bx bx-error-circle"></i> No airline PNR on record. Please contact support.</p>
    @else
    <form action="{{ route('user.bookings.flights.release-hold', $booking->id) }}" method="POST"
          onsubmit="return confirm('Release hold on PNR {{ $holdPnr }}? The booking will be cancelled at the airline.');">
        @csrf
        <button type="submit" class="bkp-btn bkp-btn--warning w-100">
            <i class="bx bx-x-circle"></i> Release Hold
        </button>
    </form>
    <p style="font-size:.7rem;color:#8492a6;margin-top:6px;text-align:center;">
        Releases the airline PNR — no charges since no payment was made.
    </p>
    @endif
@elseif ($booking->payment_status === 'paid' && $status === 'failed')
    <p class="bkpd-no-action">
        <i class="bx bx-error-circle"></i>
        Payment was received but booking could not be completed. Please contact support for assistance.
    </p>
    @if ($booking->ticket_status !== 'issued')
        <a href="{{ route('user.flights.payment.success', $booking->id) }}"
           class="bkp-btn bkp-btn--primary w-100 mt-2">
            <i class="bx bx-refresh"></i> Retry Booking
        </a>
    @endif
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
        @if ($booking->ticket_status === 'failed')
            <p style="font-size:.7rem;color:#8492a6;margin-bottom:10px;text-align:center;line-height:1.4;">
                Ticketing failed — you can cancel the airline reservation or contact support to retry ticketing.
            </p>
            <a href="{{ route('user.flights.payment.success', $booking->id) }}"
               class="bkp-btn bkp-btn--outline w-100 mb-2">
                <i class="bx bx-refresh"></i> Retry Ticketing
            </a>
        @endif
        <a href="{{ route('user.bookings.flights.cancel', $booking->id) }}"
           class="bkp-btn bkp-btn--danger w-100"
           onclick="return confirm('Cancel this confirmed booking? Cancellation charges may apply.');">
            <i class="bx bx-x"></i> Cancel Booking
        </a>
        <p style="font-size:.7rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
            Cancellation is sent to the airline via our GDS. Airline penalties may apply based on fare rules.
        </p>
    @endif
@else
    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No actions available.</p>
@endif
