@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.portal-users.create') }}
            <div class="custom-sec custom-sec--form">
                <div class="custom-sec__header">
                    <div class="section-content">
                        <h3 class="heading mb-0">{{ $title }}</h3>
                        <p class="small text-muted mt-2 mb-0">Create an account and email a password-setup link. Access is
                            controlled by the <a href="{{ route('admin.portal-roles.index') }}">portal role</a> you choose.
                        </p>
                    </div>
                </div>
                <form action="{{ route('admin.portal-users.store') }}" method="POST" class="form-wrapper">
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Account</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-fields mb-3">
                                        <label class="title">Name</label>
                                        <input type="text" name="name" class="field" value="{{ old('name') }}" required>
                                        @error('name')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-fields mb-3">
                                        <label class="title">Email</label>
                                        <input type="email" name="email" class="field" value="{{ old('email') }}"
                                            required>
                                        @error('email')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-fields mb-0">
                                        <label class="title">Role</label>
                                        <select name="admin_role_id" class="field" required>
                                            <option value="" disabled {{ old('admin_role_id') ? '' : 'selected' }}>Select a
                                                role</option>
                                            @foreach ($roles as $r)
                                                <option value="{{ $r->id }}" @selected(old('admin_role_id') == $r->id)>
                                                    {{ $r->name }}@if ($r->is_super)
                                                         -  full access
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('admin_role_id')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center mt-2">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="themeBtn">Create &amp; email login link</button>
                                <a href="{{ route('admin.portal-users.index') }}" class="themeBtn ms-0">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
