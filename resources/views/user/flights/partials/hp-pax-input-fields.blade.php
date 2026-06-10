@php
    $pIndex = $pIndex ?? 0;
    $titleOptions = $titleOptions ?? ['Mr' => 'Mr.', 'Mrs' => 'Mrs.', 'Ms' => 'Ms.', 'Dr' => 'Dr.'];
    $defaultTitle = $defaultTitle ?? array_key_first($titleOptions);

    $titleKey = 'passengers.' . $pIndex . '.title';
    $firstNameKey = 'passengers.' . $pIndex . '.first_name';
    $lastNameKey = 'passengers.' . $pIndex . '.last_name';

    $selectedTitle = old($titleKey, $defaultTitle);
@endphp
<div class="col-md-2">
    <label class="hp-label">Title <span class="hp-req">*</span></label>
    <select class="hp-select{{ $errors->has($titleKey) ? ' is-invalid' : '' }}" name="passengers[{{ $pIndex }}][title]" required>
        @foreach ($titleOptions as $value => $label)
            <option value="{{ $value }}" @selected($selectedTitle === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @if ($errors->has($titleKey))
        <span class="hp-field-error">{{ $errors->first($titleKey) }}</span>
    @endif
</div>
<div class="col-md-5">
    <label class="hp-label">First Name <span class="hp-req">*</span></label>
    <input type="text"
        class="hp-input{{ $errors->has($firstNameKey) ? ' is-invalid' : '' }}"
        name="passengers[{{ $pIndex }}][first_name]"
        value="{{ old($firstNameKey) }}"
        placeholder="Enter first name"
        required
        autocomplete="given-name">
    @if ($errors->has($firstNameKey))
        <span class="hp-field-error">{{ $errors->first($firstNameKey) }}</span>
    @endif
</div>
<div class="col-md-5">
    <label class="hp-label">Last Name <span class="hp-req">*</span></label>
    <input type="text"
        class="hp-input{{ $errors->has($lastNameKey) ? ' is-invalid' : '' }}"
        name="passengers[{{ $pIndex }}][last_name]"
        value="{{ old($lastNameKey) }}"
        placeholder="Enter last name"
        required
        autocomplete="family-name">
    @if ($errors->has($lastNameKey))
        <span class="hp-field-error">{{ $errors->first($lastNameKey) }}</span>
    @endif
</div>
