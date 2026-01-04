<?php

use App\Http\Controllers\RatePlanController;
use Illuminate\Support\Facades\Route;

$prefix = 'rate-plans';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::get('/options', [RatePlanController::class, 'options'])->name('rateplans.options');
    Route::resource($prefix, RatePlanController::class);
});
