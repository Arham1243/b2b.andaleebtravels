@extends('user.layouts.main')
@section('content')
    <div class="hc-page">
        <div class="container">
            <nav class="hd-breadcrumb">
                <a href="{{ route('user.hotels.index') }}">Hotels</a>
                <i class="bx bx-chevron-right"></i>
                <span>Booking Failed</span>
            </nav>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="hc-card text-center">
                        <div class="hc-result-icon hc-result-icon--error">
                            <i class="bx bx-x-circle"></i>
                        </div>

                        <h2 class="hc-result-title">Booking Failed</h2>
                        <p class="hc-result-text">Unfortunately, we were unable to complete your hotel booking. This could be due to payment failure or unavailability.</p>

                        @if($booking)
                        <div class="hc-result-details">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Booking Number</span>
                                        <span class="hc-result-item__value">{{ $booking->booking_number }}</span>
                                    </div>
                                </div>

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

                                <div class="col-md-12">
                                    <div class="hc-result-item">
                                        <span class="hc-result-item__label">Status</span>
                                        <span class="hc-result-item__value" style="color: #e74c3c;">{{ ucfirst($booking->payment_status) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="hc-alert hc-alert--warning mt-3" style="text-align: left;">
                            <i class="bx bx-info-circle"></i>
                            <div>
                                <strong>What should I do next?</strong>
                                <ul style="margin: 8px 0 0; padding-left: 18px; font-size: 0.82rem;">
                                    <li>Try booking again with a different payment method</li>
                                    <li>Contact our support team if you need assistance</li>
                                    <li>Check if your payment method has sufficient funds</li>
                                </ul>
                            </div>
                        </div>

                        <div class="hc-result-contact">
                            <div class="hc-result-contact__title">Need Help?</div>
                            <div class="hc-result-contact__row">
                                <a href="tel:+971525748986"><i class="bx bx-phone"></i> +971 52 574 8986</a>
                                <a href="mailto:info@andaleebtours.com"><i class="bx bx-envelope"></i> info@andaleebtours.com</a>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <a href="{{ route('user.hotels.index') }}" class="hc-btn hc-btn--primary">
                                <i class="bx bx-search"></i> Try Again
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
