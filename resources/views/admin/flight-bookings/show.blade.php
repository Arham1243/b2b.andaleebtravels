@extends('admin.layouts.main')

@push('css')
@include('user.bookings._styles')
<style>
.bkp--admin { padding: 0; min-height: auto; background: transparent; }
.bkp--admin .bkp-main { min-width: 0; }
.admin-flight-status .form-check { margin-bottom: .35rem; }
.admin-flight-status .form-check-label { font-size: .82rem; color: #4a5568; }
.admin-flight-vendor .bkpd-info-row__val a { color: var(--c-brand, #cd1b4f); font-weight: 600; text-decoration: none; }
.admin-flight-vendor .bkpd-info-row__val a:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
@php
    $status = $booking->booking_status === 'completed' ? 'confirmed' : $booking->booking_status;
    $isHold = $booking->booking_status === 'hold';
    $isRound = !empty($booking->return_date);
    $legs = $booking->itinerary_data['legs'] ?? [];
    $passengers = $booking->passengers_data['passengers'] ?? [];
    $lead = $booking->passengers_data['lead'] ?? [];
    $totalPax = max(1, $booking->adults + $booking->children + $booking->infants);

    $paxStr = $booking->adults . ' Adult' . ($booking->adults > 1 ? 's' : '');
    if ($booking->children) {
        $paxStr .= ', ' . $booking->children . ' Child' . ($booking->children > 1 ? 'ren' : '');
    }
    if ($booking->infants) {
        $paxStr .= ', ' . $booking->infants . ' Infant' . ($booking->infants > 1 ? 's' : '');
    }

    $payMethod = strtolower((string) ($booking->payment_method ?? ''));
    $payLabel = match ($payMethod) {
        'payby' => 'Card (PayBy)',
        'tabby' => 'Tabby',
        'tamara' => 'Tamara',
        'wallet' => 'Wallet',
        'hold' => 'Hold',
        default => $payMethod !== '' ? ucfirst(str_replace('_', ' ', $payMethod)) : ($isHold ? 'Hold (Free)' : 'N/A'),
    };

    $ttl = null;
    $ttlIsEstimate = false;
    if ($isHold) {
        if ($booking->hold_expires_at) {
            $ttl = $booking->hold_expires_at;
        } else {
            $ttl = $booking->created_at->copy()->addHour();
            $ttlIsEstimate = true;
        }
    }

    $nonRefundable = null;
    $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
    if (array_key_exists('non_refundable', $itinerary)) {
        $nonRefundable = (bool) ($itinerary['non_refundable'] ?? false);
    } else {
        $passengerFare = data_get($booking->search_response, 'groupedItineraryResponse.itineraryGroups.0.itineraries.0.pricingInformation.0.fare.passengerInfoList.0.passengerInfo');
        if (is_array($passengerFare) && array_key_exists('nonRefundable', $passengerFare)) {
            $nonRefundable = (bool) ($passengerFare['nonRefundable'] ?? false);
        }
    }

    $fmtMins = function (?int $m): string {
        if (!$m || $m < 1) {
            return '—';
        }
        $h = intdiv($m, 60);
        $r = $m % 60;

        return $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$r}m";
    };
@endphp

<div class="col-md-12">
    <div class="dashboard-content">
        {{ Breadcrumbs::render('admin.flight-bookings.show', $booking) }}

        <div class="bkp bkp--admin">
            <div class="bkp-main">
                @if ($isHold && $status !== 'cancelled' && $ttl)
                    <div class="bkpd-hold-expiry mb-3 {{ $ttl->isPast() ? 'bkpd-hold-expiry--expired' : '' }}">
                        <div class="bkpd-hold-expiry__icon">
                            <i class="bx {{ $ttl->isPast() ? 'bx-error-circle' : 'bx-time-five' }}"></i>
                        </div>
                        <div class="bkpd-hold-expiry__body">
                            <div class="bkpd-hold-expiry__title">
                                @if ($ttl->isPast())
                                    Hold expired
                                @else
                                    {{ $ttlIsEstimate ? 'Estimated hold expiry' : 'Hold expires' }}:
                                    <strong>{{ $ttl->format('D, d M Y \a\t h:i A') }}</strong>
                                @endif
                            </div>
                            <div class="bkpd-hold-expiry__meta">
                                PNR: <strong>{{ $booking->sabre_record_locator ?? '—' }}</strong>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bkpd-grid">
                    <div>
                        @include('admin.hotel-bookings.partials.supplier-booking-details')

                        @if ($booking->vendor)
                            <div class="bkpd-card mb-3 admin-flight-vendor">
                                <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-briefcase"></i> Vendor</div>
                                <div class="bkpd-info-rows">
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Name</span>
                                        <span class="bkpd-info-row__val">
                                            <a href="{{ route('admin.vendors.show', $booking->vendor) }}">{{ $booking->vendor->name }}</a>
                                        </span>
                                    </div>
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Email</span>
                                        <span class="bkpd-info-row__val" style="word-break:break-all;">{{ $booking->vendor->email }}</span>
                                    </div>
                                    @if ($booking->vendor->agent_code)
                                        <div class="bkpd-info-row">
                                            <span class="bkpd-info-row__label">Agent code</span>
                                            <span class="bkpd-info-row__val">{{ $booking->vendor->agent_code }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__head">
                                <div>
                                    <div class="bkpd-card__title">
                                        {{ strtoupper($booking->from_airport ?? '') }}
                                        {{ $isRound ? '⇄' : '→' }}
                                        {{ strtoupper($booking->to_airport ?? '') }}
                                    </div>
                                    <div class="bkpd-card__sub">{{ $booking->booking_number }}</div>
                                </div>
                                <div class="d-flex gap-2 ms-auto flex-wrap align-items-center">
                                    <span class="bkp-badge bkp-badge--{{ $status }}">
                                        @if ($status === 'hold')
                                            <i class="bx bx-time-five"></i> On Hold
                                        @elseif ($status === 'confirmed')
                                            <i class="bx bx-check-circle"></i> Confirmed
                                        @elseif ($status === 'cancelled')
                                            <i class="bx bx-x-circle"></i> Cancelled
                                        @else
                                            {{ ucfirst($status) }}
                                        @endif
                                    </span>
                                    @if ($booking->ticket_status)
                                        <span class="bkp-badge bkp-badge--ticket">
                                            <i class="bx bx-receipt"></i> {{ ucfirst($booking->ticket_status) }}
                                        </span>
                                    @endif
                                    <span class="bkp-badge bkp-badge--{{ $booking->payment_status }}">
                                        {{ ucfirst($booking->payment_status) }}
                                    </span>
                                </div>
                            </div>

                            @if ($booking->sabre_record_locator)
                                <div class="bkpd-pnr-row">
                                    <div>
                                        <div class="bkpd-pnr-label">PNR / Record Locator</div>
                                        <div class="bkpd-pnr-value">{{ $booking->sabre_record_locator }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (!empty($legs))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--blue"><i class="bx bx-plane"></i> Flight route</div>
                                @foreach ($legs as $li => $leg)
                                    @php
                                        $segs = $leg['segments'] ?? [];
                                        $first = $segs[0] ?? [];
                                        $last = end($segs) ?: [];
                                        $stops = count($segs) - 1;
                                        $durMins = $leg['elapsedTime'] ?? null;
                                        $midApts = [];
                                        for ($si = 0; $si < count($segs) - 1; $si++) {
                                            $midApts[] = $segs[$si]['to'] ?? '';
                                        }
                                    @endphp
                                    <div class="bkpd-leg {{ $li > 0 ? 'bkpd-leg--border' : '' }}">
                                        <div class="bkpd-leg__label">
                                            @if ($li === 0)
                                                <i class="bx bx-right-top-arrow-circle"></i> Outbound
                                            @else
                                                <i class="bx bx-left-top-arrow-circle"></i> Return
                                            @endif
                                            <span class="bkpd-leg__date ms-auto">
                                                {{ $li === 0
                                                    ? ($booking->departure_date?->format('d M Y') ?? '')
                                                    : ($booking->return_date?->format('d M Y') ?? '') }}
                                            </span>
                                        </div>

                                        <div class="bkpd-leg__visual">
                                            @if (!empty($first['carrier']))
                                                <img src="https://pics.avs.io/60/60/{{ strtoupper($first['carrier']) }}.png"
                                                    class="bkpd-leg__logo" alt="{{ $first['carrier'] }}"
                                                    onerror="this.style.display='none'">
                                            @endif

                                            <div class="bkpd-leg__dep">
                                                <div class="bkpd-leg__clock">{{ $first['departure_clock'] ?? '—' }}</div>
                                                <div class="bkpd-leg__city">{{ strtoupper($first['from'] ?? '') }}</div>
                                                @if (!empty($first['departure_city']))
                                                    <div class="bkpd-leg__city-name">{{ $first['departure_city'] }}</div>
                                                @endif
                                            </div>

                                            <div class="bkpd-leg__bridge">
                                                <div class="bkpd-leg__dur">{{ $fmtMins($durMins) }}</div>
                                                <div class="bkpd-leg__track">
                                                    <span class="bkpd-leg__dot"></span>
                                                    @foreach ($midApts as $via)
                                                        <span class="bkpd-leg__line"></span>
                                                        <span class="bkpd-leg__via">{{ strtoupper($via) }}</span>
                                                    @endforeach
                                                    <span class="bkpd-leg__line"></span>
                                                    <span class="bkpd-leg__dot"></span>
                                                </div>
                                                <div class="bkpd-leg__stops {{ $stops === 0 ? 'bkpd-leg__stops--direct' : '' }}">
                                                    {{ $stops === 0 ? 'Non-stop' : $stops . ' Stop' . ($stops > 1 ? 's' : '') }}
                                                </div>
                                            </div>

                                            <div class="bkpd-leg__arr">
                                                <div class="bkpd-leg__clock">
                                                    {{ $last['arrival_clock'] ?? '—' }}
                                                    @if (!empty($last['next_day_hint']))
                                                        <sup style="color:#cd1b4f;font-size:.6rem;">+1</sup>
                                                    @endif
                                                </div>
                                                <div class="bkpd-leg__city">{{ strtoupper($last['to'] ?? '') }}</div>
                                                @if (!empty($last['arrival_city']))
                                                    <div class="bkpd-leg__city-name">{{ $last['arrival_city'] }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        @if (count($segs) > 1)
                                            <div class="bkpd-segs">
                                                @foreach ($segs as $seg)
                                                    <div class="bkpd-seg">
                                                        <span class="bkpd-seg__flight">{{ $seg['carrier_display'] ?? ($seg['carrier'] ?? '') }}</span>
                                                        <span class="bkpd-seg__route">{{ strtoupper($seg['from'] ?? '') }} → {{ strtoupper($seg['to'] ?? '') }}</span>
                                                        <span class="bkpd-seg__time">{{ $seg['departure_clock'] ?? '' }} – {{ $seg['arrival_clock'] ?? '' }}</span>
                                                        @if (!empty($seg['cabin_code']))
                                                            <span class="bkpd-seg__cabin">{{ $seg['cabin_code'] }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="bkpd-seg-single">
                                                Flight <strong>{{ $first['carrier_display'] ?? ($first['carrier'] ?? '') }}</strong>
                                                @if (!empty($first['cabin_code']))
                                                    · {{ $first['cabin_code'] }} class
                                                @endif
                                                @if (!empty($first['booking_code']))
                                                    · Fare class {{ $first['booking_code'] }}
                                                @endif
                                                @if (!empty($first['equipment']))
                                                    · {{ $first['equipment'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (!empty($passengers))
                            <div class="bkpd-card mb-3">
                                <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-group"></i> Passengers</div>
                                <div class="bkpd-pax-list">
                                    @foreach ($passengers as $pi => $pax)
                                        @php
                                            $fullName = strtoupper(trim(($pax['title'] ?? '') . ' ' . ($pax['first_name'] ?? '') . ' ' . ($pax['last_name'] ?? '')));
                                            $initials = strtoupper(substr($pax['first_name'] ?? 'P', 0, 1) . substr($pax['last_name'] ?? '', 0, 1));
                                            $paxType = match ($pax['type'] ?? 'ADT') {
                                                'ADT' => 'Adult',
                                                'C06', 'C11' => 'Child',
                                                'INF' => 'Infant',
                                                default => ucfirst($pax['type'] ?? ''),
                                            };
                                        @endphp
                                        <div class="bkpd-pax">
                                            <div class="bkpd-pax__avatar">{{ $initials }}</div>
                                            <div class="bkpd-pax__body">
                                                <div class="bkpd-pax__name">{{ $fullName }}</div>
                                                <div class="bkpd-pax__meta">
                                                    {{ $paxType }}
                                                    @if (!empty($pax['nationality']))
                                                        · {{ strtoupper($pax['nationality']) }}
                                                    @endif
                                                    @if (!empty($pax['dob']))
                                                        · DOB: {{ \Carbon\Carbon::parse($pax['dob'])->format('d M Y') }}
                                                    @endif
                                                </div>
                                            </div>
                                            @if (!empty($pax['passport_no']))
                                                <div class="bkpd-pax__passport">
                                                    <i class="bx bx-id-card"></i> {{ $pax['passport_no'] }}
                                                    @if (!empty($pax['passport_exp']))
                                                        <span style="font-size:.65rem;color:#8492a6;font-weight:500;">
                                                            exp {{ \Carbon\Carbon::parse($pax['passport_exp'])->format('d M Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--green"><i class="bx bx-receipt"></i> Fare summary</div>
                            <div class="bkpd-fare">
                                <div class="bkpd-fare__row">
                                    <span>Total <span style="color:#8492a6;font-weight:400;">(× {{ $totalPax }} pax)</span></span>
                                    <span>{!! formatPrice($booking->total_amount) !!}</span>
                                </div>
                                @if (($booking->wallet_amount ?? 0) > 0)
                                    <div class="bkpd-fare__row">
                                        <span>Wallet applied</span>
                                        <span>− {!! formatPrice($booking->wallet_amount) !!}</span>
                                    </div>
                                @endif
                                @if ($isHold)
                                    <div class="bkpd-fare__row">
                                        <span>Hold deposit</span>
                                        <span style="color:#10b981;font-weight:800;">FREE</span>
                                    </div>
                                @endif
                                <div class="bkpd-fare__row bkpd-fare__row--total">
                                    <span>Amount</span>
                                    <span>{!! formatPrice($booking->total_amount) !!}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bkpd-card mb-3">
                            <div class="bkpd-card__section-head bkpd-card__section-head--slate"><i class="bx bx-info-circle"></i> Booking info</div>
                            <div class="bkpd-info-rows">
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Booking #</span>
                                    <span class="bkpd-info-row__val" style="color:#cd1b4f;font-weight:700;">{{ $booking->booking_number }}</span>
                                </div>
                                @if ($booking->sabre_record_locator)
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">PNR</span>
                                        <span class="bkpd-info-row__val" style="font-family:monospace;font-weight:700;">{{ $booking->sabre_record_locator }}</span>
                                    </div>
                                @endif
                                @if ($nonRefundable !== null)
                                    <div class="bkpd-info-row bkpd-info-row--refund">
                                        <span class="bkpd-info-row__label">Fare refund</span>
                                        <div class="bkpd-info-row__val bkpd-info-row__val--refund">
                                            @if ($nonRefundable)
                                                <span class="bkpd-refund-pill bkpd-refund-pill--no"><i class="bx bx-x-circle"></i> Non-refundable fare</span>
                                                <p class="bkpd-refund-note">Selected at booking from Sabre shop results. Airline change/cancel rules still apply.</p>
                                            @else
                                                <span class="bkpd-refund-pill bkpd-refund-pill--yes"><i class="bx bx-check-shield"></i> Refundable fare</span>
                                                <p class="bkpd-refund-note">Selected at booking from Sabre shop results. Refund eligibility depends on airline fare rules and timing.</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Payment</span>
                                    <span class="bkpd-info-row__val">{{ $payLabel }}</span>
                                </div>
                                <div class="bkpd-info-row">
                                    <span class="bkpd-info-row__label">Passengers</span>
                                    <span class="bkpd-info-row__val">{{ $paxStr }}</span>
                                </div>
                                @if (!empty($lead['email']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Email</span>
                                        <span class="bkpd-info-row__val" style="word-break:break-all;">{{ $lead['email'] }}</span>
                                    </div>
                                @endif
                                @if (!empty($lead['phone']))
                                    <div class="bkpd-info-row">
                                        <span class="bkpd-info-row__label">Phone</span>
                                        <span class="bkpd-info-row__val">{{ $lead['phone'] }}</span>
                                    </div>
                                @endif
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
                                                (by {{ formatBookingCancelledByLabel($booking->cancelled_by) }})
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
                        <form action="{{ route('admin.bookings.flights.status', $booking->id) }}" method="POST"
                            class="bkpd-card mb-3 admin-flight-status admin-booking-status-form"
                            data-booking-number="{{ $booking->booking_number }}"
                            data-total-amount="{{ number_format((float) $booking->total_amount, 2) }}"
                            data-vendor-name="{{ e($vendorLabel) }}"
                            data-current-payment-status="{{ $booking->payment_status }}"
                            data-current-booking-status="{{ $booking->booking_status }}"
                            data-current-ticket-status="{{ $booking->ticket_status }}"
                            data-booking-type="flight">
                            @csrf
                            <div class="bkpd-card__section-head"><i class="bx bx-edit"></i> Update status</div>
                            <div class="bkpd-actions" style="padding:0 16px 16px;">
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Payment status</div>
                                    @if ($booking->booking_status !== 'cancelled')
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
                                        @foreach (['confirmed' => 'Confirmed', 'pending' => 'Pending', 'hold' => 'On hold'] as $val => $label)
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
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Ticket status</div>
                                    @foreach (['pending' => 'Pending', 'issued' => 'Issued', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $val => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="ticket_status" id="ticket_{{ $val }}" value="{{ $val }}"
                                                {{ old('ticket_status', $booking->ticket_status) === $val ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ticket_{{ $val }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="bkp-btn bkp-btn--primary w-100">Save changes</button>
                            </div>
                        </form>

                        <div class="bkpd-card">
                            <div class="bkpd-card__section-head"><i class="bx bx-cog"></i> Actions</div>
                            <div class="bkpd-actions">
                                @if ($status === 'cancelled')
                                    <p class="bkpd-no-action"><i class="bx bx-x-circle"></i> Booking has been cancelled.</p>
                                @elseif ($isHold)
                                    <form action="{{ route('admin.bookings.flights.release-hold', $booking->id) }}" method="POST"
                                        onsubmit="return confirm('Release hold on PNR {{ $booking->sabre_record_locator }}? The booking will be cancelled at the airline end.');">
                                        @csrf
                                        <button type="submit" class="bkp-btn bkp-btn--warning w-100">
                                            <i class="bx bx-x-circle"></i> Release Hold
                                        </button>
                                    </form>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
                                        Releases the PNR at Sabre — no charges since no payment was made.
                                    </p>
                                @elseif ($status === 'confirmed' && $booking->payment_status === 'paid')
                                    <button type="button" class="bkp-btn bkp-btn--danger w-100 cancel-booking-btn">
                                        <i class="bx bx-x"></i> Cancel Booking
                                    </button>
                                    <p style="font-size:.7rem;color:#8492a6;margin-top:10px;text-align:center;line-height:1.4;">
                                        Cancellation is sent to the airline via our GDS. If the carrier does not allow cancel for this fare, you will see an error and the booking will stay active.
                                    </p>
                                @else
                                    <p class="bkpd-no-action"><i class="bx bx-info-circle"></i> No supplier cancel action available for this state.</p>
                                @endif
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
        'Cancel this flight booking at the airline?\n\n' +
        'If payment was collected, ' + amount + ' AED will be credited to ' + vendor + '\'s wallet.\n\n' +
        'Continue?';
    if (!confirm(message)) {
        return;
    }

    $.post("{{ route('admin.bookings.flights.cancel', $booking->id) }}", {
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
