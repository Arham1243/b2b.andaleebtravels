@extends('frontend.layouts.main')
@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8 col-xl-7">

                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="heading">Create Account</h2>
                            <p>Register your travel agency with Andaleeb Travel Agency</p>
                        </div>

                        <form action="{{ route('auth.signup.perform', request()->query()) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group">
                                <label class="form-label">Travel Agency <span class="text-danger">*</span></label>
                                <input type="text" value="{{ old('travel_agency') }}" name="travel_agency"
                                    class="custom-input" placeholder="Enter travel agency name" required>
                                @error('travel_agency')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">Agency Logo <span class="text-danger">*</span></label>
                                <input type="file" name="agency_logo" id="agency-logo" class="custom-input" accept="image/*" required>
                                <small class="text-muted d-block mt-1">JPG, PNG or GIF — max 2 MB</small>
                                <img id="agency-logo-preview" src="" alt="" class="mt-2 rounded" style="max-height:80px; display:none;">
                                @error('agency_logo')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" value="{{ old('first_name') }}" name="first_name"
                                            class="custom-input" required>
                                        @error('first_name')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" value="{{ old('last_name') }}" name="last_name"
                                            class="custom-input" required>
                                        @error('last_name')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" value="{{ old('email') }}" name="email" class="custom-input" required>
                                @error('email')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                        <input type="text" value="{{ old('designation') }}" name="designation"
                                            class="custom-input" placeholder="e.g. Owner, Manager" required>
                                        @error('designation')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" value="{{ old('username') }}" name="username"
                                            class="custom-input" required>
                                        <small class="text-muted d-block mt-1">Must be unique. You will use this to log in.</small>
                                        @error('username')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Trade License Number <span class="text-danger">*</span></label>
                                        <input type="text" value="{{ old('trade_license_number') }}" name="trade_license_number"
                                            class="custom-input" required>
                                        @error('trade_license_number')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Trade License Expiry <span class="text-danger">*</span></label>
                                        <input type="date" value="{{ old('trade_license_expiry') }}" name="trade_license_expiry"
                                            class="custom-input" min="{{ date('Y-m-d') }}" required>
                                        <small class="text-muted d-block mt-1">Must be today or a future date.</small>
                                        @error('trade_license_expiry')
                                            <span class="text-danger validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
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

                            <button type="submit" class="btn-primary-custom">Sign Up</button>
                        </form>

                        <div class="auth-footer">
                            Already have an account?
                            <a href="{{ route('auth.login', request()->query()) }}">Login</a>
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
            const toggleIcons = document.querySelectorAll('.password-toggle');
            toggleIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const input = this.previousElementSibling;

                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('bxs-show');
                        this.classList.add('bxs-hide');
                    } else {
                        input.type = 'password';
                        this.classList.remove('bxs-hide');
                        this.classList.add('bxs-show');
                    }
                });
            });

            const logoInput = document.getElementById('agency-logo');
            const logoPreview = document.getElementById('agency-logo-preview');
            if (logoInput && logoPreview) {
                logoInput.addEventListener('change', function() {
                    const file = this.files && this.files[0];
                    if (!file) {
                        logoPreview.style.display = 'none';
                        logoPreview.src = '';
                        return;
                    }
                    logoPreview.src = URL.createObjectURL(file);
                    logoPreview.style.display = 'block';
                });
            }

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
