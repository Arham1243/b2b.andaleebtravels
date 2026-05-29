<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header">
            <div class="title">Credit Limit</div>
        </div>
        <div class="form-box__body">
            <div class="form-fields">
                <label class="title">Credit Limit</label>
                <input type="number" name="credit_limit" id="credit_limit_input" class="field"
                    step="0.01" min="0" max="99999999.99"
                    value="{{ old('credit_limit', ($vendor->credit_limit ?? 0) > 0 ? $vendor->credit_limit : '') }}"
                    placeholder="Not set"
                    @if(old('remove_credit_limit')) disabled @endif>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_credit_limit" value="1"
                        id="remove_credit_limit"
                        @checked(old('remove_credit_limit', ! $vendor->hasCreditLimit()))>
                    <label class="form-check-label" for="remove_credit_limit">
                        No credit limit
                    </label>
                </div>
                @if ($vendor->hasCreditLimit())
                    <small class="text-muted d-block mt-1">
                        @include('partials.wallet-balance-metrics', ['vendor' => $vendor, 'compact' => true])
                    </small>
                @else
                    <small class="text-muted d-block mt-1">Enter an amount to set a recharge cap, or leave unchecked with no amount for unlimited recharge.</small>
                @endif
                @error('credit_limit')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var removeLimit = document.getElementById('remove_credit_limit');
    var limitInput = document.getElementById('credit_limit_input');
    if (!removeLimit || !limitInput) return;

    function toggleCreditLimitInput() {
        var off = removeLimit.checked;
        limitInput.disabled = off;
        if (off) {
            limitInput.value = '';
        }
    }

    removeLimit.addEventListener('change', toggleCreditLimitInput);
    toggleCreditLimitInput();
});
</script>
@endpush
