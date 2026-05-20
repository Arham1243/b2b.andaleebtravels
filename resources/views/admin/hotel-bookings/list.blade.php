@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.hotel-bookings.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">{{ $title ?? 'Hotel Bookings' }}</h3>
                            @if (isset($filterVendor) && $filterVendor)
                                <p class="small text-muted mb-0 mt-2">
                                    Showing bookings for <strong>{{ $filterVendor->name }}</strong>
                                    &middot; <a href="{{ route('admin.vendors.show', $filterVendor) }}">Vendor profile</a>
                                    &middot; <a href="{{ route('admin.hotel-bookings.index') }}">All hotel bookings</a>
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('admin.hotels.start') }}" class="themeBtn" target="_blank" rel="noopener">
                            <i class="bx bx-search"></i> Search Hotels
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Booking Number</th>
                                    <th>Vendor</th>
                                    <th>Hotel</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Supplier</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Booking Status</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookings as $booking)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.hotel-bookings.show', $booking->id) }}"
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
                                        <td>
                                            <span title="{{ $booking->hotel_name }}"
                                                style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;">
                                                {{ $booking->hotel_name ?? '—' }}
                                            </span>
                                        </td>
                                        <td>{{ $booking->check_in_date?->format('d M Y') ?? '—' }}</td>
                                        <td>{{ $booking->check_out_date?->format('d M Y') ?? '—' }}</td>
                                        <td>{{ formatBookingSupplierLabel($booking->supplier) }}</td>
                                        <td>{!! formatPrice($booking->total_amount) !!}</td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'pending' ? 'warning' : ($booking->payment_status === 'refunded' ? 'info' : 'danger')) }}">
                                                {{ ucfirst($booking->payment_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-{{ in_array($booking->booking_status, ['confirmed', 'completed'], true) ? 'success' : ($booking->booking_status === 'pending' ? 'warning' : ($booking->booking_status === 'refunded' ? 'info' : 'danger')) }}">
                                                {{ ucfirst($booking->booking_status) }}
                                            </span>
                                        </td>
                                        <td>{{ formatDateTime($booking->created_at) }}</td>
                                        <td>
                                            <a style="white-space: nowrap;"
                                                href="{{ route('admin.hotel-bookings.show', $booking->id) }}"
                                                class="themeBtn"><i class='bx bxs-show'></i>View Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">No hotel bookings found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($bookings->hasPages())
                        <div class="px-3 py-3">
                            {{ $bookings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
