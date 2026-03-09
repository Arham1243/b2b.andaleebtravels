<?php

use App\Http\Controllers\Admin\AdminDashController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\BulkActionController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\DBConsoleController;
use App\Http\Controllers\Admin\EnvEditorController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\TerminalController;
use Illuminate\Support\Facades\Route;

Route::get('/admins', function () {
    return redirect()->route('admin.login');
})->name('admin.admin');

Route::middleware('guest')->prefix('admin')->namespace('Admin')->group(function () {
    Route::get('/auth', [AdminLoginController::class, 'login'])->name('admin.login');
    Route::post('/perform-login', [AdminLoginController::class, 'performLogin'])->name('admin.performLogin')->middleware('throttle:5,1');
});

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::get('/terminal', [TerminalController::class, 'index']);
    Route::post('/terminal/run', [TerminalController::class, 'run']);

    Route::get('/db-console', [DBConsoleController::class, 'index']);
    Route::post('/db-console', [DBConsoleController::class, 'run'])->name('db.console.run');

    Route::get('/env-editor', [EnvEditorController::class, 'index'])->name('env');
    Route::post('/env-editor', [EnvEditorController::class, 'save'])->name('env.save');

    Route::get('logs', [LogController::class, 'read']);
    Route::get('logs/delete', [LogController::class, 'delete']);

    Route::post('bulk-actions/{resource}', [BulkActionController::class, 'handle'])->name('bulk-actions');

    Route::resource('users', UserController::class);
    Route::get('users/change-status/{user}', [UserController::class, 'changeStatus'])->name('users.change-status');

    Route::resource('vendors', VendorController::class);
    Route::get('vendors/change-status/{vendor}', [VendorController::class, 'changeStatus'])->name('vendors.change-status');

    Route::resource('inquiries', InquiryController::class);
    Route::get('logo-management', [ConfigController::class, 'logoManagement'])->name('settings.logo');
    Route::post('logo-management', [ConfigController::class, 'saveLogo'])->name('settings.logo');
    Route::get('details', [ConfigController::class, 'details'])->name('settings.details');
    Route::post('details', [ConfigController::class, 'saveDetails'])->name('settings.details');
});
