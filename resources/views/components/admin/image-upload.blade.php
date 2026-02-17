@props([
    'name' => 'image',
    'label' => 'Image',
    'required' => false,
    'existingImage' => null,
    'error' => null,
])

<div class="form-fields">
    <label class="title">{{ $label }} @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <div class="upload" data-upload>
        <div class="upload-box-wrapper">
            <div class="upload-box {{ !$existingImage ? 'show' : '' }}" data-upload-box>
                <input type="file" name="{{ $name }}" data-error="{{ $label }}" id="{{ $name }}"
                    class="upload-box__file d-none" accept="image/*" data-file-input
                    {{ $required ? 'data-required' : '' }} data-error="{{ $label }}">
                <div class="upload-box__placeholder"><i class='bx bxs-image'></i>
                </div>
                <label for="{{ $name }}" class="upload-box__btn themeBtn">Upload
                    Image</label>
            </div>
            <div class="upload-box__img {{ $existingImage ? 'show' : '' }}" data-upload-img>
                <button type="button" class="delete-btn" data-delete-btn><i class='bx bxs-edit-alt'></i></button>
                <a href="{{ $existingImage ? asset($existingImage) : '#' }}" class="mask" data-fancybox="gallery">
                    <img src="{{ $existingImage ? asset($existingImage) : asset('admin/assets/images/loading.webp') }}"
                        alt="{{ $label }}" class="imgFluid" data-upload-preview>
                </a>
            </div>
        </div>
        <div data-error-message class="text-danger mt-2 d-none text-center">Please
            upload a valid image file
        </div>
        @error($name)
            <div class="text-danger mt-2 text-center">{{ $message }}
            </div>
        @enderror
    </div>
</div>
