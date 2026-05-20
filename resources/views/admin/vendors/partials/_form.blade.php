@php
    $isEdit = isset($vendor);
@endphp

<div class="form-fields">
    <label class="title">Travel Agency <span class="text-danger">*</span></label>
    <input type="text" name="travel_agency" class="field"
        value="{{ old('travel_agency', $isEdit ? ($vendor->travel_agency ?: $vendor->name) : '') }}"
        placeholder="Enter travel agency name" required>
    @error('travel_agency')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-fields">
    <label class="title">Agency Logo @if(!$isEdit)@else<span class="text-muted">(leave empty to keep current)</span>@endif</label>
    @if ($isEdit && $vendor->agency_logo)
        <div class="mb-2">
            <img src="{{ asset($vendor->agency_logo) }}" alt="Agency Logo" style="max-height:60px; border-radius:6px;">
        </div>
    @endif
    <input type="file" name="agency_logo" class="field" accept="image/*" {{ $isEdit ? '' : '' }}>
    <small class="text-muted d-block mt-1">JPG, PNG or GIF — max 2 MB</small>
    @error('agency_logo')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-fields">
            <label class="title">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" class="field"
                value="{{ old('first_name', $isEdit ? $vendor->first_name : '') }}" required>
            @error('first_name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-fields">
            <label class="title">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="last_name" class="field"
                value="{{ old('last_name', $isEdit ? $vendor->last_name : '') }}" required>
            @error('last_name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-fields">
    <label class="title">Email Address <span class="text-danger">*</span></label>
    <input type="email" name="email" class="field"
        value="{{ old('email', $isEdit ? $vendor->email : '') }}" required>
    @error('email')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-fields">
    <label class="title">Designation <span class="text-danger">*</span></label>
    <input type="text" name="designation" class="field"
        value="{{ old('designation', $isEdit ? $vendor->designation : '') }}" required>
    @error('designation')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-fields">
    <label class="title">Username <span class="text-danger">*</span></label>
    <input type="text" name="username" class="field"
        value="{{ old('username', $isEdit ? $vendor->username : '') }}" required>
    @error('username')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

@if ($isEdit)
    <div class="form-fields">
        <label class="title">Agent Code</label>
        <input type="text" class="field" value="{{ $vendor->agent_code }}" readonly>
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="form-fields">
            <label class="title">Trade License Number <span class="text-danger">*</span></label>
            <input type="text" name="trade_license_number" class="field"
                value="{{ old('trade_license_number', $isEdit ? $vendor->trade_license_number : '') }}" required>
            @error('trade_license_number')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-fields">
            <label class="title">Trade License Expiry <span class="text-danger">*</span></label>
            <input type="date" name="trade_license_expiry" class="field"
                value="{{ old('trade_license_expiry', $isEdit && $vendor->trade_license_expiry ? $vendor->trade_license_expiry->format('Y-m-d') : '') }}"
                min="{{ date('Y-m-d') }}" required>
            <small class="text-muted d-block mt-1">Must be today or a future date.</small>
            @error('trade_license_expiry')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-fields">
    <label class="title">Password @if($isEdit)<span class="text-muted">(leave empty to keep current)</span>@endif</label>
    <input type="password" name="password" class="field" placeholder="{{ $isEdit ? 'Enter new password' : 'Leave empty for default 12345678' }}" autocomplete="new-password">
    @error('password')
        <div class="text-danger">{{ $message }}</div>
    @enderror
    @unless($isEdit)
        <small class="text-muted d-block mt-1">Default password is 12345678 if left empty.</small>
    @endunless
</div>
