<?php

use App\Http\Controllers\ChannexController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth'
])->group(function () {
    Route::post('channex/sync-data', [ChannexController::class, 'sync'])->name('channex.sync-data');
});
