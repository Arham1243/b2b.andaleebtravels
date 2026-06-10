@php
    $fieldName = $name ?? '';
    $fieldLabel = $label ?? 'Country';
    $isRequired = !empty($required);
    $errorKey = rtrim(str_replace(['[', ']'], ['.', ''], $fieldName), '.');
    $fieldValue = strtoupper((string) old($errorKey, $value ?? ''));
@endphp
<div class="col-md-4">
    <label class="hp-label">{{ $fieldLabel }} @if ($isRequired)<span class="hp-req">*</span>@endif</label>
    <div class="hp-ac-wrap hp-country-ac" data-field-name="{{ $fieldName }}">
        <input type="text"
            class="hp-input hp-country-ac-display{{ $errors->has($errorKey) ? ' is-invalid' : '' }}"
            placeholder="Type country name or code"
            autocomplete="off"
            autocapitalize="off"
            autocorrect="off"
            spellcheck="false"
            data-lpignore="true"
            data-1p-ignore
            aria-label="{{ $fieldLabel }}"
            readonly
            onfocus="this.removeAttribute('readonly');">
        <input type="hidden"
            class="hp-country-ac-value"
            name="{{ $fieldName }}"
            value="{{ $fieldValue }}"
            autocomplete="off"
            @if ($isRequired) required @endif>
        <div class="hp-ac-dropdown hp-country-ac-dropdown" hidden></div>
    </div>
    @if ($errors->has($errorKey))
        <span class="hp-field-error">{{ $errors->first($errorKey) }}</span>
    @endif
</div>
