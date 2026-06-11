@php
    $fieldName = $name ?? ('passengers[' . ($pIndex ?? 0) . '][dob]');
    $fieldId = $id ?? ('dob-' . ($pIndex ?? 0));
    $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldName);
    $fieldValue = old($fieldKey, $value ?? '');
    $hasServerError = $errors->has($fieldKey);
    $isRequired = (bool) ($required ?? true);
    $paxType = strtoupper((string) ($paxType ?? 'ADT'));
@endphp
<div class="col-md-4">
    <label class="hp-label" for="{{ $fieldId }}-display">Date of Birth <span class="hp-req">*</span></label>
    <div class="hp-date-field" id="{{ $fieldId }}-wrap">
        <input
            type="text"
            id="{{ $fieldId }}-display"
            class="hp-input hp-date-field__display hp-date-picker-input js-hp-date-display"
            value=""
            placeholder="Select date"
            readonly
            autocomplete="off"
            data-date-role="dob"
            data-date-target="#{{ $fieldId }}-value"
        >
        <input
            type="hidden"
            id="{{ $fieldId }}-value"
            name="{{ $fieldName }}"
            class="js-hp-date-value{{ $hasServerError ? ' is-invalid' : '' }}"
            value="{{ $fieldValue }}"
            data-pax-type="{{ $paxType }}"
            @if($isRequired) data-required="1" @endif
        >
        <i class="bx bx-calendar hp-date-field__icon"></i>
    </div>
    @if(!empty($hint))
        <span class="hp-hint">{{ $hint }}</span>
    @endif
    @if($hasServerError)
        <span class="hp-field-error">{{ $errors->first($fieldKey) }}</span>
    @endif
</div>
