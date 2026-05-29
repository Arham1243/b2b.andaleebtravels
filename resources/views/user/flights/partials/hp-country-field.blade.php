@php
    $fieldName = $name ?? '';
    $fieldLabel = $label ?? 'Country';
    $isRequired = !empty($required);
    $fieldValue = strtoupper((string) ($value ?? ''));
@endphp
<div class="col-md-4">
    <label class="hp-label">{{ $fieldLabel }} @if ($isRequired)<span class="hp-req">*</span>@endif</label>
    <div class="hp-ac-wrap hp-country-ac" data-field-name="{{ $fieldName }}">
        <input type="text"
            class="hp-input hp-country-ac-display"
            placeholder="Type country name or code"
            autocomplete="off"
            aria-label="{{ $fieldLabel }}">
        <input type="hidden"
            class="hp-country-ac-value"
            name="{{ $fieldName }}"
            value="{{ $fieldValue }}"
            @if ($isRequired) required @endif>
        <div class="hp-ac-dropdown hp-country-ac-dropdown" hidden></div>
    </div>
</div>
