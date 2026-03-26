@extends('user.layouts.main')
@section('content')
    <div class="hc-page">
        <div class="container">
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.flights.index') }}">Flights</a>
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
                        <p class="hc-result-text">Thank you. Your flight reservation has been confirmed successfully.</p>

                        <div class="hc-result-details">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Booking Number</span>
                                        <span class="hc-result-item__value">{{ $booking->booking_number }}</span>
                                    </div>
                                </div>

                                @if($booking->sabre_record_locator)
                                    <div class="col-md-6">
                                        <div class="hc-result-item">
                                            <span class="hc-result-item__label">Record Locator</span>
                                            <span class="hc-result-item__value">{{ $booking->sabre_record_locator }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-12">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Route</span>
                                        <span class="hc-result-item__value">{{ $booking->from_airport }} ? {{ $booking->to_airport }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Departure</span>
                                        <span class="hc-result-item__value">{{ $booking->departure_date?->format('d M, Y') }}</span>
                                    </div>
                                </div>

                                @if($booking->return_date)
                                    <div class="col-md-6">
                                        <div class="hc-result-item">
                                            <span class="hc-result-item__label">Return</span>
                                            <span class="hc-result-item__value">{{ $booking->return_date?->format('d M, Y') }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Passengers</span>
                                        <span class="hc-result-item__value">{{ $booking->adults + $booking->children + $booking->infants }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Total Amount</span>
                                        <span class="hc-result-item__value hc-result-item__value--primary"><span class="dirham">D</span> {{ number_format($booking->total_amount, 2) }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Lead Passenger</span>
                                        <span class="hc-result-item__value">{{ $booking->lead_full_name }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Email</span>
                                        <span class="hc-result-item__value">{{ data_get($booking->passengers_data, 'lead.email') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <a href="{{ route('user.dashboard') }}" class="hc-btn hc-btn--primary">
                                <i class="bx bx-home"></i> Dashboard
                            </a>
                            <a href="{{ route('user.flights.index') }}" class="hc-btn hc-btn--outline">
                                <i class="bx bx-search"></i> Search Flights
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
