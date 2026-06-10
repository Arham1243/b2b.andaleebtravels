@php
    $fieldName = $name ?? ('passengers[' . ($pIndex ?? 0) . '][dob]');
    $fieldId = $id ?? ('dob-' . ($pIndex ?? 0));
    $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldName);
    $fieldValue = old($fieldKey, $value ?? '');
    $hasServerError = $errors->has($fieldKey);
    $isRequired = $required ?? false;
@endphp
<div class="col-md-4">
    <label class="hp-label" for="{{ $fieldId }}-display">Date of Birth @if($isRequired)<span class="hp-req">*</span>@endif</label>
    <div class="hp-date-field" id="{{ $fieldId }}-wrap">
        <input
            type="text"
            id="{{ $fieldId }}-display"
            class="hp-input hp-date-field__display js-hp-date-display"
            value=""
            placeholder="Select date"
            readonly
            autocomplete="off"
            data-date-target="#{{ $fieldId }}-value"
        >
        <input
            type="hidden"
            id="{{ $fieldId }}-value"
            name="{{ $fieldName }}"
            class="js-hp-date-value{{ $hasServerError ? ' is-invalid' : '' }}"
            value="{{ $fieldValue }}"
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
