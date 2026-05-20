@extends('admin.layouts.main')

@push('css')
@include('user.bookings._styles')
<style>
.bkp--admin { padding: 0; min-height: auto; background: transparent; }
.bkp--admin .bkp-main { min-width: 0; }
.admin-hotel-status .form-check { margin-bottom: .35rem; }
.admin-hotel-status .form-check-label { font-size: .82rem; color: #4a5568; }
.admin-booking-vendor .bkpd-info-row__val a { color: var(--c-brand, #cd1b4f); font-weight: 600; text-decoration: none; }
.admin-booking-vendor .bkpd-info-row__val a:hover { text-decoration: underline; }
</style>
@endpush

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
    $tboRefundMeta = ['is_refundable' => null, 'summary' => null];
    if (strtolower((string) ($booking->supplier ?? '')) === 'tbo') {
        $tboRefundMeta = \App\Support\HotelRefundPresentation::tboRefundMetaFromBookingResponse(
            is_array($booking->booking_response) ? $booking->booking_response : null
        );
    }
@endphp

<div class="col-md-12">
    <div class="dashboard-content">
        {{ Breadcrumbs::render('admin.hotel-bookings.show', $booking) }}

        <div class="bkp bkp--admin">
            <div class="bkp-main">
                <div class="bkpd-grid">
                    <div>
                        @include('admin.hotel-bookings.partials.supplier-booking-details')

                        @include('admin.partials.booking-vendor-detail-card', ['vendor' => $booking->vendor])

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__head">
                                <div>
                                    <div class="bkpd-card__title">{{ $booking->hotel_name ?? 'Hotel Booking' }}</div>
                                    <div class="bkpd-card__sub">{{ $booking->booking_number }}</div>
                                </div>
                                <div class="d-flex gap-2 ms-auto">
                                    <span class="bkp-badge bkp-badge--{{ $status }}">
                                        @if ($status === 'confirmed')<i class="bx bx-check-circle"></i> Confirmed
                                        @elseif ($status === 'cancelled')<i class="bx bx-x-circle"></i> Cancelled
                                        @else {{ ucfirst($status) }}
                                        @endif
                                    </span>
                                    <span class="bkp-badge bkp-badge--{{ $booking->payment_status }}">
                                        {{ ucfirst($booking->payment_status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="bkpd-stay">
                                <div class="bkpd-stay__col">
                                    <div class="bkpd-stay__label">Check-in</div>
                                    <div class="bkpd-stay__date">{{ $booking->check_in_date?->format('d') }}</div>
                                    <div class="bkpd-stay__month">{{ $booking->check_in_date?->format('M Y') ?? ' - ' }}</div>
                                </div>
                                <div class="bkpd-stay__mid">
                                    <div class="bkpd-stay__nights">
                                        @if ($nights !== null)
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

                        @if ($booking->hotel_address)
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

                        @if ($hasLeadContact)
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
                                    @if ($booking->lead_address)
                                        <div class="bkpd-info-row" style="align-items:flex-start;">
                                            <span class="bkpd-info-row__label">Billing address</span>
                                            <span class="bkpd-info-row__val" style="text-align:right;max-width:65%;">{{ $booking->lead_address }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if (count($guestsData))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-group"></i> Guests on reservation</div>
                                <div class="bkpd-pax-list">
                                    @foreach ($guestsData as $idx => $g)
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
                                                    @if ($gAgeInt !== null)
                                                        · Age {{ $gAge }}
                                                        · {{ $gAgeInt < 12 ? 'Child' : 'Adult' }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($hasLeadContact && count($roomsData))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-info-circle"></i> Guests</div>
                                <div class="bkpd-info-rows">
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__val" style="font-weight:500;color:#64748b;">Only the primary contact is stored for this booking. Passenger names were not captured separately.</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (count($roomsData) || count($selectedRooms))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--blue"><i class="bx bx-bed"></i> Rooms & occupancy</div>
                                <div class="bkpd-info-rows" style="padding-top:8px;">
                                    @php $roomCount = max(count($roomsData), count($selectedRooms)); @endphp
                                    @for ($idx = 0; $idx < $roomCount; $idx++)
                                        @php
                                            $room = $roomsData[$idx] ?? [];
                                            $sr = $selectedRooms[$idx] ?? [];
                                            $adults = (int) ($room['Adults'] ?? 0);
                                            $childAges = isset($room['ChildAges']) && is_array($room['ChildAges']) ? $room['ChildAges'] : [];
                                        @endphp
                                        <div class="bkpd-seg-single" style="margin-top:{{ $idx === 0 ? '0' : '8px' }};margin-bottom:8px;">
                                            <div style="font-weight:700;color:#1a2540;">Room {{ $idx + 1 }}</div>
                                            @if (!empty($sr['room_name']))
                                                <div style="margin-top:4px;font-size:.82rem;">{{ $sr['room_name'] }}</div>
                                            @endif
                                            @if (!empty($sr['board_title']))
                                                <div style="font-size:.72rem;color:#64748b;">{{ $sr['board_title'] }}</div>
                                            @endif
                                            <div style="margin-top:8px;font-size:.74rem;color:#4a5568;">
                                                @if ($adults > 0)
                                                    {{ $adults }} adult{{ $adults !== 1 ? 's' : '' }}
                                                @endif
                                                @if (count($childAges))
                                                    @if ($adults > 0)<span> · </span>@endif
                                                    {{ count($childAges) }} child{{ count($childAges) !== 1 ? 'ren' : '' }} (ages {{ implode(', ', $childAges) }})
                                                @elseif ($adults === 0 && empty($sr))
                                                    <span style="color:#94a3b8;">Occupancy details unavailable</span>
                                                @endif
                                            </div>
                                            @if (isset($sr['price']) && $sr['price'] !== '')
                                                <div style="margin-top:8px;font-size:.78rem;font-weight:600;">Room total: {!! formatPrice((float) $sr['price']) !!}</div>
                                            @endif
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        @endif

                        @if (count($extrasData))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--green"><i class="bx bx-plus-circle"></i> Extras</div>
                                <div class="bkpd-info-rows">
                                    @foreach ($extrasData as $ex)
                                        <div class="bkpd-info-row">
                                            <span class="bkpd-info-row__label">{{ $ex['title'] ?? 'Extra' }}</span>
                                            <span class="bkpd-info-row__val">{!! formatPrice((float) ($ex['price'] ?? 0)) !!}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--green"><i class="bx bx-receipt"></i> Fare Summary</div>
                            <div class="bkpd-fare">
                                <div class="bkpd-fare__row">
                                    <span>Rooms</span>
                                    <span>{!! formatPrice($booking->rooms_total ?? $booking->total_amount) !!}</span>
                                </div>
                                @if (($booking->extras_total ?? 0) > 0)
                                    <div class="bkpd-fare__row">
                                        <span>Extras</span>
                                        <span>{!! formatPrice($booking->extras_total) !!}</span>
                                    </div>
                                @endif
                                @if ($booking->wallet_amount > 0)
                                    <div class="bkpd-fare__row">
                                        <span>Wallet applied</span>
                                        <span>− {!! formatPrice($booking->wallet_amount) !!}</span>
                                    </div>
                                @endif
                                <div class="bkpd-fare__row bkpd-fare__row--total">
                                    <span>Total paid</span>
                                    <span>{!! formatPrice($booking->total_amount) !!}</span>
                                </div>
                                @if ($booking->currency)
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
                                @if ($confirmRef)
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Supplier confirmation</span>
                                        <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.78rem;">{{ $confirmRef }}</span>
                                    </div>
                                @endif
                                @if ($booking->yalago_hotel_id)
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Hotel code</span>
                                        <span class="bkpd-info-row__val" style="font-family:monospace;font-size:.78rem;">{{ $booking->yalago_hotel_id }}</span>
                                    </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Supplier</span>
                                    <span class="bkpd-info-row__val">{{ formatBookingSupplierLabel($booking->supplier, 'Yalago') }}</span>
                                </div>
                                @if (strtolower((string) ($booking->supplier ?? '')) === 'tbo')
                                    <div class="bkpd-info-row bkpd-info-row--refund">
                                        <span class="bkpd-info-row__label">Refund</span>
                                        <div class="bkpd-info-row__val bkpd-info-row__val--refund">
                                            @if ($tboRefundMeta['is_refundable'] === true)
                                                <span class="bkpd-refund-pill bkpd-refund-pill--yes"><i class="bx bx-check-shield"></i> Refundable rate</span>
                                                <p class="bkpd-refund-note">Recorded as refundable on the TBO confirmation. Cancellation penalties still follow supplier timing and hotel rules.</p>
                                            @elseif ($tboRefundMeta['is_refundable'] === false)
                                                <span class="bkpd-refund-pill bkpd-refund-pill--no"><i class="bx bx-x-circle"></i> Non-refundable rate</span>
                                                <p class="bkpd-refund-note">Recorded as non-refundable on the TBO confirmation.</p>
                                            @elseif (!empty($tboRefundMeta['summary']))
                                                <p class="bkpd-refund-note">{{ $tboRefundMeta['summary'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Payment</span>
                                    <span class="bkpd-info-row__val">{{ ucfirst($booking->payment_method ?? 'N/A') }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booked on</span>
                                    <span class="bkpd-info-row__val">{{ $booking->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @if ($booking->cancelled_at)
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Cancelled</span>
                                        <span class="bkpd-info-row__val" style="color:#b91c1c;">
                                            {{ formatBookingCancelledAt($booking->cancelled_at) ?? '—' }}
                                            @if ($booking->cancelled_by)
                                                (by {{ $booking->cancelled_by }})
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            $vendorLabel = $booking->vendor
                                ? ($booking->vendor->display_agency_name ?: $booking->vendor->name)
                                : 'Vendor';
                        @endphp
                        <form action="{{ route('admin.bookings.hotels.status', $booking->id) }}" method="POST"
                            class="bkpd-card mb-3 admin-hotel-status admin-booking-status-form"
                            data-booking-number="{{ $booking->booking_number }}"
                            data-total-amount="{{ number_format((float) $booking->total_amount, 2) }}"
                            data-vendor-name="{{ e($vendorLabel) }}"
                            data-current-payment-status="{{ $booking->payment_status }}"
                            data-current-booking-status="{{ $booking->booking_status }}"
                            data-booking-type="hotel">
                            @csrf
                            <div class="bkpd-card__section-head"><i class="bx bx-edit"></i> Update status</div>
                            <div class="bkpd-actions" style="padding:0 16px 16px;">
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Payment status</div>
                                    @if ($booking->booking_status === 'cancelled' && $booking->payment_status !== 'refunded')
                                        <input type="hidden" name="payment_status" value="{{ $booking->payment_status }}">
                                        <p class="small text-muted mb-2">Current: <strong>{{ ucfirst($booking->payment_status) }}</strong> (select Refunded below to credit wallet)</p>
                                    @else
                                        @foreach (['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'] as $val => $label)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_status" id="payment_{{ $val }}" value="{{ $val }}"
                                                    {{ old('payment_status', $booking->payment_status) === $val ? 'checked' : '' }}>
                                                <label class="form-check-label" for="payment_{{ $val }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    @endif
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_status" id="payment_refunded" value="refunded"
                                            {{ old('payment_status', $booking->payment_status) === 'refunded' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="payment_refunded">Refunded</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Booking status</div>
                                    @if ($booking->booking_status !== 'cancelled')
                                        @foreach (['confirmed' => 'Confirmed', 'pending' => 'Pending'] as $val => $label)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="booking_status" id="booking_{{ $val }}" value="{{ $val }}"
                                                    {{ old('booking_status', $booking->booking_status) === $val ? 'checked' : '' }}>
                                                <label class="form-check-label" for="booking_{{ $val }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    @endif
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="booking_status" id="booking_refunded" value="refunded"
                                            {{ old('booking_status', $booking->booking_status) === 'refunded' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="booking_refunded">Refunded</label>
                                    </div>
                                    @if ($booking->booking_status !== 'cancelled')
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="booking_status" id="booking_completed" value="completed"
                                                {{ old('booking_status', $booking->booking_status) === 'completed' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="booking_completed">Completed</label>
                                        </div>
                                    @endif
                                </div>
                                <button type="submit" class="bkp-btn bkp-btn--primary w-100">Save changes</button>
                            </div>
                        </form>

                        <div class="bkpd-card">
                            <div class="bkpd-card__section-head"><i class="bx bx-cog"></i> Actions</div>
                            <div class="bkpd-actions">
                                @include('partials.admin-hotel-booking-cancel-actions', [
                                    'booking' => $booking,
                                    'cancellation' => $cancellation ?? [],
                                    'status' => $status,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('admin.bookings.partials.status-form-confirm')
<script>
$(document).on('click', '.cancel-booking-btn', function() {
    const amount = @json(number_format((float) $booking->total_amount, 2));
    const vendor = @json($vendorLabel ?? 'the vendor');
    const message =
        'Cancel this hotel booking with the supplier?\n\n' +
        'If payment was collected, ' + amount + ' AED will be credited to ' + vendor + '\'s wallet.\n\n' +
        'Continue?';
    if (!confirm(message)) {
        return;
    }

    $.post("{{ route('admin.bookings.hotels.cancel', $booking->id) }}", {
        _token: "{{ csrf_token() }}"
    })
        .done(function() {
            window.location.reload();
        })
        .fail(function(xhr) {
            alert(xhr.responseJSON?.message || 'Unable to cancel booking.');
        });
});
</script>
@endpush
