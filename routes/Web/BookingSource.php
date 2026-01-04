<?php

use App\Http\Controllers\BookingSourceController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth'
])->group(function () {
    Route::resource('booking-sources', BookingSourceController::class);
});
