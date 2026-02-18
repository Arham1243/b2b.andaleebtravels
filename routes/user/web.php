<?php

use App\Http\Controllers\Frontend\Auth\AuthController;
use App\Http\Controllers\Frontend\Auth\PasswordResetController;
use App\Http\Controllers\User\UserDashController;
use App\Http\Controllers\User\ProfileSettingsController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\TravelInsuranceController;
use App\Http\Controllers\User\HotelController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'login'])->name('login');
Route::prefix('auth')->name('auth.')->middleware('user_guest')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::get('signup', [AuthController::class, 'signup'])->name('signup');
    Route::post('login', [AuthController::class, 'performLogin'])->name('login.perform');
    Route::post('signup', [AuthController::class, 'performSignup'])->name('signup.perform');
});

Route::get('password/reset', [PasswordResetController::class, 'index'])->name('password.request');
Route::post('password/email', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('password/notify', [PasswordResetController::class, 'notify'])->name('password.notify');
Route::get('password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.update');

Route::middleware(['auth', 'check_user_status'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('profile/personal-info', [ProfileSettingsController::class, 'personalInfo'])->name('profile.personalInfo');
    Route::post('profile/personal-info/update', [ProfileSettingsController::class, 'updatePersonalInfo'])->name('profile.updatePersonalInfo');

    Route::get('profile/change/password', [ProfileSettingsController::class, 'changePassword'])->name('profile.changePassword');
    Route::post('profile/change/password/update', [ProfileSettingsController::class, 'updatePassword'])->name('profile.updatePassword');
});
