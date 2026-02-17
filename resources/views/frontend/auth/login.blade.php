@extends('frontend.layouts.main')
@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">

                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="heading">Welcome Back</h2>
                            <p>Login to access your bookings and profile</p>
                        </div>
                        <form action="{{ route('auth.login.perform', request()->query()) }}" method="POST">
                            @csrf
                            <!-- Email Field -->
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="custom-input" required
                                    value="{{ old('email') }}">
                                @error('email')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Password Field with Toggle -->
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" class="custom-input password-field" required>
                                    <i class='bx bxs-show password-toggle'></i>
                                </div>
                                @error('password')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Remember & Forgot -->
                            <div class="auth-actions">
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="remember">
                                    Remember me
                                </label>
                                <a href="{{ route('password.request') }}" class="custom-link">Forgot
                                    Password?</a>
                            </div>

                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="btn-primary-custom">Login</button>
                        </form>

                        <!-- Divider -->
                        <div class="auth-divider">
                            <span>OR</span>
                        </div>

                        <!-- Google Button -->
                        <a href="{{ route('frontend.socialite.redirect', 'google') }}" class="btn-google">
                            <img src="{{ asset('frontend/assets/images/google.svg') }}" alt="Google Logo">
                            Continue with Google
                        </a>

                        <!-- Footer -->
                        <div class="auth-footer">
                            <p class="mb-2">Don't have an account? <a href="{{ route('auth.signup') }}"
                                    class="custom-link">Sign Up</a></p>

                            <a href="{{ route('auth.my-booking') }}" class="custom-link">
                                Find Your Booking
                            </a>
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
            // Select all toggle icons
            const toggleIcons = document.querySelectorAll('.password-toggle');

            toggleIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    // Find the input within the same wrapper
                    const input = this.previousElementSibling;

                    if (input.type === 'password') {
                        // Switch to text
                        input.type = 'text';
                        // Change icon to hide eye
                        this.classList.remove('bxs-show');
                        this.classList.add('bxs-hide');
                    } else {
                        // Switch back to password
                        input.type = 'password';
                        // Change icon back to show eye
                        this.classList.remove('bxs-hide');
                        this.classList.add('bxs-show');
                    }
                });
            });

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
