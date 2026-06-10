@php
    $fieldName = $name ?? ('passengers[' . ($pIndex ?? 0) . '][passport_exp]');
    $fieldId = $id ?? ('passport-exp-' . ($pIndex ?? 0));
    $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldName);
    $fieldValue = old($fieldKey, $value ?? '');
    $paxLabel = $paxLabel ?? 'Passenger';
    $hasServerError = $errors->has($fieldKey);
@endphp
<div class="col-md-4">
    <label class="hp-label" for="{{ $fieldId }}-display">Passport Expiry</label>
    <div class="hp-date-field" id="{{ $fieldId }}-wrap">
        <input
            type="text"
            id="{{ $fieldId }}-display"
            class="hp-input hp-date-field__display hp-date-picker-input js-hp-date-display"
            value=""
            placeholder="Select date"
            readonly
            autocomplete="off"
            data-date-role="passport-exp"
            @if (!empty($minDate)) data-min-date="{{ $minDate }}" @endif
        >
        <input
            type="hidden"
            id="{{ $fieldId }}-value"
            class="js-passport-exp js-hp-date-value{{ $hasServerError ? ' is-invalid' : '' }}"
            name="{{ $fieldName }}"
            value="{{ $fieldValue }}"
            data-pax-label="{{ $paxLabel }}"
        >
        <i class="bx bx-calendar hp-date-field__icon"></i>
    </div>
    <span class="hp-passport-exp-error" role="alert" @unless($hasServerError) hidden @endunless>{{ $hasServerError ? $errors->first($fieldKey) : '' }}</span>
    <span class="hp-passport-exp-info" hidden>
        <i class="bx bx-info-circle"></i>
        This passport expires within 6 months of your travel date. Please ensure it meets destination requirements.
    </span>
</div>
