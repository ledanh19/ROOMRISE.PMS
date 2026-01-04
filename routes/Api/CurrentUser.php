<?php

use Illuminate\Support\Facades\Route;

$prefix = env('PREFIX_API', 'v1') . "/currentUser";

Route::group(['middleware' => ['sanctum'], 'prefix' => $prefix], function () use ($prefix) {
    Route::get('', [\App\Http\Controllers\Api\v1\CurrentUserController::class, 'currentUser']);
    Route::post('/Update', [\App\Http\Controllers\Api\v1\CurrentUserController::class, 'update']);
    Route::post('/ChangePassword', [\App\Http\Controllers\Api\v1\CurrentUserController::class, 'changePassword']);
});
