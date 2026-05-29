@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.portal-users.edit', $adminUser) }}
            <div class="custom-sec custom-sec--form">
                <div class="custom-sec__header">
                    <div class="section-content">
                        <h3 class="heading mb-0">{{ $title }}</h3>
                    </div>
                </div>
                <form action="{{ route('admin.portal-users.update', $adminUser) }}" method="POST"
                    class="form-wrapper">
                    @csrf
                    @method('PUT')
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Account</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-fields mb-3">
                                        <label class="title">Name</label>
                                        <input type="text" name="name" class="field"
                                            value="{{ old('name', $adminUser->name) }}" required>
                                        @error('name')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-fields mb-3">
                                        <label class="title">Email</label>
                                        <input type="email" name="email" class="field"
                                            value="{{ old('email', $adminUser->email) }}" required>
                                        @error('email')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-fields mb-3">
                                        <label class="title">Role</label>
                                        <select name="admin_role_id" class="field" required>
                                            @foreach ($roles as $r)
                                                <option value="{{ $r->id }}"
                                                    @selected(old('admin_role_id', $adminUser->admin_role_id) == $r->id)>
                                                    {{ $r->name }}@if ($r->is_super)
                                                         · full access
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('admin_role_id')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-fields mb-0">
                                        <label class="title">Portal status</label>
                                        @php
                                            $statusVal = old('status', $adminUser->status ?? \App\Models\B2bAdmin::STATUS_ACTIVE);
                                        @endphp
                                        <select name="status" class="field" required>
                                            <option value="{{ \App\Models\B2bAdmin::STATUS_ACTIVE }}" @selected($statusVal === \App\Models\B2bAdmin::STATUS_ACTIVE)>
                                                Active
                                            </option>
                                            <option value="{{ \App\Models\B2bAdmin::STATUS_INACTIVE }}" @selected($statusVal === \App\Models\B2bAdmin::STATUS_INACTIVE)>
                                                Inactive
                                            </option>
                                        </select>
                                        <p class="small text-muted mt-1 mb-0">Inactive users cannot log in to the portal.
                                        </p>
                                        @error('status')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Password</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-fields mb-3">
                                        <label class="title">New password</label>
                                        <input type="password" name="password" class="field"
                                            autocomplete="new-password"
                                            placeholder="Leave blank to keep current password">
                                        @error('password')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-fields mb-0">
                                        <label class="title">Confirm new password</label>
                                        <input type="password" name="password_confirmation" class="field"
                                            autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center mt-2">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="themeBtn">Save</button>
                                <a href="{{ route('admin.portal-users.index') }}" class="themeBtn ms-0">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
