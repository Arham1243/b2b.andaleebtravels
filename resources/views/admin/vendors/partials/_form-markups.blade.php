@php
    $markupsEnabled = (bool) old('vendor_markups_enabled', $vendor->vendor_markups_enabled ?? false);
    $flightMarkupValue = old('flight_markup_value', ($vendor->flight_markup_value ?? 0) > 0 ? $vendor->flight_markup_value : '');
    $hotelMarkupValue = old('hotel_markup_value', ($vendor->hotel_markup_value ?? 0) > 0 ? $vendor->hotel_markup_value : '');
@endphp

<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header d-flex align-items-center justify-content-between gap-3">
            <div class="title mb-0">Vendor Markup</div>
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="vendor_markups_enabled" value="0">
                <input class="form-check-input" type="checkbox" name="vendor_markups_enabled" value="1"
                    id="vendor_markups_enabled" role="switch"
                    @checked($markupsEnabled)>
                <label class="form-check-label" for="vendor_markups_enabled">
                    {{ $markupsEnabled ? 'Enabled' : 'Disabled' }}
                </label>
            </div>
        </div>
        <div class="form-box__body" id="vendor_markups_body" @if(! $markupsEnabled) hidden @endif>
            <p class="text-muted mb-3" style="font-size:13px;">
                Set agency default markup for flights and hotels. Applied after any vendor discount.
                Sub-agents inherit this as their starting markup; each agent can override in profile settings.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="mb-2">Flight markup</h6>
                    <div class="form-fields mb-2">
                        <label class="title">Type</label>
                        <select name="flight_markup_type" class="field vendor-markup-field">
                            <option value="">No markup</option>
                            <option value="percent" @selected(old('flight_markup_type', $vendor->flight_markup_type) === 'percent')>Percentage (%)</option>
                            <option value="fixed" @selected(old('flight_markup_type', $vendor->flight_markup_type) === 'fixed')>Fixed amount</option>
                        </select>
                        @error('flight_markup_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-fields">
                        <label class="title">Value</label>
                        <input type="number" name="flight_markup_value" class="field vendor-markup-field"
                            step="0.01" min="0" max="99999999.99"
                            value="{{ $flightMarkupValue }}"
                            placeholder="e.g. 5 for 5% or 100 AED">
                        @error('flight_markup_value')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="mb-2">Hotel markup</h6>
                    <div class="form-fields mb-2">
                        <label class="title">Type</label>
                        <select name="hotel_markup_type" class="field vendor-markup-field">
                            <option value="">No markup</option>
                            <option value="percent" @selected(old('hotel_markup_type', $vendor->hotel_markup_type) === 'percent')>Percentage (%)</option>
                            <option value="fixed" @selected(old('hotel_markup_type', $vendor->hotel_markup_type) === 'fixed')>Fixed amount</option>
                        </select>
                        @error('hotel_markup_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-fields">
                        <label class="title">Value</label>
                        <input type="number" name="hotel_markup_value" class="field vendor-markup-field"
                            step="0.01" min="0" max="99999999.99"
                            value="{{ $hotelMarkupValue }}"
                            placeholder="e.g. 5 for 5% or 100 AED">
                        @error('hotel_markup_value')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('vendor_markups_enabled');
    var body = document.getElementById('vendor_markups_body');
    var label = toggle ? toggle.closest('.form-switch').querySelector('.form-check-label') : null;
    var fields = document.querySelectorAll('.vendor-markup-field');

    if (!toggle || !body) return;

    function syncMarkupPanel() {
        var on = toggle.checked;
        body.hidden = !on;
        if (label) {
            label.textContent = on ? 'Enabled' : 'Disabled';
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
