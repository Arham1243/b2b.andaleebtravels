@extends('frontend.layouts.main')
@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">

                    <div class="auth-card">
                        <div class="auth-header">
                            <h2 class="heading">Reset Password</h2>
                            <p>Please enter your new password below</p>
                        </div>

                        <form action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $_GET['email'] }}">
                            <!-- New Password Field -->
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" class="custom-input password-field" required>
                                    <i class='bx bx-show password-toggle'></i>
                                </div>
                                @error('password')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password_confirmation" class="custom-input password-field"
                                        required>
                                    <i class='bx bx-show password-toggle'></i>
                                </div>
                                @error('password_confirmation')
                                    <span class="text-danger validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="btn-primary-custom">Reset Password</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
@push('js')
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
                        this.classList.remove('bx-show');
                        this.classList.add('bx-hide');
                    } else {
                        // Switch back to password
                        input.type = 'password';
                        // Change icon back to show eye
                        this.classList.remove('bx-hide');
                        this.classList.add('bx-show');
                    }
                });
            });
        });
    </script>
@endpush
