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
                        <div class="login-content__title">Forgot password</div>
                        <p>Enter your admin email. We will send a reset link.</p>
                        <form class="login-content__form" method="POST"
                            action="{{ route('admin.password.email') }}">
                            @csrf
                            <div class="fields">
                                <label for="email" class="title">Email <span class="text-danger">*</span></label>
                                <input class="@if ($errors->has('email')) is-invalid @endif" type="email"
                                    name="email" id="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="fields mt-4">
                                <button class="themeBtn" type="submit">Send reset link</button>
                            </div>
                            <div class="fields mt-3">
                                <a href="{{ route('admin.login') }}">Back to login</a>
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
