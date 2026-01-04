<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

$prefix = 'inventory';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::post($prefix . '/store-multiple', [InventoryController::class, 'storeMultiple'])->name($prefix . '.storeMultiple');
    Route::post($prefix . '/update-bulk-restriction', [InventoryController::class, 'updateBulk'])->name($prefix . '.update-bulk-restriction');
    Route::post($prefix . '/full-sync', [InventoryController::class, 'fullSync'])->name($prefix . '.fullsync');
    Route::get($prefix . '/full-sync-all', [InventoryController::class, 'fullSyncAll'])->name($prefix . '.fullsync-all');
    Route::resource($prefix, InventoryController::class);
});
