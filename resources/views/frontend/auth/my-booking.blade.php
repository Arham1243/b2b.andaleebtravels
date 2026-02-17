@extends('frontend.layouts.main')
@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">

                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="heading">Find Your Booking</h2>
                            <p>Access, print, or cancel your booking instantly using the email from your reservation. No
                                sign-in needed.</p>
                        </div>

                        <form action="{{ route('booking.send') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="custom-input" name="email" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Booking Reference Number</label>
                                <input type="text" class="custom-input" name="order_number">
                            </div>
                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                            </div>

                            <button type="submit" class="btn-primary-custom">Submit</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
@push('js')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const recaptcha = grecaptcha.getResponse();

                if (!recaptcha) {
                    e.preventDefault();
                    showMessage("Please complete the reCAPTCHA before submitting.", "error");
                    return false;
                }
            });

        });
    </script>
@endpush
