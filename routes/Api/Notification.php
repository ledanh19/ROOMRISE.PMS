<?php

use App\Http\Controllers\Api\v1\NotificationController;
use Illuminate\Support\Facades\Route;

$prefix = env('PREFIX_API') . "/notifications";

Route::group(['middleware' => ['sanctum'], 'prefix' => $prefix], function () use ($prefix) {
    Route::delete('{id}', [\App\Http\Controllers\Api\v1\UserController::class, 'destroy']);

    Route::post(null, [NotificationController::class, 'all']);
    Route::post('/get-by-id', [NotificationController::class, 'getById']);
    Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-as-read-all', [NotificationController::class, 'markAsReadAll']);
});
