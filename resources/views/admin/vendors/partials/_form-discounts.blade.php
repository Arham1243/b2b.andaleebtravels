<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header">
            <div class="title">Vendor Discounts</div>
        </div>
        <div class="form-box__body">
            <p class="text-muted mb-3" style="font-size:13px;">
                Set agency-level discounts for flights and hotels. Sub-agents inherit these rules.
                Vendors see the discounted price as the normal fare; only admins see the breakdown on bookings.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="mb-2">Flight discount</h6>
                    <div class="form-fields mb-2">
                        <label class="title">Type</label>
                        <select name="flight_discount_type" class="field">
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
                        <input type="number" name="flight_discount_value" class="field"
                            step="0.01" min="0" max="99999999.99"
                            value="{{ old('flight_discount_value', $vendor->flight_discount_value ?? 0) }}"
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
                        <select name="hotel_discount_type" class="field">
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
                        <input type="number" name="hotel_discount_value" class="field"
                            step="0.01" min="0" max="99999999.99"
                            value="{{ old('hotel_discount_value', $vendor->hotel_discount_value ?? 0) }}"
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
