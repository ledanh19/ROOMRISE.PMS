<?php

use App\Http\Controllers\PolicyController;
use Illuminate\Support\Facades\Route;


$prefix = 'policy';


Route::prefix($prefix)->group(function () {
    Route::get('/', [PolicyController::class, 'index'])->name('policy.index');
});
