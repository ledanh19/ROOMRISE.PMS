<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

$prefix = 'bookings';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::prefix($prefix)->group(function () {
        Route::get('/list', [BookingController::class, 'index'])->name('bookings.list');
        Route::get('/schedule', [BookingController::class, 'schedule'])->name('bookings.schedule');
        Route::get('/{booking}/get-detail', [BookingController::class, 'getDetail'])->name('bookings.get-detail');
        Route::post('/bookings/{booking}/assign-room-unit', [BookingController::class, 'assignRoomUnit'])->name('bookings.assign-room-unit');
        Route::get('/bookings-export', [BookingController::class, 'exportBookings'])->name('bookings.exports');

        Route::resource('', BookingController::class)->names('bookings');
        Route::get('/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/rooms', [BookingController::class, 'roomStore'])->name('bookings.rooms.store');
        Route::put('/bookings/{booking}/rooms/{room}', [BookingController::class, 'roomUpdate'])->name('bookings.rooms.update');
    });

    Route::get('/properties/options', [PropertyController::class, 'list'])->name('properties.options');
    Route::get('/properties/{property}/rooms', [PropertyController::class, 'roomsByProperty'])->name('rooms.options');
    Route::get('/rooms/{room}/units', [RoomController::class, 'unitsByRoom'])->name('rooms.units.options');
    Route::get('/logistics', [BookingController::class, 'getLogisticsData'])->name('bookings.getLogisticsData');
    Route::get('/check-in', [BookingController::class, 'getCheckInData'])->name('bookings.getCheckInData');
    Route::get('/check-out', [BookingController::class, 'getCheckOutData'])->name('bookings.getCheckOutData');
    Route::get('/recent-bookings', [BookingController::class, 'getRecentBookings'])->name('bookings.getRecentBookings');
    Route::get('/vehicle-data', [BookingController::class, 'getVehicleData'])->name('bookings.getVehicleData');
    Route::get('/room-status', [BookingController::class, 'getRoomStatusData'])->name('bookings.getRoomStatusData');
    Route::get('/get-room-type', [BookingController::class, 'getRoomType'])->name('bookings.getRoomType');
    Route::post('/rooms/check-availability', [BookingController::class, 'checkAvailability'])
        ->name('rooms.check-availability');
    Route::post('/update-booking-information/{booking}', [BookingController::class, 'updateBookingInformation'])
        ->name('bookings.updateBookingInformation');
    Route::post('/add-new-customer/{booking}', [BookingController::class, 'addNewCustomer'])
        ->name('bookings.addNewCustomer');
    Route::post('/customer-check-in/{room}', [BookingController::class, 'customerCheckIn'])
        ->name('bookings.customerCheckIn');
    Route::post('/undo-check-in/{room}', [BookingController::class, 'undoCheckIn'])
        ->name('bookings.undoCheckIn');
    Route::post('/undo-check-out/{booking}/{room}', [BookingController::class, 'undoCheckOut'])
        ->name('bookings.undoCheckOut');


    Route::get('/bookings/{booking}/invoice', [BookingController::class, 'exportInvoicePDF'])->name('bookings.exportInvoicePdf');
    Route::get('/bookings/{booking}/confirm-invoice', [BookingController::class, 'exportConfirmInvoicePdf'])->name('bookings.exportConfirmInvoicePdf');
    Route::post('/booking/cancel-booking/{booking}', [BookingController::class, 'cancelBooking'])
        ->name('bookings.cancelBooking');

    Route::get('/sync-future-bookings', [BookingController::class, 'syncBookings'])->name('bookings.syncFutureBookings');
});
