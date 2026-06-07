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
                                    <label class="ps-field__label">Travel Agency</label>
                                    <input type="text" class="ps-field__input" disabled readonly
                                           value="{{ $user->travel_agency ?: $user->name }}">
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
                                    <input type="email" class="ps-field__input" disabled readonly
                                           value="{{ $user->email }}">
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
                                    <label class="ps-field__label">Username</label>
                                    <input type="text" class="ps-field__input" disabled readonly
                                           value="{{ $user->username }}">
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">Agent Code</label>
                                    <input type="text" class="ps-field__input" disabled readonly
                                           value="{{ $user->loginAgentCode() }}">
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">Trade License Number</label>
                                    <input type="text" class="ps-field__input" disabled readonly
                                           value="{{ $user->trade_license_number ?: '—' }}">
                                </div>

                                <div class="ps-field">
                                    <label class="ps-field__label">Trade License Expiry</label>
                                    <input type="text" class="ps-field__input" disabled readonly
                                           value="{{ $user->trade_license_expiry?->format('d M Y') ?: '—' }}">
                                </div>

                            </div>

                            {{-- Agency Logo (shown in site header) --}}
                            <div style="margin-top:20px;">
                                <div class="ps-field__label" style="margin-bottom:10px;">Agency Logo</div>
                                @include('partials.agency-logo-upload', [
                                    'inputId' => 'agency-logo-img',
                                    'currentUrl' => $user->agencyLogoUrl() ?? asset('admin/assets/images/placeholder.png'),
                                    'chooseLabel' => 'Upload Agency Logo',
                                    'btnClass' => 'ps-avatar-pick__btn agency-logo-upload__btn',
                                    'filenameText' => null,
                                    'hint' => null,
                                ])
                                @error('agency_logo')
                                    <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                @enderror
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
                                $flightProviderOptions = [
                                    'sabre' => 'Sabre',
                                    'travelport' => 'Travelport',
                                ];
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
