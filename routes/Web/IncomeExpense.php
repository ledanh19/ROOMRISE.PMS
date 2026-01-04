<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncomeExpenseController;

$prefix = 'income-and-expense';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::post('income-expense/update/{incomeandexpense}', [IncomeExpenseController::class, 'update'])->name('income-and-expense.postUpdate');
    Route::get('export', [IncomeExpenseController::class, 'export'])->name('income-and-expense.export');
    Route::get('/income-expense/chart-data', [IncomeExpenseController::class, 'getChartData'])->name('income-and-expense.getChartData');
    Route::get('/income-expense/pie-data', [IncomeExpenseController::class, 'getPieData'])->name('income-and-expense.getPieData');
    Route::get('/income-expense/{id}/export-pdf', [IncomeExpenseController::class, 'exportPdf'])->name('income-expense.export-pdf');
    Route::get('/income-expense/{id}/print', [IncomeExpenseController::class, 'printView'])->name('income-expense.print');
    Route::resource($prefix, IncomeExpenseController::class)->parameters([
        'income-and-expense' => 'incomeandexpense',
    ]);
});
