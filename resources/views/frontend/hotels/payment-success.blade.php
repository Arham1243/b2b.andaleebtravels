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
                        Booking Confirmed
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
                        <div class="success-icon mb-4">
                            <i class='bx bx-check-circle' style="font-size: 80px; color: #28a745;"></i>
                        </div>

                        <h2 class="mb-3">Booking Confirmed!</h2>
                        <p class="text-muted mb-4">
                            Thank you for your booking. Your hotel reservation has been confirmed successfully.
                        </p>

                        <div class="booking-details-card mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Booking Number</label>
                                        <strong>{{ $booking->booking_number }}</strong>
                                    </div>
                                </div>
                                
                                @if($booking->yalago_booking_reference)
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Confirmation Reference</label>
                                        <strong>{{ $booking->yalago_booking_reference }}</strong>
                                    </div>
                                </div>
                                @endif

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

                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Number of Nights</label>
                                        <strong>{{ $booking->nights }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Total Amount</label>
                                        <strong>{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <label>Guest Name</label>
                                        <strong>{{ $booking->lead_full_name }}</strong>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <label>Email</label>
                                        <strong>{{ $booking->lead_email }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class='bx bx-info-circle'></i>
                            A confirmation email has been sent to <strong>{{ $booking->lead_email }}</strong> with all the booking details.
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('frontend.index') }}" class="btn-primary-custom">
                                <i class='bx bx-home'></i> Back to Home
                            </a>
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
</style>
@endpush
