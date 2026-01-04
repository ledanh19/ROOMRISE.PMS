<?php

use App\Http\Controllers\Api\v1\ChannexWebhookController;
use Illuminate\Support\Facades\Route;

$prefix = env('PREFIX_API', 'v1');

Route::group(['prefix' => $prefix], function () {
    Route::post('/webhook/channex', [ChannexWebhookController::class, 'handleWebhook']);
});
