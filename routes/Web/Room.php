<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

$prefix = 'room-types';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::resource($prefix, RoomController::class);
});
