<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {
    Route::get('daily-activity-report', [ReportController::class, 'dailyActivity'])->name('report.dailyActivity');
    Route::get('management-report', [ReportController::class, 'managerReport'])->name('report.managerReport');
    Route::get('revenue-report', [ReportController::class, 'revenueReport'])->name('report.revenueReport');
    Route::get('payment-report', [ReportController::class, 'paymentReport'])->name('report.paymentReport');
});
