<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/report', [DashboardController::class, 'report'])->name('report');
    Route::get('/components', [DashboardController::class, 'components'])->name('components');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/booking-details', [DashboardController::class, 'getBookingDetails'])->name('dashboard.booking-details');

    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats'])->name('dashboard.stats');

    Route::get('/dashboard/booking-sources', [DashboardController::class, 'getBookingSources'])->name('dashboard.booking-sources');
    Route::get('/dashboard/booking-by-source', [DashboardController::class, 'getBookingBySource'])->name('dashboard.booking-by-source');
    Route::get('/dashboard/customer-locations', [DashboardController::class, 'getCustomerLocations'])->name('dashboard.customer-locations');
    Route::get('/dashboard/booking-by-area', [DashboardController::class, 'getBookingByArea'])->name('dashboard.booking-by-area');

    Route::get('/dashboard/properties', [DashboardController::class, 'getProperties'])->name('dashboard.properties');
    // Route::get('/dashboard/areas', [DashboardController::class, 'getAreas'])->name('dashboard.areas'); // Nếu cần
    Route::get('/dashboard/customer-details', [DashboardController::class, 'getCustomerDetails'])->name('dashboard.customer-details');
    Route::get('/dashboard/bookings-need-processing', [DashboardController::class, 'getBookingsNeedProcessing'])->name('dashboard.bookings-need-processing');
    Route::get('/dashboard/current-guests', [DashboardController::class, 'getCurrentGuests'])->name('dashboard.current-guests');
    Route::get('/dashboard/room-status-by-type', [DashboardController::class, 'getRoomStatusByType'])->name('dashboard.room-status-by-type');

    Route::get('/dashboard/room-details', [DashboardController::class, 'getRoomDetails'])->name('dashboard.room-details');

    Route::get('/dashboard-executive', [DashboardController::class, 'DashboardExecutive'])->name('dashboard.DashboardExecutive');
    Route::get('/dashboard-booking', [DashboardController::class, 'dashboardBooking'])->name('dashboard.dashboardBooking');
    Route::get('/dashboard-revenue', [DashboardController::class, 'dashboardRevenue'])->name('dashboard.dashboardRevenue');
    Route::get('/dashboard-performance', [DashboardController::class, 'dashboardPerformance'])->name('dashboard.dashboardPerformance');
});
