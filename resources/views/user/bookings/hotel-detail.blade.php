@extends('user.layouts.main')

@section('css')
@include('user.bookings._styles')
@endsection

@section('content')
@php
    $status = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
    $nights = $booking->check_in_date && $booking->check_out_date
        ? $booking->check_in_date->diffInDays($booking->check_out_date)
        : null;
    $guests = $booking->booking_data ?? $booking->search_request ?? [];
@endphp

<div class="bkp">
    <div class="container">
        <div class="bkp-shell">

            @include('user.bookings._nav', ['activeSection' => 'hotels', 'counts' => $counts])

            <main class="bkp-main">

                <nav class="bkp-crumb">
                    <a href="{{ route('user.bookings.hotels') }}"><i class="bx bx-chevron-left"></i> Hotel Bookings</a>
                    <span>{{ $booking->booking_number }}</span>
                </nav>

                <div class="bkpd-grid">

                    {{-- LEFT --}}
                    <div>

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__head">
                                <div>
                                    <div class="bkpd-card__title">{{ $booking->hotel_name ?? 'Hotel Booking' }}</div>
                                    <div class="bkpd-card__sub">{{ $booking->booking_number }}</div>
                                </div>
                                <div class="d-flex gap-2 ms-auto">
                                    <span class="bkp-badge bkp-badge--{{ $status }}">
                                        @if($status === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                        @elseif($status === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                        @else {{ ucfirst($status) }}
                                        @endif
                                    </span>
                                    <span class="bkp-badge bkp-badge--{{ $booking->payment_status }}">
                                        {{ ucfirst($booking->payment_status) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Stay info visual --}}
                            <div class="bkpd-stay">
                                <div class="bkpd-stay__col">
                                    <div class="bkpd-stay__label">Check-in</div>
                                    <div class="bkpd-stay__date">{{ $booking->check_in_date?->format('d') }}</div>
                                    <div class="bkpd-stay__month">{{ $booking->check_in_date?->format('M Y') ?? '—' }}</div>
                                </div>
                                <div class="bkpd-stay__mid">
                                    <div class="bkpd-stay__nights">{{ $nights ?? '—' }} night{{ $nights > 1 ? 's' : '' }}</div>
                                    <div class="bkpd-stay__line"></div>
                                    <i class="bx bxs-hotel" style="color:#8492a6;font-size:1.1rem;"></i>
                                </div>
                                <div class="bkpd-stay__col bkpd-stay__col--right">
                                    <div class="bkpd-stay__label">Check-out</div>
                                    <div class="bkpd-stay__date">{{ $booking->check_out_date?->format('d') }}</div>
                                    <div class="bkpd-stay__month">{{ $booking->check_out_date?->format('M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Room / guest details --}}
                        @php
                            $rooms      = $booking->rooms_data ?? $booking->booking_data['rooms'] ?? null;
                            $guestName  = $booking->booking_data['lead_name'] ?? ($booking->booking_data['guest_name'] ?? null);
                        @endphp
                        @if($rooms || $guestName)
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-user"></i> Guest & Room Info</div>
                            <div class="bkpd-info-rows">
                                @if($guestName)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Lead Guest</span>
                                    <span class="bkpd-info-row__val">{{ $guestName }}</span>
                                </div>
                                @endif
                                @if($rooms)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Rooms</span>
                                    <span class="bkpd-info-row__val">{{ is_array($rooms) ? count($rooms) : $rooms }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                    </div>

                    {{-- RIGHT --}}
                    <div>

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--green"><i class="bx bx-receipt"></i> Fare Summary</div>
                            <div class="bkpd-fare">
                                <div class="bkpd-fare__row">
                                    <span>Room Charge</span>
                                    <span>{!! formatPrice($booking->total_amount) !!}</span>
                                </div>
                                <div class="bkpd-fare__row bkpd-fare__row--total">
                                    <span>Total</span>
                                    <span>{!! formatPrice($booking->total_amount) !!}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-info-circle"></i> Booking Info</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booking #</span>
                                    <span class="bkpd-info-row__val" style="color:#cd1b4f;font-weight:700;">{{ $booking->booking_number }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Supplier</span>
                                    <span class="bkpd-info-row__val">{{ ucfirst($booking->supplier ?? 'Yalago') }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Payment</span>
                                    <span class="bkpd-info-row__val">{{ ucfirst($booking->payment_method ?? 'N/A') }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booked On</span>
                                    <span class="bkpd-info-row__val">{{ $booking->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @if($booking->cancelled_at)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Cancelled</span>
                                    <span class="bkpd-info-row__val" style="color:#b91c1c;">{{ $booking->cancelled_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="bkpd-card">
                            <div class="bkpd-card__section-head"><i class="bx bx-cog"></i> Actions</div>
                            <div class="bkpd-actions">
                                @if($status === 'cancelled')
                                    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
                                @elseif($status === 'confirmed' && $booking->payment_status === 'paid')
                                    @if(($booking->supplier ?? 'yalago') === 'tbo')
                                        <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn-tbo" data-booking-id="{{ $booking->id }}">
                                            <i class="bx bx-x"></i> Cancel Booking
                                        </button>
                                    @else
                                        <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn" data-booking-id="{{ $booking->id }}">
                                            <i class="bx bx-x"></i> Cancel Booking
                                        </button>
                                    @endif
                                @else
                                    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No actions available.</p>
                                @endif
                            </div>
                        </div>

                    </div>

                </div>

            </main>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancellation Charges</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cancelBookingModalBody">
                <div class="text-center py-5">Loading…</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).on('click', '.cancel-booking-btn', function() {
    const bookingId = $(this).data('booking-id');
    const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
    $('#cancelBookingModalBody').html('<div class="text-center py-5">Loading cancellation policy…</div>');
    modal.show();
    $.post("{{ route('user.bookings.hotels.cancellation-charges') }}", { booking_id: bookingId, _token: "{{ csrf_token() }}" })
        .done(html => $('#cancelBookingModalBody').html(html))
        .fail(() => $('#cancelBookingModalBody').html('<p class="text-danger text-center py-3">Failed to load policy.</p>'));
});
$(document).on('click', '.cancel-booking-btn-tbo', function() {
    if (!confirm('Cancel this booking?')) return;
    $.post("{{ route('user.bookings.hotels.cancel-tbo') }}", { booking_id: $(this).data('booking-id'), _token: "{{ csrf_token() }}" })
        .done(() => window.location.reload())
        .fail(xhr => alert(xhr.responseJSON?.message || 'Unable to cancel.'));
});
</script>
@endpush
