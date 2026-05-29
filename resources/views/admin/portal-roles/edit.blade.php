@extends('admin.layouts.main')

@php
    use App\Http\Controllers\Admin\PortalRoleController;
    $portalRolePermSelected = PortalRoleController::permissionsSelectedForDisplay(old('permissions', $role->permissionList()));
@endphp
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.portal-roles.edit', $role) }}
            <div class="custom-sec custom-sec--form">
                <div class="custom-sec__header">
                    <div class="section-content">
                        <h3 class="heading mb-0">{{ $title }}</h3>
                        <p class="small text-muted mt-2 mb-0">Updating <strong>{{ $role->name }}</strong> applies instantly
                            to everyone assigned to this role.</p>
                    </div>
                </div>
                <form action="{{ route('admin.portal-roles.update', $role) }}" method="POST" class="form-wrapper">
                    @csrf
                    @method('PUT')
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="form-box">
                                <div class="form-box__body">
                                    <div class="form-fields mb-0">
                                        <label class="title">Role name</label>
                                        <input type="text" name="name" class="field"
                                            value="{{ old('name', $role->name) }}" required>
                                        @error('name')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            @include('admin.portal-roles._permission-fields', [
                                'permissionUi' => $permissionUi,
                                'selected' => $portalRolePermSelected,
                            ])
                        </div>
                    </div>
                    <div class="row justify-content-center mt-2">
                        <div class="col-xl-10">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="themeBtn">Save changes</button>
                                <a href="{{ route('admin.portal-roles.index') }}" class="themeBtn ms-0">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
