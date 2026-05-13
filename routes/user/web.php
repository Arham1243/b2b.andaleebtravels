<?php

use App\Http\Controllers\Frontend\Auth\AuthController;
use App\Http\Controllers\Frontend\Auth\PasswordResetController;
use App\Http\Controllers\User\UserDashController;
use App\Http\Controllers\User\ProfileSettingsController;
use App\Http\Controllers\User\HotelController;
use App\Http\Controllers\User\FlightController;
use App\Http\Controllers\User\FlightBookingController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\WalletRechargeController;
use App\Http\Controllers\User\ProvinceSyncController;
use App\Http\Controllers\User\SubAgentController;
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

    Route::prefix('sub-agents')->name('sub-agents.')->group(function () {
        Route::get('/', [SubAgentController::class, 'index'])->name('index');
        Route::get('/create', [SubAgentController::class, 'create'])->name('create');
        Route::post('/', [SubAgentController::class, 'store'])->name('store');
    });


    Route::prefix('hotels')->name('hotels.')->group(function () {
        Route::get('/', [HotelController::class, 'index'])->name('index');
        Route::get('/search', [HotelController::class, 'search'])->name('search');
        Route::get('/search-hotels', [HotelController::class, 'searchHotels'])->name('search-hotels');
        Route::get('/sync-provinces', [ProvinceSyncController::class, 'syncFromTbo'])->name('sync-provinces');
        Route::get('/update-province-tbo-codes', [ProvinceSyncController::class, 'updateTboCodes'])->name('update-province-tbo-codes');
        Route::get('/details/{id}', [HotelController::class, 'details'])->name('details');
        Route::get('/details/tbo/{code}', [HotelController::class, 'detailsTbo'])->name('details.tbo');
        Route::get('/details/tripindeal/{code}', [HotelController::class, 'detailsTripInDeal'])->name('details.tripindeal');
        Route::get('/checkout/{id}', [HotelController::class, 'checkout'])->name('checkout');
        Route::get('/checkout/tbo/{code}', [HotelController::class, 'checkoutTbo'])->name('checkout.tbo');
        Route::post('/payment/process', [HotelController::class, 'processPayment'])->name('payment.process');
        Route::get('/payment/success/{booking}', [HotelController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/payment/success/view/{booking}', [HotelController::class, 'paymentSuccessView'])->name('payment.success.view');
        Route::get('/payment/failed/{booking?}', [HotelController::class, 'paymentFailed'])->name('payment.failed');
    });

    Route::prefix('flights')->name('flights.')->group(function () {
        Route::get('/', [FlightController::class, 'index'])->name('index');
        Route::get('/search', [FlightController::class, 'search'])->name('search');
        Route::get('/checkout/{itinerary}', [FlightBookingController::class, 'checkout'])->name('checkout');
        Route::post('/payment/process', [FlightBookingController::class, 'processPayment'])->name('payment.process');
        Route::get('/payment/success/{booking}', [FlightBookingController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/payment/success/view/{booking}', [FlightBookingController::class, 'paymentSuccessView'])->name('payment.success.view');
        Route::get('/payment/failed/{booking?}', [FlightBookingController::class, 'paymentFailed'])->name('payment.failed');
        // Hold (PNR without payment)
        Route::get('/hold/{itinerary}', [FlightBookingController::class, 'holdCheckout'])->name('hold');
        Route::post('/hold/process', [FlightBookingController::class, 'processHold'])->name('hold.process');
        Route::get('/hold/success/{booking}', [FlightBookingController::class, 'holdSuccess'])->name('hold.success');
        // Convert hold → confirmed booking (pay & issue ticket)
        Route::get('/hold/confirm/{booking}', [FlightBookingController::class, 'holdConfirmPage'])->name('hold.confirm');
        Route::post('/hold/confirm/{booking}/pay', [FlightBookingController::class, 'holdConfirmPay'])->name('hold.confirm.pay');
        // Saved passengers (AJAX)
        Route::get('/passengers/saved', [FlightBookingController::class, 'getSavedPassengers'])->name('passengers.saved');
        Route::post('/passengers/save', [FlightBookingController::class, 'savePassenger'])->name('passengers.save');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        // Index redirects to flights list
        Route::get('/', fn() => redirect()->route('user.bookings.flights'))->name('index');

        // Flight bookings
        Route::get('/flights', [BookingController::class, 'flights'])->name('flights');
        Route::post('/flights/release-hold/{id}', [BookingController::class, 'releaseHold'])->name('flights.release-hold');
        Route::get('/flights/cancel/{id}', [BookingController::class, 'cancelFlightBooking'])->name('flights.cancel');
        Route::get('/flights/{id}', [BookingController::class, 'flightDetail'])->name('flights.detail');

        // Hotel bookings
        Route::get('/hotels', [BookingController::class, 'hotels'])->name('hotels');
        Route::post('/hotels/cancellation-charges', [BookingController::class, 'getCancellationCharges'])->name('hotels.cancellation-charges');
        Route::post('/hotels/cancel-tbo', [BookingController::class, 'cancelTboBooking'])->name('hotels.cancel-tbo');
        Route::get('/hotels/cancel/{id}', [BookingController::class, 'cancelHotelBooking'])->name('hotels.cancel');
        Route::get('/hotels/{id}', [BookingController::class, 'hotelDetail'])->name('hotels.detail');
    });

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/recharge', [WalletRechargeController::class, 'index'])->name('recharge');
        Route::post('/recharge/process', [WalletRechargeController::class, 'process'])->name('recharge.process');
        Route::get('/recharge/retry/{recharge}', [WalletRechargeController::class, 'retryPayment'])->name('recharge.retry');
        Route::get('/payment/success/{transactionNumber}', [WalletRechargeController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/payment/failed/{transactionNumber?}', [WalletRechargeController::class, 'paymentFailed'])->name('payment.failed');
    });
});
