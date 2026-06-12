@php
    use App\Support\Travelport\TravelportHoldPayloadBuilder;

    $phoneForm = TravelportHoldPayloadBuilder::parseLeadPhoneForForm(
        old('lead.phone'),
        old('lead.phone_dial_code'),
        old('lead.phone_local'),
    );
    $phoneDial = $phoneForm['dial_code'] ?: '971';
    $phoneLocal = $phoneForm['local'] ?? '';
    $phoneIso = $phoneForm['iso'] ?? 'AE';
    $phoneHasError = $errors->has('lead.phone')
        || $errors->has('lead.phone_local')
        || $errors->has('lead.phone_dial_code');
@endphp

<div class="hp-phone-field{{ $phoneHasError ? ' is-invalid' : '' }}" data-hp-travelport-phone>
    <input type="hidden" name="lead[phone_dial_code]" value="{{ $phoneDial }}" data-hp-phone-dial-code>
    <input type="hidden" name="lead[phone_local]" value="{{ $phoneLocal }}" data-hp-phone-local>
    <input type="hidden" name="lead[phone]" value="{{ old('lead.phone') }}" data-hp-phone-display>
    <input type="tel"
        class="hp-input hp-input--phone{{ $phoneHasError ? ' is-invalid' : '' }}"
        data-hp-phone-input
        value="{{ $phoneLocal }}"
        placeholder="50 123 4567"
        required
        autocomplete="tel-national"
        data-initial-iso="{{ strtolower($phoneIso) }}">
    @if ($phoneHasError)
        <span class="hp-field-error">
            {{ $errors->first('lead.phone_local')
                ?: $errors->first('lead.phone_dial_code')
                ?: $errors->first('lead.phone') }}
        </span>
    @endif
</div>
