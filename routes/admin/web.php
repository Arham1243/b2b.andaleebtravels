<?php

use App\Http\Controllers\Admin\AdminDashController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\BulkActionController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\DBConsoleController;
use App\Http\Controllers\Admin\EnvEditorController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\TerminalController;
use App\Http\Controllers\Admin\AdminHotelController;
use App\Http\Controllers\Admin\AdminFlightController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminHotelBookingController;
use App\Http\Controllers\Admin\AdminFlightBookingController;
use App\Http\Controllers\Admin\WalletBankTransferController;
use Illuminate\Support\Facades\Route;

Route::redirect('/admin', '/admin/dashboard');

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

    Route::get('/hotels/start', [AdminHotelController::class, 'start'])->name('hotels.start');
    Route::get('/flights/start', [AdminFlightController::class, 'start'])->name('flights.start');

    Route::get('/terminal', [TerminalController::class, 'index']);
    Route::post('/terminal/run', [TerminalController::class, 'run']);

    Route::get('/db-console', [DBConsoleController::class, 'index']);
    Route::post('/db-console', [DBConsoleController::class, 'run'])->name('db.console.run');

    Route::get('/env-editor', [EnvEditorController::class, 'index'])->name('env');
    Route::post('/env-editor', [EnvEditorController::class, 'save'])->name('env.save');

    Route::get('logs', [LogController::class, 'read']);
    Route::get('logs/delete', [LogController::class, 'delete']);

    Route::post('bulk-actions/{resource}', [BulkActionController::class, 'handle'])->name('bulk-actions');

    Route::get('vendors/pending', [VendorController::class, 'pendingIndex'])->name('vendors.pending.index');
    Route::get('vendors/pending/{vendor}', [VendorController::class, 'pendingShow'])->name('vendors.pending.show');
    Route::post('vendors/pending/{vendor}/approve', [VendorController::class, 'approve'])->name('vendors.pending.approve');
    Route::post('vendors/pending/{vendor}/reject', [VendorController::class, 'reject'])->name('vendors.pending.reject');
    Route::get('vendors/{vendor}/sub-agents/create', [VendorController::class, 'createSubAgent'])->name('vendors.sub-agents.create');
    Route::post('vendors/{vendor}/sub-agents', [VendorController::class, 'storeSubAgent'])->name('vendors.sub-agents.store');
    Route::post('vendors/{vendor}/wallet-transactions', [VendorController::class, 'storeWalletTransaction'])->name('vendors.wallet-transactions.store');
    Route::put('vendors/{vendor}/wallet-transactions/{ledger}', [VendorController::class, 'updateWalletTransaction'])->name('vendors.wallet-transactions.update');
    Route::post('vendors/{vendor}/wallet-transactions/{ledger}/void', [VendorController::class, 'voidWalletTransaction'])->name('vendors.wallet-transactions.void');
    Route::put('vendors/{vendor}/credit-limit', [VendorController::class, 'updateCreditLimit'])->name('vendors.credit-limit.update');
    Route::resource('vendors', VendorController::class);
    Route::get('vendors/change-status/{vendor}', [VendorController::class, 'changeStatus'])->name('vendors.change-status');

    Route::resource('hotel-bookings', AdminHotelBookingController::class)->only(['index', 'show']);
    Route::resource('flight-bookings', AdminFlightBookingController::class)->only(['index', 'show']);

    Route::post('bookings/hotels/{booking}/status', [AdminBookingController::class, 'updateHotelStatus'])->name('bookings.hotels.status');
    Route::post('bookings/hotels/{booking}/cancel', [AdminBookingController::class, 'cancelHotelBooking'])->name('bookings.hotels.cancel');
    Route::post('bookings/flights/{booking}/status', [AdminBookingController::class, 'updateFlightStatus'])->name('bookings.flights.status');
    Route::post('bookings/flights/{booking}/release-hold', [AdminBookingController::class, 'releaseFlightHold'])->name('bookings.flights.release-hold');
    Route::post('bookings/flights/{booking}/cancel', [AdminBookingController::class, 'cancelFlightBooking'])->name('bookings.flights.cancel');

    Route::get('wallet/bank-transfers', [WalletBankTransferController::class, 'index'])->name('wallet.bank-transfers.index');
    Route::post('wallet/bank-transfers/{recharge}/confirm', [WalletBankTransferController::class, 'confirm'])->name('wallet.bank-transfers.confirm');
    Route::post('wallet/bank-transfers/{recharge}/reject', [WalletBankTransferController::class, 'reject'])->name('wallet.bank-transfers.reject');

    Route::resource('inquiries', InquiryController::class);
    Route::get('logo-management', [ConfigController::class, 'logoManagement'])->name('settings.logo');
    Route::post('logo-management', [ConfigController::class, 'saveLogo'])->name('settings.logo');
    Route::get('details', [ConfigController::class, 'details'])->name('settings.details');
    Route::post('details', [ConfigController::class, 'saveDetails'])->name('settings.details');
});
