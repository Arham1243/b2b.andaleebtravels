@php
    use App\Support\Travelport\TravelportHoldPayloadBuilder;

    $namePrefix = $namePrefix ?? 'lead';
    $oldDotPrefix = $oldDotPrefix ?? 'lead';
    $inputClass = $inputClass ?? 'hp-input hp-input--phone';
    $wrapperClass = $wrapperClass ?? 'hp-phone-field';

    $phoneForm = TravelportHoldPayloadBuilder::parseLeadPhoneForForm(
        old($oldDotPrefix . '.phone', data_get($defaultPhone, 'phone')),
        old($oldDotPrefix . '.phone_dial_code', data_get($defaultPhone, 'phone_dial_code')),
        old($oldDotPrefix . '.phone_local', data_get($defaultPhone, 'phone_local')),
    );
    $phoneDial = $phoneForm['dial_code'] ?: '971';
    $phoneLocal = $phoneForm['local'] ?? '';
    $phoneIso = $phoneForm['iso'] ?? 'AE';
    $phoneHasError = $errors->has($oldDotPrefix . '.phone')
        || $errors->has($oldDotPrefix . '.phone_local')
        || $errors->has($oldDotPrefix . '.phone_dial_code');
@endphp

<div class="{{ $wrapperClass }}{{ $phoneHasError ? ' is-invalid' : '' }}" data-hp-travelport-phone>
    <input type="hidden" name="{{ $namePrefix }}[phone_dial_code]" value="{{ $phoneDial }}" data-hp-phone-dial-code>
    <input type="hidden" name="{{ $namePrefix }}[phone_local]" value="{{ $phoneLocal }}" data-hp-phone-local>
    <input type="hidden" name="{{ $namePrefix }}[phone]" value="{{ old($oldDotPrefix . '.phone', data_get($defaultPhone, 'phone')) }}" data-hp-phone-display>
    <input type="tel"
        class="{{ $inputClass }}{{ $phoneHasError ? ' is-invalid' : '' }}"
        data-hp-phone-input
        value="{{ $phoneLocal }}"
        required
        autocomplete="tel-national"
        data-initial-iso="{{ strtolower($phoneIso) }}">
    @if ($phoneHasError)
        <span class="hp-field-error">
            {{ $errors->first($oldDotPrefix . '.phone_local')
                ?: $errors->first($oldDotPrefix . '.phone_dial_code')
                ?: $errors->first($oldDotPrefix . '.phone') }}
        </span>
    @endif
</div>
