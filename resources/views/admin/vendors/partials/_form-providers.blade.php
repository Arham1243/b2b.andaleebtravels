@php
    $selectedProviders = $vendor->hotel_search_providers ?? null;
    if (!is_array($selectedProviders) || empty($selectedProviders)) {
        $selectedProviders = $adminProviders ?? [];
    }
    $providerOptions = [
        'yalago' => 'Yalago',
        'tbo' => 'TBO',
        'tripindeal' => 'TripInDeal',
    ];

    $selectedFlightProviders = $vendor->flight_search_providers ?? null;
    if (!is_array($selectedFlightProviders) || empty($selectedFlightProviders)) {
        $selectedFlightProviders = $adminFlightProviders ?? [];
    }
    $flightProviderOptions = ['sabre' => 'Sabre'];
@endphp

<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header">
            <div class="title">Hotel Search Providers</div>
        </div>
        <div class="form-box__body">
            <div class="form-fields">
                <label class="title">Enable Providers</label>
                <select name="hotel_search_providers[]" class="field select2-select" multiple>
                    @foreach ($providerOptions as $key => $label)
                        <option value="{{ $key }}" {{ in_array($key, $selectedProviders, true) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">If none selected, all providers are enabled.</small>
            </div>
        </div>
    </div>
</div>

<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header">
            <div class="title">Flight Search Providers</div>
        </div>
        <div class="form-box__body">
            <div class="form-fields">
                <label class="title">Enable Providers</label>
                <select name="flight_search_providers[]" class="field select2-select" multiple>
                    @foreach ($flightProviderOptions as $key => $label)
                        <option value="{{ $key }}" {{ in_array($key, $selectedFlightProviders, true) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">If none selected, all providers are enabled.</small>
            </div>
        </div>
    </div>
</div>
