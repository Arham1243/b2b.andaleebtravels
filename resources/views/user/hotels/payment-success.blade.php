@extends('user.layouts.main')
@section('content')
    <div class="hc-page">
        <div class="container">
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.hotels.index') }}">Hotels</a>
                <i class="bx bx-chevron-right"></i>
                <span>Booking Confirmed</span>
            </nav>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="hc-card text-center">
                        <div class="hc-result-icon hc-result-icon--success">
                            <i class="bx bx-check-circle"></i>
                        </div>

                        <h2 class="hc-result-title">Booking Confirmed!</h2>
                        <p class="hc-result-text">Thank you for your booking. Your hotel reservation has been confirmed successfully.</p>

                        <div class="hc-result-details">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Booking Number</span>
                                        <span class="hc-result-item__value">{{ $booking->booking_number }}</span>
                                    </div>
                                </div>

                                @if($booking->yalago_booking_reference)
                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Confirmation Ref</span>
                                        <span class="hc-result-item__value">{{ $booking->yalago_booking_reference }}</span>
                                    </div>
                                </div>
                                @endif

                                <div class="col-md-12">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Hotel</span>
                                        <span class="hc-result-item__value">{{ $booking->hotel_name }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Check-in</span>
                                        <span class="hc-result-item__value">{{ $booking->check_in_date->format('d M, Y') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Check-out</span>
                                        <span class="hc-result-item__value">{{ $booking->check_out_date->format('d M, Y') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Nights</span>
                                        <span class="hc-result-item__value">{{ $booking->nights }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Total Amount</span>
                                        <span class="hc-result-item__value hc-result-item__value--primary">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Guest</span>
                                        <span class="hc-result-item__value">{{ $booking->lead_full_name }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Email</span>
                                        <span class="hc-result-item__value">{{ $booking->lead_email }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hc-alert mt-3">
                            <i class="bx bx-info-circle"></i>
                            <span>A confirmation email has been sent to <strong>{{ $booking->lead_email }}</strong>.</span>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <a href="{{ route('user.dashboard') }}" class="hc-btn hc-btn--primary">
                                <i class="bx bx-home"></i> Dashboard
                            </a>
                            <a href="{{ route('user.hotels.index') }}" class="hc-btn hc-btn--outline">
                                <i class="bx bx-search"></i> Search Hotels
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
