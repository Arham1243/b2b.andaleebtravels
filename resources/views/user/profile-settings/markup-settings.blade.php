@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
    <style>
        .ps-markup-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid #e8ecf2;
            border-radius: 10px;
            background: #fafbfd;
            margin-bottom: 18px;
        }
        .ps-markup-toggle__label {
            font-size: .88rem;
            font-weight: 600;
            color: #1a2540;
        }
        .ps-markup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        @media (max-width: 767px) {
            .ps-markup-grid { grid-template-columns: 1fr; }
        }
        .ps-markup-grid .ps-field__input:disabled,
        .ps-markup-grid .ps-field__input[readonly] {
            background: #f8fafc;
            color: #1a2540;
            opacity: 1;
            cursor: default;
        }
    </style>
@endsection

@section('content')
@php
    $overrideEnabled = (bool) old('agent_markup_override_enabled', $user->agent_markup_override_enabled ?? false);

    $agencyFlightType = $agency->flight_markup_type ?? '';
    $agencyFlightValue = ($agency->flight_markup_value ?? 0) > 0 ? $agency->flight_markup_value : '';
    $agencyHotelType = $agency->hotel_markup_type ?? '';
    $agencyHotelValue = ($agency->hotel_markup_value ?? 0) > 0 ? $agency->hotel_markup_value : '';

    $flightMarkupValue = old('agent_flight_markup_value', ($user->agent_flight_markup_value ?? 0) > 0 ? $user->agent_flight_markup_value : '');
    $hotelMarkupValue = old('agent_hotel_markup_value', ($user->agent_hotel_markup_value ?? 0) > 0 ? $user->agent_hotel_markup_value : '');
@endphp

<div class="ps">
    <div class="container">

        <div class="ps-page-head">
            <div class="ps-page-head__icon">
                <i class="bx bx-purchase-tag-alt"></i>
            </div>
            <div>
                <h1 class="ps-page-head__title">Account Settings</h1>
                <p class="ps-page-head__sub">Manage your markup and commission</p>
            </div>
        </div>

        <div class="ps-shell">
            @include('user.profile-settings._sidebar')

            <main class="ps-main">
                <form action="{{ route('user.profile.updateMarkupSettings') }}" method="POST" id="validation-form">
                    @csrf

                    <div class="ps-card">
                        <div class="ps-card__head">
                            <h2 class="ps-card__title">
                                <i class="bx bx-slider-alt"></i> Markup Settings
                            </h2>
                            <button type="submit" class="ps-btn-save" id="markup_save_btn" @if(! $overrideEnabled) hidden @endif>
                                <i class="bx bx-save"></i> Save Markup
                            </button>
                        </div>
                        <div class="ps-card__body">
                            <div id="agency_markup_body" @if($overrideEnabled) hidden @endif>
                                <div class="ps-markup-grid" id="agency_markup_fields">
                                    <div>
                                        <h6 style="font-size:.88rem;font-weight:700;margin-bottom:12px;">Flight markup</h6>
                                        <div class="ps-field" style="margin-bottom:14px;">
                                            <label class="ps-field__label">Type</label>
                                            <select class="ps-field__input" disabled>
                                                <option value="" @selected($agencyFlightType === '' || $agencyFlightType === null)>No markup</option>
                                                <option value="percent" @selected($agencyFlightType === 'percent')>Percentage (%)</option>
                                                <option value="fixed" @selected($agencyFlightType === 'fixed')>Fixed amount</option>
                                            </select>
                                        </div>
                                        <div class="ps-field">
                                            <label class="ps-field__label">Value</label>
                                            <input type="text" class="ps-field__input" readonly
                                                value="{{ $agencyFlightValue !== '' ? $agencyFlightValue : '' }}"
                                                placeholder="{{ $agencyFlightType ? '' : 'Not set' }}">
                                        </div>
                                    </div>

                                    <div>
                                        <h6 style="font-size:.88rem;font-weight:700;margin-bottom:12px;">Hotel markup</h6>
                                        <div class="ps-field" style="margin-bottom:14px;">
                                            <label class="ps-field__label">Type</label>
                                            <select class="ps-field__input" disabled>
                                                <option value="" @selected($agencyHotelType === '' || $agencyHotelType === null)>No markup</option>
                                                <option value="percent" @selected($agencyHotelType === 'percent')>Percentage (%)</option>
                                                <option value="fixed" @selected($agencyHotelType === 'fixed')>Fixed amount</option>
                                            </select>
                                        </div>
                                        <div class="ps-field">
                                            <label class="ps-field__label">Value</label>
                                            <input type="text" class="ps-field__input" readonly
                                                value="{{ $agencyHotelValue !== '' ? $agencyHotelValue : '' }}"
                                                placeholder="{{ $agencyHotelType ? '' : 'Not set' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ps-markup-toggle">
                                <div>
                                    <div class="ps-markup-toggle__label">Use custom markup</div>
                                    <div id="agent_markup_toggle_hint" style="font-size:.78rem;color:#64748b;margin-top:2px;">
                                        {{ $overrideEnabled
                                            ? 'Your custom markup applies independently to your searches and bookings.'
                                            : 'Turn on to replace agency markup with your own flight and hotel settings.' }}
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="agent_markup_override_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" name="agent_markup_override_enabled"
                                        value="1" id="agent_markup_override_enabled" role="switch"
                                        @checked($overrideEnabled)>
                                </div>
                            </div>

                            <div id="agent_markup_body" @if(! $overrideEnabled) hidden @endif>
                                <div class="ps-markup-grid">
                                    <div>
                                        <h6 style="font-size:.88rem;font-weight:700;margin-bottom:12px;">Flight markup</h6>
                                        <div class="ps-field" style="margin-bottom:14px;">
                                            <label class="ps-field__label">Type</label>
                                            <select name="agent_flight_markup_type" class="ps-field__input agent-markup-field">
                                                <option value="">No markup</option>
                                                <option value="percent" @selected(old('agent_flight_markup_type', $user->agent_flight_markup_type) === 'percent')>Percentage (%)</option>
                                                <option value="fixed" @selected(old('agent_flight_markup_type', $user->agent_flight_markup_type) === 'fixed')>Fixed amount</option>
                                            </select>
                                            @error('agent_flight_markup_type')
                                                <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="ps-field">
                                            <label class="ps-field__label">Value</label>
                                            <input type="number" name="agent_flight_markup_value" class="ps-field__input agent-markup-field"
                                                step="0.01" min="0" max="99999999.99"
                                                value="{{ $flightMarkupValue }}"
                                                placeholder="e.g. 5 for 5% or 100 AED">
                                            @error('agent_flight_markup_value')
                                                <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <h6 style="font-size:.88rem;font-weight:700;margin-bottom:12px;">Hotel markup</h6>
                                        <div class="ps-field" style="margin-bottom:14px;">
                                            <label class="ps-field__label">Type</label>
                                            <select name="agent_hotel_markup_type" class="ps-field__input agent-markup-field">
                                                <option value="">No markup</option>
                                                <option value="percent" @selected(old('agent_hotel_markup_type', $user->agent_hotel_markup_type) === 'percent')>Percentage (%)</option>
                                                <option value="fixed" @selected(old('agent_hotel_markup_type', $user->agent_hotel_markup_type) === 'fixed')>Fixed amount</option>
                                            </select>
                                            @error('agent_hotel_markup_type')
                                                <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="ps-field">
                                            <label class="ps-field__label">Value</label>
                                            <input type="number" name="agent_hotel_markup_value" class="ps-field__input agent-markup-field"
                                                step="0.01" min="0" max="99999999.99"
                                                value="{{ $hotelMarkupValue }}"
                                                placeholder="e.g. 5 for 5% or 100 AED">
                                            @error('agent_hotel_markup_value')
                                                <span class="text-danger" style="font-size:.75rem;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
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
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('agent_markup_override_enabled');
    var agencyBody = document.getElementById('agency_markup_body');
    var agentBody = document.getElementById('agent_markup_body');
    var saveBtn = document.getElementById('markup_save_btn');
    var hint = document.getElementById('agent_markup_toggle_hint');
    var fields = document.querySelectorAll('.agent-markup-field');

    if (!toggle) return;

    function syncMarkupPanel() {
        var on = toggle.checked;

        if (agencyBody) {
            agencyBody.hidden = on;
        }
        if (agentBody) {
            agentBody.hidden = !on;
        }
        if (saveBtn) {
            saveBtn.hidden = !on;
        }
        if (hint) {
            hint.textContent = on
                ? 'Your custom markup applies independently to your searches and bookings.'
                : 'Turn on to replace agency markup with your own flight and hotel settings.';
        }
        fields.forEach(function (field) {
            field.disabled = !on;
        });
    }

    toggle.addEventListener('change', syncMarkupPanel);
    syncMarkupPanel();
});
</script>
@endpush
