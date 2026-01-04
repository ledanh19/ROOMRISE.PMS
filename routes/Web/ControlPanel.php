<?php

use App\Http\Controllers\ControlPanelController;
use Illuminate\Support\Facades\Route;

$prefix = 'control-panel';

Route::middleware(['auth'])->group(function () use ($prefix) {
    Route::prefix($prefix)->as('cp.')->group(function () {
        Route::get('/', [ControlPanelController::class, 'index'])->name('index');
        Route::get('/system',   [ControlPanelController::class, 'system'])->name('system');
        Route::get('/features', [ControlPanelController::class, 'features'])->name('features');
        Route::get('/links',    [ControlPanelController::class, 'links'])->name('links');
    });
});
