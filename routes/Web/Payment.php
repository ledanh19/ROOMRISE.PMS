<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

$prefix = 'payment';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::post('store-payment/{id}', [PaymentController::class, 'storePayment'])->name('payment.storePayment');
    Route::get('get-data-histories/{id}', [PaymentController::class, 'getHistoriesData'])->name('payment.historiesData');
    Route::get('payment-detail/{booking}', [PaymentController::class, 'getPaymentDetailById'])->name('payment.detailById');
    Route::get('/payment-export', [PaymentController::class, 'exportBookings'])->name('payment.exports');
    Route::get('/payment-export-selected', [PaymentController::class, 'exportBookingsSelected'])->name('payment.exports.selected');

    Route::resource($prefix, PaymentController::class)->parameters([
        'payment' => 'payment',
    ]);
});
