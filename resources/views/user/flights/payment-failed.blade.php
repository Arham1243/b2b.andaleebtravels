@extends('user.layouts.main')
@section('content')
    <div class="hc-page">
        <div class="container">
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.flights.index') }}">Flights</a>
                <i class="bx bx-chevron-right"></i>
                <span>Payment Failed</span>
            </nav>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="hc-card text-center">
                        <div class="hc-result-icon hc-result-icon--error">
                            <i class="bx bx-x-circle"></i>
                        </div>

                        <h2 class="hc-result-title">Payment Failed</h2>
                        <p class="hc-result-text">We couldn't complete your payment. Please try again or contact support.</p>

                        @if (!empty($booking))
                            <div class="hc-result-details">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="hc-result-item">
                                            <span class="hc-result-item__label">Booking Number</span>
                                            <span class="hc-result-item__value">{{ $booking->booking_number }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="hc-result-item">
                                            <span class="hc-result-item__label">Route</span>
                                            <span class="hc-result-item__value">{{ $booking->from_airport }} ? {{ $booking->to_airport }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <a href="{{ route('user.flights.index') }}" class="hc-btn hc-btn--primary">
                                <i class="bx bx-search"></i> Search Flights
                            </a>
                            <a href="{{ route('user.dashboard') }}" class="hc-btn hc-btn--outline">
                                <i class="bx bx-home"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
