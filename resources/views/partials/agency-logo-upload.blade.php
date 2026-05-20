@php
    $inputId = $inputId ?? 'agency-logo';
    $inputName = $inputName ?? 'agency_logo';
    $previewId = $previewId ?? 'agency-logo-preview';
    $filenameId = $filenameId ?? 'agency-logo-filename';
    $required = $required ?? false;
    $currentUrl = $currentUrl ?? null;
    $btnClass = $btnClass ?? 'agency-logo-upload__btn';
    $hint = $hint ?? 'JPG, PNG or GIF — max 2 MB';
@endphp

<div class="agency-logo-upload">
    @if ($currentUrl)
        <img src="{{ $currentUrl }}" alt="Agency Logo" class="agency-logo-upload__preview" id="{{ $previewId }}">
    @else
        <img src="" alt="" class="agency-logo-upload__preview agency-logo-upload__preview--empty" id="{{ $previewId }}" style="display:none;">
    @endif
    <div class="agency-logo-upload__actions">
        <input type="file"
            name="{{ $inputName }}"
            id="{{ $inputId }}"
            class="agency-logo-upload__input"
            accept="image/*"
            @if ($required) required @endif
            onchange="showImage(this, '{{ $previewId }}', '{{ $filenameId }}')">
        <label for="{{ $inputId }}" class="{{ $btnClass }}">
            <i class="bx bx-upload"></i> {{ $chooseLabel ?? 'Choose logo' }}
        </label>
        <span class="agency-logo-upload__name" id="{{ $filenameId }}">{{ $filenameText ?? 'No file chosen' }}</span>
    </div>
    @if ($hint)
        <small class="agency-logo-upload__hint">{{ $hint }}</small>
    @endif
</div>
