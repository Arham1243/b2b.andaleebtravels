@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
@endsection

@section('content')
<div class="ps">
    <div class="container">

        {{-- Page header --}}
        <div class="ps-page-head">
            <div class="ps-page-head__icon">
                <i class="bx bxs-lock-alt"></i>
            </div>
            <div>
                <h1 class="ps-page-head__title">Account Settings</h1>
                <p class="ps-page-head__sub">Manage your profile, password, and preferences</p>
            </div>
        </div>

        <div class="ps-shell">

            {{-- ── SIDEBAR ── --}}
            @include('user.profile-settings._sidebar')

            {{-- ── MAIN CONTENT ── --}}
            <main class="ps-main">
                <form action="{{ route('user.profile.updatePassword', $user->id) }}" method="POST"
                      id="validation-form">
                    @csrf

                    <div class="ps-card">
                        <div class="ps-card__head">
                            <h2 class="ps-card__title">
                                <i class="bx bxs-lock-alt"></i> Change Password
                            </h2>
                            <button type="submit" class="ps-btn-save">
                                <i class="bx bx-shield-quarter"></i> Update Password
                            </button>
                        </div>
                        <div class="ps-card__body">

                            <div style="max-width:480px;display:flex;flex-direction:column;gap:18px;">

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        Current Password <span class="req">*</span>
                                    </label>
                                    <div class="ps-pwd-wrap">
                                        <input type="password" id="password" name="password"
                                               class="ps-field__input" required
                                               placeholder="Enter current password">
                                        <button type="button" class="ps-pwd-wrap__toggle"
                                                onclick="psPwdToggle('password', this)">
                                            <i class="bx bxs-show"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        New Password <span class="req">*</span>
                                    </label>
                                    <div class="ps-pwd-wrap">
                                        <input type="password" id="password_confirmation"
                                               name="password_confirmation"
                                               class="ps-field__input" required
                                               placeholder="Enter new password (min 8 chars)">
                                        <button type="button" class="ps-pwd-wrap__toggle"
                                                onclick="psPwdToggle('password_confirmation', this)">
                                            <i class="bx bxs-show"></i>
                                        </button>
                                    </div>
                                    @error('password_confirmation')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div style="background:#f5f7fa;border:1px solid #e4e9f0;border-radius:8px;padding:12px 14px;">
                                    <p style="font-size:.75rem;color:#4a5568;margin:0;display:flex;align-items:flex-start;gap:7px;">
                                        <i class="bx bx-info-circle" style="font-size:1rem;color:#8492a6;flex-shrink:0;margin-top:1px;"></i>
                                        Password must be at least <strong>&nbsp;8 characters</strong>. Use a mix of letters, numbers and symbols for a stronger password.
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
            </main>

        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function psPwdToggle(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bx bxs-hide';
    } else {
        field.type = 'password';
        icon.className = 'bx bxs-show';
    }
}
</script>
@endpush
