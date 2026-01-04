<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

$prefix = 'customers';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::get('/customers/by-type/{type}', [CustomerController::class, 'getByType'])->name('customers.by-type');
    Route::get('/customers/by-search', [CustomerController::class, 'getBySearch'])->name('customers.by-search');
    Route::post('/customers/{customer}/update-with-file', [CustomerController::class, 'update'])
        ->name('customers.updateWithFile');

    Route::resource($prefix, CustomerController::class);
});
