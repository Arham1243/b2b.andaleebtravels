@php
    use App\Support\FlightPassengerDobValidator;
    use Carbon\Carbon;

    $fieldName = $name ?? ('passengers[' . ($pIndex ?? 0) . '][dob]');
    $fieldId = $id ?? ('dob-' . ($pIndex ?? 0));
    $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldName);
    $fieldValue = old($fieldKey, $value ?? '');
    $hasServerError = $errors->has($fieldKey);
    $isRequired = (bool) ($required ?? true);
    $paxType = strtoupper((string) ($paxType ?? 'ADT'));
    $wrapperClass = isset($wrapperClass) ? $wrapperClass : 'col-md-4';
    $hideLabel = (bool) ($hideLabel ?? false);
    $labelClass = $labelClass ?? 'hp-label';
    $inputClass = $inputClass ?? 'hp-input hp-date-field__display hp-date-picker-input js-hp-date-display';
    $showRequired = isset($showRequired) ? $showRequired : $isRequired;

    $dobBounds = ['min' => null, 'max' => null];
    $dobMinYear = null;
    $dobMaxYear = null;
    if (! empty($travelDate)) {
        try {
            $dobBounds = FlightPassengerDobValidator::dobPickerBounds(
                $paxType,
                Carbon::parse($travelDate)->startOfDay(),
            );
            if (! empty($dobBounds['min'])) {
                $dobMinYear = Carbon::parse($dobBounds['min'])->year;
            }
            if (! empty($dobBounds['max'])) {
                $dobMaxYear = Carbon::parse($dobBounds['max'])->year;
            }
        } catch (\Throwable) {
            // Leave bounds unset if travel date is invalid.
        }
    }
@endphp
@if($wrapperClass)
<div class="{{ $wrapperClass }}">
@endif
    @unless($hideLabel)
        <label class="{{ $labelClass }}" for="{{ $fieldId }}-display">
            Date of Birth @if($showRequired)<span class="hp-req">*</span>@endif
        </label>
    @endunless
    <div class="hp-date-field" id="{{ $fieldId }}-wrap">
        <input
            type="text"
            id="{{ $fieldId }}-display"
            class="{{ $inputClass }}"
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
            @if(!empty($dobBounds['min'])) data-min-dob="{{ $dobBounds['min'] }}" @endif
            @if(!empty($dobBounds['max'])) data-max-dob="{{ $dobBounds['max'] }}" @endif
            @if($dobMinYear !== null) data-min-year="{{ $dobMinYear }}" @endif
            @if($dobMaxYear !== null) data-max-year="{{ $dobMaxYear }}" @endif
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
@if($wrapperClass)
</div>
@endif
