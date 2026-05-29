@php
    $isEdit = isset($vendor);
    $isSubAgent = $isEdit && $vendor->parent_vendor_id;
@endphp

@if ($isSubAgent)
    <div class="form-fields">
        <label class="title">Full Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="field"
            value="{{ old('name', $vendor->name) }}" required>
        @error('name')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
@else
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
        <label class="title">Agency Logo @if($isEdit)<span class="text-muted">(leave empty to keep current)</span>@endif</label>
        @include('partials.agency-logo-upload', [
            'currentUrl' => $isEdit ? $vendor->agencyLogoUrl() : null,
            'chooseLabel' => 'Choose logo',
            'btnClass' => 'themeBtn agency-logo-upload__btn',
            'hint' => null,
        ])
        @error('agency_logo')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-fields mb-0">
                <label class="title">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="field"
                    value="{{ old('first_name', $isEdit ? $vendor->first_name : '') }}" required>
                @error('first_name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-fields mb-0">
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
        <label class="title">Designation <span class="text-danger">*</span></label>
        <input type="text" name="designation" class="field"
            value="{{ old('designation', $isEdit ? $vendor->designation : '') }}" required>
        @error('designation')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-fields mb-0">
                <label class="title">Trade License Number <span class="text-danger">*</span></label>
                <input type="text" name="trade_license_number" class="field"
                    value="{{ old('trade_license_number', $isEdit ? $vendor->trade_license_number : '') }}" required>
                @error('trade_license_number')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-fields mb-0">
                <label class="title">Trade License Expiry <span class="text-danger">*</span></label>
                <input type="date" name="trade_license_expiry" class="field"
                    value="{{ old('trade_license_expiry', $isEdit && $vendor->trade_license_expiry ? $vendor->trade_license_expiry->format('Y-m-d') : '') }}"
                    @unless($isEdit) min="{{ date('Y-m-d') }}" @endunless
                    required>
                @unless($isEdit)
                    <small class="text-muted d-block mt-1">Must be today or a future date.</small>
                @endunless
                @error('trade_license_expiry')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
@endif

<div class="form-fields">
    <label class="title">Email Address <span class="text-danger">*</span></label>
    <input type="email" name="email" class="field"
        value="{{ old('email', $isEdit ? $vendor->email : '') }}" required>
    @error('email')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-fields">
    <label class="title">Username <span class="text-danger">*</span></label>
    <input type="text" name="username" class="field"
        value="{{ old('username', $isEdit ? $vendor->username : '') }}" required>
    <small class="text-muted d-block mt-1">Must be unique across all accounts.</small>
    @error('username')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

@if ($isEdit)
    @php
        $displayAgentCode = $isSubAgent
            ? ($vendor->loginAgentCode() ?? $vendor->agent_code)
            : $vendor->agent_code;
    @endphp
    <div class="form-fields">
        <label class="title">Agent Code</label>
        <input type="text" class="field" value="{{ $displayAgentCode }}" disabled readonly>
        <small class="text-muted d-block mt-1">
            @if ($isSubAgent)
                Same as the parent agency. Cannot be changed.
            @else
                Assigned at registration. Cannot be changed.
            @endif
        </small>
    </div>
@endif

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

