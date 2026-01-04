<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

$prefix = 'properties';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {

    Route::get($prefix . '/{property}/booking-sources', [PropertyController::class, 'bookingSourcesByProperty'])
        ->name('properties.booking-sources');
    Route::resource($prefix, PropertyController::class);
});
