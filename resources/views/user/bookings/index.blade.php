@extends('user.layouts.main')

@section('css')
    <style>
        .bookings-page {
            padding: 24px 0;
        }

        .bookings-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .bookings-section__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .bookings-section__title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }

        .bookings-section__title i {
            font-size: 24px;
            color: var(--color-primary);
        }

        .bookings-section__count {
            background: var(--color-primary);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 20px;
        }

        .bookings-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        .bookings-table thead th {
            background: #f8f9fa;
            padding: 10px 12px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eee;
            white-space: nowrap;
        }

        .bookings-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .bookings-table tbody tr:hover {
            background: #fafbfc;
        }

        .bookings-table tbody tr:last-child td {
            border-bottom: none;
        }

        .booking-number {
            font-weight: 600;
            color: var(--color-primary);
        }

        .booking-hotel-name {
            font-weight: 500;
            color: #1a1a2e;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .booking-dates {
            white-space: nowrap;
            font-size: 12px;
            color: #555;
        }

        .booking-amount {
            font-weight: 600;
            color: #1a1a2e;
        }

        .booking-supplier {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            background: #e8f4fd;
            color: #1976d2;
            display: inline-block;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-status--confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-status--pending {
            background: #fff3e0;
            color: #e65100;
        }

        .badge-status--cancelled {
            background: #fce4ec;
            color: #c62828;
        }

        .badge-status--failed {
            background: #fce4ec;
            color: #c62828;
        }

        .badge-status--paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .booking-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .btn-cancel-booking {
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: 1px solid #dc3545;
            background: transparent;
            color: #dc3545;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-cancel-booking:hover {
            background: #dc3545;
            color: #fff;
        }

        .bookings-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .bookings-empty i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
        }

        .bookings-empty p {
            margin: 0;
            font-size: 14px;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
@endsection

@section('content')
    <div class="bookings-page">
        <div class="container">

            {{-- Hotel Bookings --}}
            <div class="bookings-section">
                <div class="bookings-section__header">
                    <div class="bookings-section__title">
                        <i class="bx bx-building-house"></i>
                        Hotel Bookings
                    </div>
                    <span class="bookings-section__count">{{ $hotelBookings->count() }}</span>
                </div>

                @if ($hotelBookings->isNotEmpty())
                    <div class="table-responsive">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Hotel</th>
                                    <th>Supplier</th>
                                    <th>Check In / Out</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Booked On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hotelBookings as $booking)
                                    <tr>
                                        <td><span class="booking-number">{{ $booking->booking_number }}</span></td>
                                        <td><span class="booking-hotel-name" title="{{ $booking->hotel_name }}">{{ $booking->hotel_name }}</span></td>
                                        <td><span class="booking-supplier">{{ $booking->supplier ?? 'yalago' }}</span></td>
                                        <td>
                                            <div class="booking-dates">
                                                {{ $booking->check_in_date?->format('d M Y') }} &mdash; {{ $booking->check_out_date?->format('d M Y') }}
                                            </div>
                                        </td>
                                        <td><span class="booking-amount">{!! formatPrice($booking->total_amount) !!}</span></td>
                                        <td>
                                            <span class="badge-status badge-status--{{ $booking->payment_status }}">
                                                {{ ucfirst($booking->payment_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status badge-status--{{ $booking->booking_status }}">
                                                {{ ucfirst($booking->booking_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="booking-dates">{{ $booking->created_at->format('d M Y, h:i A') }}</div>
                                        </td>
                                        <td>
                                            <div class="booking-actions">
                                                @if ($booking->booking_status === 'confirmed' && $booking->payment_status === 'paid')
                                                    <button type="button"
                                                        class="btn-cancel-booking cancel-booking-btn"
                                                        data-booking-id="{{ $booking->id }}">
                                                        <i class="bx bx-x"></i> Cancel
                                                    </button>
                                                @else
                                                    <span class="text-muted">&mdash;</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bookings-empty">
                        <i class="bx bx-building-house"></i>
                        <p>No hotel bookings yet.</p>
                    </div>
                @endif
            </div>

            {{-- Future: Flight Bookings --}}
            {{-- <div class="bookings-section">
                <div class="bookings-section__header">
                    <div class="bookings-section__title">
                        <i class="bx bx-plane"></i>
                        Flight Bookings
                    </div>
                    <span class="bookings-section__count">0</span>
                </div>
                <div class="bookings-empty">
                    <i class="bx bx-plane"></i>
                    <p>No flight bookings yet.</p>
                </div>
            </div> --}}

            {{-- Future: Tour Bookings --}}
            {{-- <div class="bookings-section">
                <div class="bookings-section__header">
                    <div class="bookings-section__title">
                        <i class="bx bx-map-alt"></i>
                        Tour Bookings
                    </div>
                    <span class="bookings-section__count">0</span>
                </div>
                <div class="bookings-empty">
                    <i class="bx bx-map-alt"></i>
                    <p>No tour bookings yet.</p>
                </div>
            </div> --}}

        </div>
    </div>

    {{-- Cancellation Modal --}}
    <div class="modal fade" id="cancelBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancellation Charges</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="cancelBookingModalBody">
                    <div class="text-center py-5">Loading cancellation policy...</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).on('click', '.cancel-booking-btn', function() {
            const bookingId = $(this).data('booking-id');

            const modal = new bootstrap.Modal(
                document.getElementById('cancelBookingModal')
            );

            $('#cancelBookingModalBody').html(
                '<div class="text-center py-5">Loading cancellation policy...</div>'
            );

            modal.show();

            $.post(
                "{{ route('user.bookings.hotels.cancellation-charges') }}", {
                    booking_id: bookingId,
                    _token: "{{ csrf_token() }}"
                }
            )
            .done(function(html) {
                $('#cancelBookingModalBody').html(html);
            })
            .fail(function() {
                $('#cancelBookingModalBody').html(
                    '<p class="text-danger text-center py-3">Failed to load cancellation policy. Please try again.</p>'
                );
            });
        });
    </script>
@endpush
