@extends('frontend.layouts.main')
@section('content')
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">

                    <div class="card status-card p-4 p-md-5 text-center">

                        <!-- Success Icon -->
                        <div>
                            <div class="status-icon-wrapper success">
                                <i class='bx bx-info-circle'></i>
                            </div>
                        </div>

                        <!-- Headline -->
                        <h1 class="h3 fw-bold mb-2">Password Reset Email Sent</h1>
                        <p class="text-muted mb-4">
                            A password reset link has been sent to the email address below, if it’s associated with an
                            account.
                            <br>
                            <strong>{{ $_GET['email'] }}</strong>
                        </p>

                        <!-- Actions -->
                        <div class="d-grid gap-2">
                            <a href="{{ route('auth.login') }}" class="btn btn-primary-theme btn-lg">
                                Back to Login
                            </a>
                            <a href="{{ route('frontend.index') }}" class="btn btn-link text-decoration-none text-muted">
                                Return to Home
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
