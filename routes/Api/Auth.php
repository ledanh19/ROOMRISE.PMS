<?php

use Illuminate\Support\Facades\Route;

$prefix = env('PREFIX_API', 'v1') . "/auth";

Route::group(['prefix' => $prefix], function () use ($prefix) {
    Route::post('/Login', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'login']);
    Route::post('/ResendCode', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'resendCode']);
    Route::post('/Verify', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'verify']);
    Route::post('/RefreshToken', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'refresh']);
    Route::get('/Logout', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'logout'])->middleware(\App\Http\Middleware\Sanctum::class);
    Route::post('/Register', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'register']);
    Route::post('/VerifyRegister', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'verifyRegister']);
    Route::post('/ForgotPassword', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'forgotPassword']);
    Route::post('/ResetPassword', [\App\Http\Controllers\Api\v1\AuthenticationController::class, 'resetPassword']);
});
