<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PancakeSyncController;

Route::middleware([
    'auth'
])->group(function () {
    // Chỉ Admin mới có thể truy cập
    Route::middleware(['auth'])->group(function () {
        Route::get('/pancake-sync-bookings', [PancakeSyncController::class, 'syncAll'])->name('pancake-sync.sync-all');
    });
});
