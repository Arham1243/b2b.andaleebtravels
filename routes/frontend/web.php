<?php

use App\Http\Controllers\Frontend\IndexController;
use App\Http\Controllers\Frontend\TourController;
use App\Http\Controllers\Frontend\TravelInsuranceController;
use App\Http\Controllers\Frontend\HotelController;
use App\Http\Controllers\Frontend\PackageController;
use Illuminate\Support\Facades\Route;

Route::name('frontend.')->group(function () {
    Route::get('/', [IndexController::class, 'index'])->name('index');

    Route::post('/contact-us', [IndexController::class, 'submitContact'])->name('contact.submit');

    Route::prefix('travel-insurance')->name('travel-insurance.')->group(function () {
        Route::get('/', [TravelInsuranceController::class, 'index'])->name('index');
        Route::get('/details', [TravelInsuranceController::class, 'details'])->name('details');
        Route::post('/payment/process', [TravelInsuranceController::class, 'processPayment'])->name('payment.process');
        Route::get('/payment/success/{insurance}', [TravelInsuranceController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/payment/failed', [TravelInsuranceController::class, 'paymentFailed'])->name('payment.failed');
    });


    Route::prefix('tour')->name('tour.')->group(function () {
        Route::get('/details/{slug}', [TourController::class, 'details'])->name('details');
        Route::post('/save-review/{slug}', [TourController::class, 'saveReview'])->name('save-review');
    });


    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('/search', [PackageController::class, 'search'])->name('search');
        Route::get('/searchNames', [PackageController::class, 'searchNames'])->name('searchNames');
        Route::get('/category/{slug}', [PackageController::class, 'category'])->name('category');
        Route::get('/{slug}', [PackageController::class, 'details'])->name('details');
        Route::post('/inquiry', [PackageController::class, 'submitInquiry'])->name('inquiry.submit');
    });

    Route::prefix('hotels')->name('hotels.')->group(function () {
        Route::get('/', [HotelController::class, 'index'])->name('index');
        Route::get('/search', [HotelController::class, 'search'])->name('search');
        Route::get('/search-hotels', [HotelController::class, 'searchHotels'])->name('search.hotels');
        Route::get('/details/{id}', [HotelController::class, 'details'])->name('details');
        Route::get('/checkout/{id}', [HotelController::class, 'checkout'])->name('checkout');
        Route::post('/payment/process', [HotelController::class, 'processPayment'])->name('payment.process');
        Route::get('/payment/success/{booking}', [HotelController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/payment/success/view/{booking}', [HotelController::class, 'paymentSuccessView'])->name('payment.success.view');
        Route::get('/payment/failed/{booking?}', [HotelController::class, 'paymentFailed'])->name('payment.failed');
    });
});
