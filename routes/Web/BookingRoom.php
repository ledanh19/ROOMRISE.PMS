<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingRoomController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

$prefix = 'booking-rooms';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::prefix($prefix)->group(function () {
        Route::post('booking-rooms/{booking_room}/assign-room-unit', [BookingRoomController::class, 'assignRoomUnit'])->name('booking-rooms.assign-room-unit');
    });
});
