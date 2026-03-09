@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.create') }}
            <form action="{{ route('admin.vendors.store') }}" method="POST" id="validation-form">
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
                                        <label class="title">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="field" value="{{ old('name') }}"
                                            placeholder="Enter full name" required>
                                        @error('name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
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
                                        <label class="title">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="field" value="{{ old('username') }}"
                                            placeholder="Enter username" required>
                                        @error('username')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Agent Code <span class="text-danger">*</span></label>
                                        <input type="text" name="agent_code" class="field"
                                            value="{{ old('agent_code') }}" placeholder="Enter agent code" required>
                                        @error('agent_code')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <small class="text-muted">A random password will be generated and sent to the vendor via email.</small>
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
                                        username, password, and a link to the login page.
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
