<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OTAReceiptController;

$prefix = 'ota-receipt';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::resource($prefix, OTAReceiptController::class)->parameters([
        'ota-receipt' => 'otareceipt',
    ]);
    Route::get('/customers/by-property/{property}', [OTAReceiptController::class, 'getByProperty'])->name('customers.by-property');
    Route::put('/update-payout', [OTAReceiptController::class, 'updatePayout'])->name('ota-receipt.updatePayout');
    Route::put('/update-payout-note', [OTAReceiptController::class, 'updatePayoutNote'])->name('ota-receipt.updatePayoutNote');
    Route::post('/store-settlements', [OTAReceiptController::class, 'storeSettlements'])->name('ota-receipt.storeSettlements');
    Route::get('/load-settlements', [OTAReceiptController::class, 'loadSettlements'])->name('ota-receipt.loadSettlements');
    Route::get('/load-settlement-bookings', [OTAReceiptController::class, 'loadSettlementBookings'])->name('ota-receipt.loadSettlementBookings');
    Route::post('/confirm-settlement-bookings', [OTAReceiptController::class, 'confirmSettlements'])->name('ota-receipt.confirmSettlements');
});
