@extends('user.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('user.profile.personalInfo') }}
            <form action="{{ route('user.profile.updatePersonalInfo') }}" method="POST" enctype="multipart/form-data"
                id="validation-form">
                @csrf
                @method('POST')
                <div class="custom-sec custom-sec--form">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Edit: Personal Information</h3>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-9">
                        <div class="form-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Information</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="form-fields">
                                                <label class="title">Name<span class="text-danger">*</span>
                                                    :</label>
                                                <input type="text" name="name" class="field"
                                                    value="{{ old('name', $user->name) }}" required="">
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <div class="form-fields">
                                                <label class="title">Username<span class="text-danger">*</span>
                                                    :</label>
                                                <input type="text" name="username" class="field"
                                                    value="{{ old('username', $user->username) }}" required="">
                                                @error('username')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <div class="form-fields">
                                                <label class="title">Email<span class="text-danger">*</span> :</label>
                                                <input type="email" readonly class="field"
                                                    value="{{ old('email', $user->email) }}" required="">
                                                @error('email')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <div class="form-fields">
                                                <label class="title">Agent Code<span class="text-danger">*</span> :</label>
                                                <input type="text" name="agent_code" class="field"
                                                    value="{{ old('agent_code', $user->agent_code) }}" required="">
                                                @error('agent_code')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="placeholder-user">
                                                <label for="profile-img" class="placeholder-user__img">
                                                    <img src="{{ $user->avatar ? asset($user->avatar) : asset('admin/assets/images/placeholder.png') }}"
                                                        alt="image" style=" object-fit: contain;" class="imgFluid" id="profile-preview" loading="lazy">
                                                </label>
                                                <input type="file" name="avatar" id="profile-img"
                                                    onchange="showImage(this, 'profile-preview', 'filename-preview');"
                                                    class="d-none" accept="image/*">
                                                <div class="placeholder-user__name" id="filename-preview">Profile Image</div>
                                                @error('avatar')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <button class="themeBtn mx-auto mt-4">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const otherRoleField = document.getElementById('other-role-field');
            const otherRoleInput = document.getElementById('other-role');


            if (roleSelect.value === 'Other') {
                otherRoleField.style.display = 'block';
            }


            roleSelect.addEventListener('change', function() {
                if (roleSelect.value === 'Other') {

                    roleSelect.removeAttribute('name');
                    otherRoleField.style.display = 'block';
                    otherRoleInput.setAttribute('name',
                        'role');
                } else {

                    roleSelect.setAttribute('name', 'role');
                    otherRoleField.style.display = 'none';
                    otherRoleField.classList.remove('d-block');
                    otherRoleInput.removeAttribute('name');
                }
            });
        });
    </script>
@endpush
