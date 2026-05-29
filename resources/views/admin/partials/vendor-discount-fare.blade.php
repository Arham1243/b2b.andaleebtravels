@php
    $hasDiscount = (float) ($booking->vendor_discount_amount ?? 0) > 0.001;
    $snapshot = is_array($booking->vendor_discount_snapshot ?? null) ? $booking->vendor_discount_snapshot : [];
    $originalAmount = (float) ($booking->original_amount ?? 0);
    $discountType = $snapshot['discount_type'] ?? null;
    $discountValue = $snapshot['discount_value'] ?? null;
@endphp

@if ($hasDiscount && $originalAmount > 0)
    <div class="bkpd-fare__row">
        <span>List price</span>
        <span style="text-decoration:line-through;color:#8492a6;">{!! formatPrice($originalAmount) !!}</span>
    </div>
    <div class="bkpd-fare__row">
        <span>
            Vendor discount
            @if ($discountType === 'percent' && $discountValue !== null)
                ({{ rtrim(rtrim(number_format((float) $discountValue, 2), '0'), '.') }}%)
            @elseif ($discountType === 'fixed' && $discountValue !== null)
                ({{ rtrim(rtrim(number_format((float) $discountValue, 2), '0'), '.') }} fixed)
            @endif
        </span>
        <span style="color:#10b981;">− {!! formatPrice($booking->vendor_discount_amount) !!}</span>
    </div>
@endif
