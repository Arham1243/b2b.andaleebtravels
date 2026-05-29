@php
    $is_login = true;
@endphp
@extends('admin.layouts.main')
@section('content')
    <div class="login-wrapper">
        <div class="container-fluid p-0">
            <div class="row g-0">
                <div class="col-md-5">
                    <div class="login-content">
                        <div class="login-content__title">Set new password</div>
                        <form class="login-content__form" method="POST"
                            action="{{ route('admin.password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="fields">
                                <label for="email" class="title">Email <span class="text-danger">*</span></label>
                                <input class="@if ($errors->has('email')) is-invalid @endif" type="email"
                                    name="email" id="email" value="{{ old('email', $email) }}" required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="fields">
                                <label for="password" class="title">New password <span
                                        class="text-danger">*</span></label>
                                <input class="@if ($errors->has('password')) is-invalid @endif" type="password"
                                    name="password" id="password" required>
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="fields">
                                <label for="password_confirmation" class="title">Confirm password <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    required>
                            </div>
                            <div class="fields mt-4">
                                <button class="themeBtn" type="submit">Update password</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="login-image">
                        <img src="{{ asset('admin/assets/images/login.jpg') }}" alt="Login" class="imgFluid">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
