@php
    $discountsEnabled = (bool) old('vendor_discounts_enabled', $vendor->vendor_discounts_enabled ?? false);
    $flightDiscountValue = old('flight_discount_value', ($vendor->flight_discount_value ?? 0) > 0 ? $vendor->flight_discount_value : '');
    $hotelDiscountValue = old('hotel_discount_value', ($vendor->hotel_discount_value ?? 0) > 0 ? $vendor->hotel_discount_value : '');
@endphp

<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header d-flex align-items-center justify-content-between gap-3">
            <div class="title mb-0">Vendor Discounts</div>
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="vendor_discounts_enabled" value="0">
                <input class="form-check-input" type="checkbox" name="vendor_discounts_enabled" value="1"
                    id="vendor_discounts_enabled" role="switch"
                    @checked($discountsEnabled)>
                <label class="form-check-label" for="vendor_discounts_enabled">
                    {{ $discountsEnabled ? 'Enabled' : 'Disabled' }}
                </label>
            </div>
        </div>
        <div class="form-box__body" id="vendor_discounts_body" @if(! $discountsEnabled) hidden @endif>
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="mb-2">Flight discount</h6>
                    <div class="form-fields mb-2">
                        <label class="title">Type</label>
                        <select name="flight_discount_type" class="field vendor-discount-field">
                            <option value="">No discount</option>
                            <option value="percent" @selected(old('flight_discount_type', $vendor->flight_discount_type) === 'percent')>Percentage (%)</option>
                            <option value="fixed" @selected(old('flight_discount_type', $vendor->flight_discount_type) === 'fixed')>Fixed amount</option>
                        </select>
                        @error('flight_discount_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-fields">
                        <label class="title">Value</label>
                        <input type="number" name="flight_discount_value" class="field vendor-discount-field"
                            step="0.01" min="0" max="99999999.99"
                            value="{{ $flightDiscountValue }}"
                            placeholder="e.g. 5 for 5% or 50 AED">
                        @error('flight_discount_value')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="mb-2">Hotel discount</h6>
                    <div class="form-fields mb-2">
                        <label class="title">Type</label>
                        <select name="hotel_discount_type" class="field vendor-discount-field">
                            <option value="">No discount</option>
                            <option value="percent" @selected(old('hotel_discount_type', $vendor->hotel_discount_type) === 'percent')>Percentage (%)</option>
                            <option value="fixed" @selected(old('hotel_discount_type', $vendor->hotel_discount_type) === 'fixed')>Fixed amount</option>
                        </select>
                        @error('hotel_discount_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-fields">
                        <label class="title">Value</label>
                        <input type="number" name="hotel_discount_value" class="field vendor-discount-field"
                            step="0.01" min="0" max="99999999.99"
                            value="{{ $hotelDiscountValue }}"
                            placeholder="e.g. 5 for 5% or 50 AED">
                        @error('hotel_discount_value')
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
    var toggle = document.getElementById('vendor_discounts_enabled');
    var body = document.getElementById('vendor_discounts_body');
    var label = toggle ? toggle.closest('.form-switch').querySelector('.form-check-label') : null;
    var fields = document.querySelectorAll('.vendor-discount-field');

    if (!toggle || !body) return;

    function syncDiscountPanel() {
        var on = toggle.checked;
        body.hidden = !on;
        if (label) {
            label.textContent = on ? 'Enabled' : 'Disabled';
        }
        fields.forEach(function (field) {
            field.disabled = !on;
        });
    }

    toggle.addEventListener('change', syncDiscountPanel);
    syncDiscountPanel();
});
</script>
@endpush
