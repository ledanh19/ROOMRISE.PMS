<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;

$prefix = 'partner';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::resource($prefix, PartnerController::class)->parameters([
        'partner' => 'partner',
    ]);
    Route::get('/get-partner-by-id', [PartnerController::class, 'getPartnerById'])->name('partner.getPartnerById');
    // Route::get('/get-partner', [PartnerController::class, 'getPartner'])->name('partner.getPartner');
    Route::get('/get-partner', [PartnerController::class, 'getPartnerByType'])->name('partner.getPartnerByType');
    Route::get('/get-data-sale', [PartnerController::class, 'loadDataSale'])->name('partner.loadDataSale');
    Route::get('/get-data-sale-ta', [PartnerController::class, 'loadDataSaleTA'])->name('partner.loadDataSaleTA');
    Route::post('/partner/{partner}/sync-stats', [PartnerController::class, 'syncStats']);
});
