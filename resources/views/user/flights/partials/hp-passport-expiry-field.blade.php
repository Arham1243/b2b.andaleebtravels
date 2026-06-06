@php
    $fieldName = $name ?? ('passengers[' . ($pIndex ?? 0) . '][passport_exp]');
    $fieldId = $id ?? ('passport-exp-' . ($pIndex ?? 0));
    $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldName);
    $fieldValue = old($fieldKey, $value ?? '');
    $paxLabel = $paxLabel ?? 'Passenger';
    $hasServerError = $errors->has($fieldKey);
@endphp
<div class="col-md-4">
    <label class="hp-label" for="{{ $fieldId }}">Passport Expiry</label>
    <input
        type="date"
        id="{{ $fieldId }}"
        class="hp-input js-passport-exp{{ $hasServerError ? ' is-invalid' : '' }}"
        name="{{ $fieldName }}"
        value="{{ $fieldValue }}"
        data-pax-label="{{ $paxLabel }}"
        @if (!empty($minDate)) min="{{ $minDate }}" @endif
    >
    <span class="hp-passport-exp-error" role="alert" @unless($hasServerError) hidden @endunless>{{ $hasServerError ? $errors->first($fieldKey) : '' }}</span>
    <span class="hp-passport-exp-info" hidden>
        <i class="bx bx-info-circle"></i>
        This passport expires within 6 months of your travel date. Please ensure it meets destination requirements.
    </span>
</div>
