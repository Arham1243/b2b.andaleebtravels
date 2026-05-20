@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.flight-bookings.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">{{ $title ?? 'Flight Bookings' }}</h3>
                            @if (isset($filterVendor) && $filterVendor)
                                <p class="small text-muted mb-0 mt-2">
                                    Showing bookings for <strong>{{ $filterVendor->name }}</strong>
                                    &middot; <a href="{{ route('admin.vendors.show', $filterVendor) }}">Vendor profile</a>
                                    &middot; <a href="{{ route('admin.flight-bookings.index') }}">All flight bookings</a>
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('admin.flights.start') }}" class="themeBtn" target="_blank" rel="noopener">
                            <i class="bx bx-search"></i> Search Flights
                        </a>
                    </div>

                    @if ($bookings->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bx bx-plane" style="font-size: 36px;"></i>
                            <p class="mt-2 mb-0">No flight bookings found.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Booking Number</th>
                                        <th>Vendor</th>
                                        <th>Route</th>
                                        <th>Departure</th>
                                        <th>Return</th>
                                        <th>PNR</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Payment Status</th>
                                        <th>Booking Status</th>
                                        <th>Ticket Status</th>
                                        <th>Date</th>
                                        <th class="no-sort">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bookings as $booking)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.flight-bookings.show', $booking->id) }}"
                                                    class="link">{{ $booking->booking_number }}</a>
                                            </td>
                                            <td>
                                                @if ($booking->vendor)
                                                    <a href="{{ route('admin.vendors.show', $booking->vendor) }}"
                                                        class="link">{{ $booking->vendor->name }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="white-space:nowrap;">
                                                {{ strtoupper($booking->from_airport ?? '—') }}
                                                <i class="bx bx-right-arrow-alt" style="vertical-align:middle;color:#aaa;"></i>
                                                {{ strtoupper($booking->to_airport ?? '—') }}
                                            </td>
                                            <td>{{ $booking->departure_date?->format('d M Y') ?? '—' }}</td>
                                            <td>{{ $booking->return_date?->format('d M Y') ?? '—' }}</td>
                                            <td>{{ $booking->sabre_record_locator ?? '—' }}</td>
                                            <td>{!! formatPrice($booking->total_amount) !!}</td>
                                            <td>
                                                @php
                                                    $payMethod = strtolower((string) ($booking->payment_method ?? ''));
                                                    $payLabel = match ($payMethod) {
                                                        'payby' => 'Card (PayBy)',
                                                        'tabby' => 'Tabby',
                                                        'tamara' => 'Tamara',
                                                        'wallet' => 'Wallet',
                                                        'hold' => 'Hold',
                                                        default => $payMethod !== '' ? ucfirst(str_replace('_', ' ', $payMethod)) : ($booking->booking_status === 'hold' ? 'Hold' : '—'),
                                                    };
                                                @endphp
                                                {{ $payLabel }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'pending' ? 'warning' : ($booking->payment_status === 'refunded' ? 'info' : 'danger')) }}">
                                                    {{ ucfirst($booking->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ in_array($booking->booking_status, ['confirmed', 'completed'], true) ? 'success' : ($booking->booking_status === 'hold' ? 'info' : ($booking->booking_status === 'pending' ? 'warning' : ($booking->booking_status === 'refunded' ? 'info' : 'danger'))) }}">
                                                    {{ ucfirst($booking->booking_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ $booking->ticket_status === 'issued' ? 'success' : ($booking->ticket_status === 'pending' ? 'warning' : ($booking->ticket_status === 'refunded' ? 'info' : 'danger')) }}">
                                                    {{ ucfirst($booking->ticket_status ?? '—') }}
                                                </span>
                                            </td>
                                            <td>{{ formatDateTime($booking->created_at) }}</td>
                                            <td>
                                                <a style="white-space: nowrap;"
                                                    href="{{ route('admin.flight-bookings.show', $booking->id) }}"
                                                    class="themeBtn"><i class='bx bxs-show'></i>View Details</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($bookings->hasPages())
                            <div class="px-3 py-3">
                                {{ $bookings->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
