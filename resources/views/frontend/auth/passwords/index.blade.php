@extends('frontend.layouts.main')
@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">

                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="heading">Forgot Password?</h2>
                            <p>Enter your email and we'll send you a reset link</p>
                        </div>

                        <form action="{{ route('password.email') }}" method="POST">
                            @csrf
                            <!-- Email Field -->
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name='email' class="custom-input" required value="{{ old('email') }}">
                                @error('email')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>


                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="btn-primary-custom">Send Reset Link</button>
                        </form>

                        <!-- Footer -->
                        <div class="auth-footer">
                            <a href="{{ route('auth.login') }}" class="custom-link"> <i class='bx bx-arrow-back'></i>
                                Back to Login</a>
                        </div>
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
