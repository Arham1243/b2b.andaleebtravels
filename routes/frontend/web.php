<?php

use App\Http\Controllers\Frontend\IndexController;
use Illuminate\Support\Facades\Route;

Route::name('frontend.')->group(function () {
    Route::get('/', [IndexController::class, 'index'])->name('index');
    Route::post('/contact-us', [IndexController::class, 'submitContact'])->name('contact.submit');
});
