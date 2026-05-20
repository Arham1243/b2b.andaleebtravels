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
                <i class="bx bxs-user-circle"></i>
            </div>
            <div>
                <h1 class="ps-page-head__title">Account Settings</h1>
                <p class="ps-page-head__sub">Manage your profile</p>
            </div>
        </div>

        <div class="ps-shell">

            {{-- ── SIDEBAR ── --}}
            @include('user.profile-settings._sidebar')

            {{-- ── MAIN CONTENT ── --}}
            <main class="ps-main">
                <form action="{{ route('user.profile.updatePersonalInfo') }}" method="POST"
                      enctype="multipart/form-data" id="validation-form">
                    @csrf
                    @method('POST')

                    {{-- Personal Information --}}
                    <div class="ps-card">
                        <div class="ps-card__head">
                            <h2 class="ps-card__title">
                                <i class="bx bxs-id-card"></i> Personal Information
                            </h2>
                            <button type="submit" class="ps-btn-save">
                                <i class="bx bx-save"></i> Save Changes
                            </button>
                        </div>
                        <div class="ps-card__body">
                            <div class="ps-form-grid">

                                <div class="ps-field" style="grid-column: 1 / -1;">
                                    <label class="ps-field__label">
                                        Travel Agency <span class="req">*</span>
                                    </label>
                                    <input type="text" name="travel_agency" class="ps-field__input"
                                           value="{{ old('travel_agency', $user->travel_agency ?: $user->name) }}" required>
                                    @error('travel_agency')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        First Name <span class="req">*</span>
                                    </label>
                                    <input type="text" name="first_name" class="ps-field__input"
                                           value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        Last Name <span class="req">*</span>
                                    </label>
                                    <input type="text" name="last_name" class="ps-field__input"
                                           value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">Email Address</label>
                                    <input type="email" class="ps-field__input" readonly
                                           value="{{ old('email', $user->email) }}">
                                    <span class="ps-field__hint">Email address cannot be changed.</span>
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        Designation <span class="req">*</span>
                                    </label>
                                    <input type="text" name="designation" class="ps-field__input"
                                           value="{{ old('designation', $user->designation) }}" required>
                                    @error('designation')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        Username <span class="req">*</span>
                                    </label>
                                    <input type="text" name="username" class="ps-field__input"
                                           value="{{ old('username', $user->username) }}" required>
                                    @error('username')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">Agent Code</label>
                                    <input type="text" class="ps-field__input" readonly
                                           value="{{ $user->agent_code }}">
                                    <span class="ps-field__hint">Used for login. Contact admin if you need it changed.</span>
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        Trade License Number <span class="req">*</span>
                                    </label>
                                    <input type="text" name="trade_license_number" class="ps-field__input"
                                           value="{{ old('trade_license_number', $user->trade_license_number) }}" required>
                                    @error('trade_license_number')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">
                                        Trade License Expiry <span class="req">*</span>
                                    </label>
                                    <input type="date" name="trade_license_expiry" class="ps-field__input"
                                           value="{{ old('trade_license_expiry', $user->trade_license_expiry?->format('Y-m-d')) }}" required>
                                    @error('trade_license_expiry')
                                        <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>

                            {{-- Agency Logo --}}
                            <div style="margin-top:20px;">
                                <div class="ps-field__label" style="margin-bottom:10px;">Agency Logo</div>
                                <div class="ps-avatar-pick">
                                    <img src="{{ $user->agency_logo ? asset($user->agency_logo) : asset('admin/assets/images/placeholder.png') }}"
                                         alt="Agency Logo" class="ps-avatar-pick__preview"
                                         id="agency-logo-preview">
                                    <div>
                                        <label for="agency-logo-img" class="ps-avatar-pick__btn">
                                            <i class="bx bx-upload"></i> Upload Agency Logo
                                        </label>
                                        <input type="file" name="agency_logo" id="agency-logo-img" class="d-none"
                                               accept="image/*"
                                               onchange="showImage(this, 'agency-logo-preview', 'agency-logo-filename')">
                                        <div class="ps-avatar-pick__name" id="agency-logo-filename">
                                            JPG, PNG or GIF - max 2 MB
                                        </div>
                                        @error('agency_logo')
                                            <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Avatar --}}
                            <div style="margin-top:20px;">
                                <div class="ps-field__label" style="margin-bottom:10px;">Profile Picture</div>
                                <div class="ps-avatar-pick">
                                    <img src="{{ $user->avatar ? asset($user->avatar) : asset('admin/assets/images/placeholder.png') }}"
                                         alt="Profile" class="ps-avatar-pick__preview"
                                         id="profile-preview">
                                    <div>
                                        <label for="profile-img" class="ps-avatar-pick__btn">
                                            <i class="bx bx-upload"></i> Upload Photo
                                        </label>
                                        <input type="file" name="avatar" id="profile-img" class="d-none"
                                               accept="image/*"
                                               onchange="showImage(this, 'profile-preview', 'avatar-filename')">
                                        <div class="ps-avatar-pick__name" id="avatar-filename">
                                            JPG, PNG or GIF - max 2 MB
                                        </div>
                                        @error('avatar')
                                            <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hotel Search Providers --}}
                    <div class="ps-card">
                        <div class="ps-card__head">
                            <h2 class="ps-card__title">
                                <i class="bx bxs-hotel"></i> Hotel Search Providers
                            </h2>
                        </div>
                        <div class="ps-card__body">
                            @php
                                $selectedProviders = $user->hotel_search_providers ?? null;
                                if (!is_array($selectedProviders) || empty($selectedProviders)) {
                                    $selectedProviders = $adminProviders ?? [];
                                }
                                $providerOptions = [
                                    'yalago'     => 'Yalago',
                                    'tbo'        => 'TBO',
                                    'tripindeal' => 'TripInDeal',
                                ];
                            @endphp
                            <div class="ps-field" style="max-width:420px;">
                                <label class="ps-field__label">Enable Providers</label>
                                <select name="hotel_search_providers[]" class="ps-field__input select2-select" multiple>
                                    @foreach ($providerOptions as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ in_array($key, $selectedProviders, true) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="ps-field__hint">If none selected, all providers are enabled.</span>
                            </div>
                        </div>
                    </div>

                    {{-- Flight Search Providers --}}
                    <div class="ps-card">
                        <div class="ps-card__head">
                            <h2 class="ps-card__title">
                                <i class="bx bxs-plane-alt"></i> Flight Search Providers
                            </h2>
                        </div>
                        <div class="ps-card__body">
                            @php
                                $selectedFlightProviders = $user->flight_search_providers ?? null;
                                if (!is_array($selectedFlightProviders) || empty($selectedFlightProviders)) {
                                    $selectedFlightProviders = $adminFlightProviders ?? [];
                                }
                                $flightProviderOptions = ['sabre' => 'Sabre'];
                            @endphp
                            <div class="ps-field" style="max-width:420px;">
                                <label class="ps-field__label">Enable Providers</label>
                                <select name="flight_search_providers[]" class="ps-field__input select2-select" multiple>
                                    @foreach ($flightProviderOptions as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ in_array($key, $selectedFlightProviders, true) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="ps-field__hint">If none selected, all providers are enabled.</span>
                            </div>
                        </div>
                    </div>

                </form>
            </main>

        </div>
    </div>
</div>
@endsection
