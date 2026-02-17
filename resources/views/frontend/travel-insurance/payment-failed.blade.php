@extends('frontend.layouts.main')
@section('content')
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">

                    <div class="card status-card p-4 p-md-5 text-center">

                        <div>
                            <div class="status-icon-wrapper error">
                                <i class='bx bxs-error-circle'></i>
                            </div>
                        </div>

                        <h1 class="h3 fw-bold mb-2">Payment Failed</h1>
                        <p class="text-muted mb-4">
                            Unfortunately, we couldn't process your travel insurance payment.
                        </p>

                        @if(session('error'))
                            <div class="alert alert-danger border-0 bg-opacity-10 mb-4" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <i class='bx bx-error-circle mt-1'></i>
                                    <div class="text-start">
                                        <strong class="small">{{ session('error') }}</strong>   
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($insurance)
                            <div class="transaction-details mb-4">
                                <div class="detail-row">
                                    <span class="text-muted">Insurance Number</span>
                                    <span class="fw-medium">{{ $insurance->insurance_number }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="text-muted">Payment Status</span>
                                    <span class="badge bg-danger">{{ ucfirst($insurance->payment_status) }}</span>
                                </div>
                            </div>
                        @endif

                        <p class="small text-muted mb-4">
                            Please try again or contact our support team if the issue persists. You can also try a different payment method.
                        </p>

                        <div class="d-grid gap-2">
                            <a href="{{ route('frontend.travel-insurance.index') }}" class="btn btn-primary-theme btn-lg">Try Again</a>
                            <a href="{{ route('frontend.contact-us') }}" class="btn btn-outline-primary">Contact Support</a>
                            <a href="{{ route('frontend.index') }}" class="btn btn-link text-decoration-none text-muted">Return to Home</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
