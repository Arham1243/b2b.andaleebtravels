@extends('frontend.layouts.main')
@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-6 col-xl-5">

                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="heading">Create Account</h2>
                            <p>Start your journey with Andaleeb Travel</p>
                        </div>

                        <form action="{{ route('auth.signup.perform', request()->query()) }}" method="POST">
                            @csrf
                            <!-- Name Fields Row -->
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                        <input type="text" value="{{ old('first_name') }}" name="first_name"
                                            class="custom-input" required>
                                        @error('first_name')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" value="{{ old('last_name') }}" name="last_name"
                                            class="custom-input" required>
                                        @error('last_name')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Email Field -->
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" value="{{ old('email') }}" name="email" class="custom-input"
                                    required>
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
                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="btn-primary-custom">Sign Up</button>
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
                            Already have an account? <a href="{{ route('auth.login') }}" class="custom-link">Login</a>
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
