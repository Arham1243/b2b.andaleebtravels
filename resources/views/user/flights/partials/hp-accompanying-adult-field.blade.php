@php
    $fieldName = 'passengers[' . ($pIndex ?? 0) . '][accompanying_adult]';
    $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldName);
    $selected = old($fieldKey, $defaultAdult ?? 0);
@endphp
<div class="col-md-4">
    <label class="hp-label" for="accompanying-adult-{{ $pIndex ?? 0 }}">Travelling with adult <span class="hp-req">*</span></label>
    <select
        id="accompanying-adult-{{ $pIndex ?? 0 }}"
        name="{{ $fieldName }}"
        class="hp-input{{ $errors->has($fieldKey) ? ' is-invalid' : '' }}"
        required
    >
        @for ($adultIdx = 0; $adultIdx < (int) ($adultCount ?? 1); $adultIdx++)
            <option value="{{ $adultIdx }}" @selected((int) $selected === $adultIdx)>Adult {{ $adultIdx + 1 }}</option>
        @endfor
    </select>
    <span class="hp-hint">Each infant must be linked to an adult traveller.</span>
    @if ($errors->has($fieldKey))
        <span class="hp-field-error">{{ $errors->first($fieldKey) }}</span>
    @endif
</div>
