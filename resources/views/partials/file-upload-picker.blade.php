@php
    $inputId = $inputId ?? 'file-upload';
    $inputName = $inputName ?? 'attachment';
    $previewId = $previewId ?? 'file-upload-preview';
    $filenameId = $filenameId ?? 'file-upload-filename';
    $required = $required ?? false;
    $currentUrl = $currentUrl ?? null;
    $btnClass = $btnClass ?? 'agency-logo-upload__btn';
    $chooseLabel = $chooseLabel ?? 'Choose file';
    $filenameText = $filenameText ?? 'No file chosen';
    $accept = $accept ?? '.jpg,.jpeg,.png,.gif,.webp,.pdf';
    $hint = $hint ?? null;
@endphp

<div class="agency-logo-upload">
    @if ($currentUrl)
        <img src="{{ $currentUrl }}" alt="" class="agency-logo-upload__preview" id="{{ $previewId }}">
    @else
        <img src="" alt="" class="agency-logo-upload__preview agency-logo-upload__preview--empty" id="{{ $previewId }}" style="display:none;">
    @endif
    <div class="agency-logo-upload__actions">
        <input type="file"
            name="{{ $inputName }}"
            id="{{ $inputId }}"
            class="agency-logo-upload__input"
            accept="{{ $accept }}"
            @if ($required) required @endif
            onchange="showFilePreview(this, '{{ $previewId }}', '{{ $filenameId }}')">
        <label for="{{ $inputId }}" class="{{ $btnClass }}">
            <i class="bx bx-upload"></i> {{ $chooseLabel }}
        </label>
        <span class="agency-logo-upload__name" id="{{ $filenameId }}">{{ $filenameText }}</span>
    </div>
    @if ($hint)
        <small class="agency-logo-upload__hint">{{ $hint }}</small>
    @endif
</div>
