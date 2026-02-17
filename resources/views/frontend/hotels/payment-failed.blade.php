@extends('frontend.layouts.main')
@section('content')
    <div class="py-2">
        <div class="container">
            <nav class="breadcrumb-nav">
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.index') }}" class="breadcrumb-link">Home</a>
                        <i class='bx bx-chevron-right breadcrumb-separator'></i>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.hotels.index') }}" class="breadcrumb-link">Hotels</a>
                        <i class='bx bx-chevron-right breadcrumb-separator'></i>
                    </li>
                    <li class="breadcrumb-item active">
                        Booking Failed
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <section class="section-gap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="modern-card text-center">
                        <div class="error-icon mb-4">
                            <i class='bx bx-x-circle' style="font-size: 80px; color: #dc3545;"></i>
                        </div>

                        <h2 class="mb-3">Booking Failed</h2>
                        <p class="text-muted mb-4">
                            Unfortunately, we were unable to complete your hotel booking. This could be due to payment failure or unavailability of the selected hotel.
                        </p>

                        @if($booking)
                        <div class="booking-details-card mb-4">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <label>Booking Number</label>
                                        <strong>{{ $booking->booking_number }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <label>Hotel Name</label>
                                        <strong>{{ $booking->hotel_name }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Check-in Date</label>
                                        <strong>{{ $booking->check_in_date->format('d M, Y') }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Check-out Date</label>
                                        <strong>{{ $booking->check_out_date->format('d M, Y') }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <label>Status</label>
                                        <strong class="text-danger">{{ ucfirst($booking->payment_status) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="alert alert-warning">
                            <i class='bx bx-info-circle'></i>
                            <strong>What should I do next?</strong>
                            <ul class="text-start mt-2 mb-0">
                                <li>Please try booking again with a different payment method</li>
                                <li>Contact our support team if you need assistance</li>
                                <li>Check if your payment method has sufficient funds</li>
                            </ul>
                        </div>

                        <div class="contact-info mb-4">
                            <h5>Need Help?</h5>
                            <p class="mb-2">
                                <i class='bx bx-phone'></i> 
                                <a href="tel:+971525748986">+971 52 574 8986</a>
                            </p>
                            <p class="mb-0">
                                <i class='bx bx-envelope'></i> 
                                <a href="mailto:info@andaleebtours.com">info@andaleebtours.com</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css')
<style>
    .booking-details-card {
        background: #f8f9fa;
        padding: 30px;
        border-radius: 10px;
        text-align: left;
    }

    .detail-item {
        margin-bottom: 10px;
    }

    .detail-item label {
        display: block;
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .detail-item strong {
        display: block;
        font-size: 16px;
        color: #212529;
    }

    .contact-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
    }

    .contact-info a {
        color: var(--color-primary);
        text-decoration: none;
    }

    .contact-info a:hover {
        text-decoration: underline;
    }

    .btn-secondary-custom {
        display: inline-block;
        padding: 12px 30px;
        background: #6c757d;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-secondary-custom:hover {
        background: #5a6268;
        color: white;
    }
</style>
@endpush
