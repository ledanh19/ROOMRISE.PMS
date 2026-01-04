<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('messages', [MessageController::class, 'index'])->name('message.index');
});
