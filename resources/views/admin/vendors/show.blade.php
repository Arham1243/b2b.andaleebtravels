@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.show', $vendor) }}
            <div class="table-container universal-table">
                {{-- Vendor Info Card --}}
                <div class="custom-sec mb-4">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Vendor Details</h3>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.vendors.change-status', $vendor->id) }}"
                                class="themeBtn {{ $vendor->status === 'active' ? 'themeBtn--danger' : 'themeBtn--success' }}">
                                {{ $vendor->status === 'active' ? 'Make Inactive' : 'Make Active' }}
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="fw-semibold text-muted" style="width:140px;">Name</td>
                                    <td>{{ $vendor->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Email</td>
                                    <td>{{ $vendor->email }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Username</td>
                                    <td>{{ $vendor->username }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="fw-semibold text-muted" style="width:140px;">Agent Code</td>
                                    <td>{{ $vendor->agent_code }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Wallet Balance</td>
                                    <td class="fw-bold">{!! formatPrice($vendor->main_balance ?? 0) !!}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Status</td>
                                    <td>
                                        <span
                                            class="badge rounded-pill bg-{{ $vendor->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($vendor->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Registered</td>
                                    <td>{{ formatDateTime($vendor->created_at) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Wallet Ledger --}}
                <div class="custom-sec mb-4">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Wallet Ledger</h3>
                        </div>
                        <span class="badge bg-primary rounded-pill">{{ $walletLedger->count() }} entries</span>
                    </div>
                    @if ($walletLedger->isNotEmpty())
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Balance Before</th>
                                        <th>Balance After</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($walletLedger as $index => $entry)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $entry->created_at->format('d M Y, h:i A') }}</td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ $entry->isCredit() ? 'success' : 'danger' }}">
                                                    {{ ucfirst($entry->type) }}
                                                </span>
                                            </td>
                                            <td class="fw-bold {{ $entry->isCredit() ? 'text-success' : 'text-danger' }}">
                                                {{ $entry->isCredit() ? '+' : '-' }}{!! formatPrice($entry->amount) !!}
                                            </td>
                                            <td>{!! formatPrice($entry->balance_before) !!}</td>
                                            <td>{!! formatPrice($entry->balance_after) !!}</td>
                                            <td>{{ $entry->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-wallet" style="font-size: 36px;"></i>
                            <p class="mt-2 mb-0">No wallet transactions yet.</p>
                        </div>
                    @endif
                </div>

                {{-- Hotel Bookings --}}
                <div class="custom-sec mb-4">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Hotel Bookings</h3>
                        </div>
                        <span class="badge bg-primary rounded-pill">{{ $hotelBookings->count() }} bookings</span>
                    </div>
                    @if ($hotelBookings->isNotEmpty())
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Hotel</th>
                                        <th>Supplier</th>
                                        <th>Check In / Out</th>
                                        <th>Amount</th>
                                        <th>Wallet Used</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Booked On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hotelBookings as $booking)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold" style="color: var(--color-primary);">
                                                    {{ $booking->booking_number }}
                                                </span>
                                            </td>
                                            <td>
                                                <span title="{{ $booking->hotel_name }}"
                                                    style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;">
                                                    {{ $booking->hotel_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-uppercase" style="font-size:10px;">
                                                    {{ $booking->supplier ?? 'yalago' }}
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap; font-size:12px;">
                                                {{ $booking->check_in_date?->format('d M Y') }} &mdash;
                                                {{ $booking->check_out_date?->format('d M Y') }}
                                            </td>
                                            <td class="fw-bold">{!! formatPrice($booking->total_amount) !!}</td>
                                            <td>{!! formatPrice($booking->wallet_amount ?? 0) !!}</td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($booking->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ $booking->booking_status === 'confirmed' ? 'success' : ($booking->booking_status === 'pending' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($booking->booking_status) }}
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap; font-size:12px;">
                                                {{ $booking->created_at->format('d M Y, h:i A') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-building-house" style="font-size: 36px;"></i>
                            <p class="mt-2 mb-0">No hotel bookings yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
