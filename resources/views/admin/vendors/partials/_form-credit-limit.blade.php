<div class="form-wrapper mt-3">
    <div class="form-box">
        <div class="form-box__header">
            <div class="title">Credit Limit</div>
        </div>
        <div class="form-box__body">
            <div class="form-fields">
                <label class="title">Credit Limit</label>
                <input type="number" name="credit_limit" class="field"
                    step="0.01" min="0" max="99999999.99"
                    value="{{ old('credit_limit', ($vendor->credit_limit ?? 0) > 0 ? $vendor->credit_limit : '') }}"
                    placeholder="Not set">
                @if ($vendor->hasCreditLimit())
                    <small class="text-muted d-block mt-1">
                        @include('partials.wallet-balance-metrics', ['vendor' => $vendor, 'compact' => true])
                    </small>
                    @if ($vendor->creditUsedAmount() > 0)
                        <small class="text-muted d-block mt-1">Minimum allowed: {!! formatPrice($vendor->creditUsedAmount()) !!}.</small>
                    @endif
                @else
                    <small class="text-muted d-block mt-1">Enter an amount to set the vendor credit limit.</small>
                @endif
                @error('credit_limit')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
