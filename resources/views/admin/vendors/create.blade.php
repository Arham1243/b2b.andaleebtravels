@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.create') }}
            <form action="{{ route('admin.vendors.store') }}" method="POST" enctype="multipart/form-data" id="validation-form">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Vendor Details</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-fields">
                                        <label class="title">Travel Agency <span class="text-danger">*</span></label>
                                        <input type="text" name="travel_agency" class="field"
                                            value="{{ old('travel_agency') }}" placeholder="Enter travel agency name" required>
                                        @error('travel_agency')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Agency Logo</label>
                                        <input type="file" name="agency_logo" class="field" accept="image/*">
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
                                                    value="{{ old('first_name') }}" placeholder="Enter first name" required>
                                                @error('first_name')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-fields">
                                                <label class="title">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" class="field"
                                                    value="{{ old('last_name') }}" placeholder="Enter last name" required>
                                                @error('last_name')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="field" value="{{ old('email') }}"
                                            placeholder="Enter email address" required>
                                        @error('email')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Designation <span class="text-danger">*</span></label>
                                        <input type="text" name="designation" class="field"
                                            value="{{ old('designation') }}" placeholder="e.g. Owner, Manager" required>
                                        @error('designation')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="field" value="{{ old('username') }}"
                                            placeholder="Enter username" required>
                                        @error('username')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-fields">
                                                <label class="title">Trade License Number <span class="text-danger">*</span></label>
                                                <input type="text" name="trade_license_number" class="field"
                                                    value="{{ old('trade_license_number') }}"
                                                    placeholder="Enter trade license number" required>
                                                @error('trade_license_number')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-fields">
                                                <label class="title">Trade License Expiry <span class="text-danger">*</span></label>
                                                <input type="date" name="trade_license_expiry" class="field"
                                                    value="{{ old('trade_license_expiry') }}" required>
                                                @error('trade_license_expiry')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-fields">
                                        <small class="text-muted">Default password is 12345678. An agent code is generated automatically. The vendor will receive a welcome email with login details and a password reset link.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="seo-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Status</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="active"
                                            value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Active</label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="status" id="inactive"
                                            value="inactive" {{ old('status') == 'inactive' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inactive">Inactive</label>
                                    </div>

                                    <div class="text-end mt-4">
                                        <button class="themeBtn" type="submit">Create Vendor</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Info</div>
                                </div>
                                <div class="form-box__body">
                                    <p class="text-muted mb-0" style="font-size: 13px;">
                                        Upon creation, the vendor will receive an invite email containing their agent code,
                                        username, default password, and a password reset link.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
