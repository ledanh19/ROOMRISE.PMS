<?php

use App\Http\Controllers\RoomUnitController;
use Illuminate\Support\Facades\Route;

$prefix = 'rooms';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::resource($prefix, RoomUnitController::class);
});
