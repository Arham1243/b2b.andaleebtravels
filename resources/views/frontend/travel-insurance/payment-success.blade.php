@extends('frontend.layouts.main')
@section('content')
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">

                    <div class="card status-card p-4 p-md-5 text-center">

                        <div>
                            <div class="status-icon-wrapper success">
                                <i class='bx bxs-check-circle'></i>
                            </div>
                        </div>

                        <h1 class="h3 fw-bold mb-2">Insurance Payment Successful!</h1>
                        <p class="text-muted mb-4">
                            Thank you for purchasing travel insurance. Your policy confirmation has been sent to your email.
                        </p>

                        @if($insurance)
                            <div class="transaction-details mb-4">
                                <div class="detail-row">
                                    <span class="text-muted">Insurance Number</span>
                                    <span class="fw-medium">{{ $insurance->insurance_number }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="text-muted">Plan</span>
                                    <span class="fw-medium">{{ $insurance->plan_title }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="text-muted">Coverage Period</span>
                                    <span class="fw-medium">
                                        {{ $insurance->start_date ? $insurance->start_date->format('M d, Y') : 'N/A' }} - 
                                        {{ $insurance->return_date ? $insurance->return_date->format('M d, Y') : 'N/A' }}
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="text-muted">Travelers</span>
                                    <span class="fw-medium">
                                        {{ $insurance->total_adults }} Adult(s)
                                        @if($insurance->total_children > 0), {{ $insurance->total_children }} Child(ren)@endif
                                        @if($insurance->total_infants > 0), {{ $insurance->total_infants }} Infant(s)@endif
                                    </span>
                                </div>
                                <div class="detail-row total">
                                    <span>Amount Paid</span>
                                    <span>{{ number_format($insurance->total_premium, 2) }} {{ $insurance->currency }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="text-muted">Payment Status</span>
                                    <span class="badge bg-success">{{ ucfirst($insurance->payment_status) }}</span>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info mb-4">
                                <p class="mb-0 small">Insurance details not available. Please check your email for confirmation.</p>
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            @auth
                                <a href="{{ route('user.dashboard') }}" class="btn btn-primary-theme btn-lg">View My Dashboard</a>
                            @endauth
                            <a href="{{ route('frontend.index') }}" class="btn btn-link text-decoration-none text-muted">Return to Home</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
