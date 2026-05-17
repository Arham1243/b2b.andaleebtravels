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
    $roomsData = is_array($booking->rooms_data) ? $booking->rooms_data : [];
    $selectedRooms = is_array($booking->selected_rooms) ? $booking->selected_rooms : [];
    $guestsData = is_array($booking->guests_data) ? array_values($booking->guests_data) : [];
    $extrasData = is_array($booking->extras_data) ? $booking->extras_data : [];
    $confirmRef = $booking->yalago_booking_reference
        ?: data_get($booking->booking_response, 'BookingReferenceId')
        ?: data_get($booking->booking_response, 'BookingRef')
        ?: data_get($booking->booking_response, 'ConfirmationNumber');
    $hasLeadContact = $booking->lead_first_name || $booking->lead_last_name || $booking->lead_email || $booking->lead_phone;
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
                                    <div class="bkpd-stay__month">{{ $booking->check_in_date?->format('M Y') ?? ' - ' }}</div>
                                </div>
                                <div class="bkpd-stay__mid">
                                    <div class="bkpd-stay__nights">
                                        @if($nights !== null)
                                            {{ $nights }} night{{ $nights !== 1 ? 's' : '' }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                    <div class="bkpd-stay__line"></div>
                                    <i class="bx bx-restaurant" style="color:#8492a6;font-size:1.1rem;"></i>
                                </div>
                                <div class="bkpd-stay__col bkpd-stay__col--right">
                                    <div class="bkpd-stay__label">Check-out</div>
                                    <div class="bkpd-stay__date">{{ $booking->check_out_date?->format('d') }}</div>
                                    <div class="bkpd-stay__month">{{ $booking->check_out_date?->format('M Y') ?? ' - ' }}</div>
                                </div>
                            </div>
                        </div>

                        @if($booking->hotel_address)
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--blue"><i class="bx bx-map"></i> Address</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row" style="align-items:flex-start;">
                                    <span class="bkpd-info-row__label">Hotel</span>
                                    <span class="bkpd-info-row__val" style="text-align:right;max-width:70%;">{{ $booking->hotel_address }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($hasLeadContact)
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-phone-call"></i> Primary contact</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Name</span>
                                    <span class="bkpd-info-row__val">{{ trim(($booking->lead_title ? $booking->lead_title . ' ' : '') . $booking->lead_full_name) ?: '—' }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Email</span>
                                    <span class="bkpd-info-row__val" style="word-break:break-all;">{{ $booking->lead_email ?: '—' }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Phone</span>
                                    <span class="bkpd-info-row__val">{{ $booking->lead_phone ?: '—' }}</span>
                                </div>
                                @if($booking->lead_address)
                                <div class="bkpd-info-row" style="align-items:flex-start;">
                                    <span class="bkpd-info-row__label">Billing address</span>
                                    <span class="bkpd-info-row__val" style="text-align:right;max-width:65%;">{{ $booking->lead_address }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(count($guestsData))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-group"></i> Guests on reservation</div>
                            <div class="bkpd-pax-list">
                                @foreach($guestsData as $idx => $g)
                                    @php
                                        $gTitle = $g['title'] ?? 'Mr';
                                        $gFirst = trim((string) ($g['first_name'] ?? ''));
                                        $gLast = trim((string) ($g['last_name'] ?? ''));
                                        $gFull = trim($gTitle . ' ' . $gFirst . ' ' . $gLast);
                                        $gAge = $g['age'] ?? null;
                                        $gAgeInt = $gAge !== null && $gAge !== '' ? (int) $gAge : null;
                                        $initial = strtoupper(substr($gFirst !== '' ? $gFirst : ($gLast !== '' ? $gLast : '?'), 0, 1));
                                    @endphp
                                    <div class="bkpd-pax">
                                        <div class="bkpd-pax__avatar">{{ $initial }}</div>
                                        <div style="min-width:0;">
                                            <div class="bkpd-pax__name">{{ $gFull ?: ('Guest ' . ($idx + 1)) }}</div>
                                            <div class="bkpd-pax__meta">
                                                Guest {{ $idx + 1 }}
                                                @if($gAgeInt !== null)
                                                    · Age {{ $gAge }}
                                                    · {{ $gAgeInt < 12 ? 'Child' : 'Adult' }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @elseif($hasLeadContact && count($roomsData))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-info-circle"></i> Guests</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__val" style="font-weight:500;color:#64748b;">Only the primary contact is stored for this booking. Passenger names were not captured separately.</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(count($roomsData) || count($selectedRooms))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--blue"><i class="bx bx-bed"></i> Rooms & occupancy</div>
                            <div class="bkpd-info-rows" style="padding-top:8px;">
                                @php
                                    $roomCount = max(count($roomsData), count($selectedRooms));
                                @endphp
                                @for($idx = 0; $idx < $roomCount; $idx++)
                                    @php
                                        $room = $roomsData[$idx] ?? [];
                                        $sr = $selectedRooms[$idx] ?? [];
                                        $adults = (int) ($room['Adults'] ?? 0);
                                        $childAges = isset($room['ChildAges']) && is_array($room['ChildAges']) ? $room['ChildAges'] : [];
                                    @endphp
                                    <div class="bkpd-seg-single" style="margin-top:{{ $idx === 0 ? '0' : '8px' }};margin-bottom:8px;">
                                        <div style="font-weight:700;color:#1a2540;">Room {{ $idx + 1 }}</div>
                                        @if(!empty($sr['room_name']))
                                            <div style="margin-top:4px;font-size:.82rem;">{{ $sr['room_name'] }}</div>
                                        @endif
                                        @if(!empty($sr['board_title']))
                                            <div style="font-size:.72rem;color:#64748b;">{{ $sr['board_title'] }}</div>
                                        @endif
                                        <div style="margin-top:8px;font-size:.74rem;color:#4a5568;">
                                            @if($adults > 0)
                                                {{ $adults }} adult{{ $adults !== 1 ? 's' : '' }}
                                            @endif
                                            @if(count($childAges))
                                                @if($adults > 0)<span> · </span>@endif
                                                {{ count($childAges) }} child{{ count($childAges) !== 1 ? 'ren' : '' }} (ages {{ implode(', ', $childAges) }})
                                            @elseif($adults === 0 && empty($sr))
                                                <span style="color:#94a3b8;">Occupancy details unavailable</span>
                                            @endif
                                        </div>
                                        @if(isset($sr['price']) && $sr['price'] !== '')
                                            <div style="margin-top:8px;font-size:.78rem;font-weight:600;">Room total: {!! formatPrice((float) $sr['price']) !!}</div>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>
                        @endif

                        @if(count($extrasData))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--green"><i class="bx bx-plus-circle"></i> Extras</div>
                            <div class="bkpd-info-rows">
                                @foreach($extrasData as $ex)
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">{{ $ex['title'] ?? 'Extra' }}</span>
                                        <span class="bkpd-info-row__val">{!! formatPrice((float) ($ex['price'] ?? 0)) !!}</span>
                                    </div>
                                @endforeach
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
                                    <span>Rooms</span>
                                    <span>{!! formatPrice($booking->rooms_total ?? $booking->total_amount) !!}</span>
                                </div>
                                @if(($booking->extras_total ?? 0) > 0)
                                <div class="bkpd-fare__row">
                                    <span>Extras</span>
                                    <span>{!! formatPrice($booking->extras_total) !!}</span>
                                </div>
                                @endif
                                @if($booking->wallet_amount > 0)
                                <div class="bkpd-fare__row">
                                    <span>Wallet applied</span>
                                    <span>− {!! formatPrice($booking->wallet_amount) !!}</span>
                                </div>
                                @endif
                                <div class="bkpd-fare__row bkpd-fare__row--total">
                                    <span>Total paid</span>
                                    <span>{!! formatPrice($booking->total_amount) !!}</span>
                                </div>
                                @if($booking->currency)
                                <div class="bkpd-fare__row" style="font-size:.72rem;color:#8492a6;margin-top:-4px;">
                                    <span></span>
                                    <span>Currency: {{ $booking->currency }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-info-circle"></i> Booking Info</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booking #</span>
                                    <span class="bkpd-info-row__val" style="color:#cd1b4f;font-weight:700;">{{ $booking->booking_number }}</span>
                                </div>
                                @if($confirmRef)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Supplier confirmation</span>
                                    <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.78rem;">{{ $confirmRef }}</span>
                                </div>
                                @endif
                                @if($booking->yalago_hotel_id)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Hotel code</span>
                                    <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.78rem;">{{ $booking->yalago_hotel_id }}</span>
                                </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Supplier</span>
                                    <span class="bkpd-info-row__val">{{ formatBookingSupplierLabel($booking->supplier, 'Yalago') }}</span>
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
                                    <span class="bkpd-info-row__val" style="color:#b91c1c;">{{ formatBookingCancelledAt($booking->cancelled_at) ?? '—' }}</span>
                                </div>
                                @endif
                                @if($booking->cancelled_by)
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Cancelled by</span>
                                    <span class="bkpd-info-row__val">{{ formatBookingCancelledByLabel($booking->cancelled_by) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        @if($status === 'cancelled')
                            @php
                                $cancelEnvelope = $booking->cancel_response;
                                $cancelApiPayload = bookingCancellationApiPayload(is_array($cancelEnvelope) ? $cancelEnvelope : null);
                            @endphp
                            @if(!empty($cancelApiPayload) || (is_array($cancelEnvelope) && !empty($cancelEnvelope['cancellation_type'] ?? null)))
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-data"></i> Cancellation record</div>
                            <div class="bkpd-info-rows">
                                @if(is_array($cancelEnvelope))
                                    @if(!empty($cancelEnvelope['cancellation_type']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Type</span>
                                        <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.72rem;">{{ $cancelEnvelope['cancellation_type'] }}</span>
                                    </div>
                                    @endif
                                    @if(!empty($cancelEnvelope['recorded_at']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Response recorded</span>
                                        <span class="bkpd-info-row__val" style="font-size:.78rem;">{{ $cancelEnvelope['recorded_at'] }}</span>
                                    </div>
                                    @endif
                                @endif
                            </div>
                            @if(!empty($cancelApiPayload))
                            <details style="padding:0 18px 14px;">
                                <summary style="cursor:pointer;font-size:.74rem;font-weight:700;color:#64748b;">Supplier / API response (JSON)</summary>
                                <pre style="margin-top:10px;padding:10px 12px;background:#f8fafc;border:1px solid #e4e9f0;border-radius:8px;font-size:.65rem;overflow:auto;max-height:280px;white-space:pre-wrap;word-break:break-word;">{{ Str::limit(json_encode($cancelApiPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), 12000) }}</pre>
                            </details>
                            @endif
                        </div>
                            @endif
                        @endif

                        <div class="bkpd-card">
                            <div class="bkpd-card__section-head"><i class="bx bx-cog"></i> Actions</div>
                            <div class="bkpd-actions">
                                @if($status === 'cancelled')
                                    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
                                @elseif($booking->booking_status === 'confirmed' && $booking->payment_status === 'paid')
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
