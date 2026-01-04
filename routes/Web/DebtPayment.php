<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebtPaymentController;

$prefix = 'debt-payment';

Route::middleware([
    'auth'
])->group(function () use ($prefix) {
    Route::resource($prefix, DebtPaymentController::class)->parameters([
        'debt-payment' => 'debtpayment',
    ]);
    Route::get('get-customers', [DebtPaymentController::class, 'getPartners'])->name('debtPayment.getPartners');
    Route::get('/remainings', [DebtPaymentController::class, 'getRemainings'])->name('debtPayment.getRemainings');
    Route::post('/store-remainings', [DebtPaymentController::class, 'storeRemainings'])->name('debtPayment.storeRemainings');
    Route::get('/get-invoice-histories', [DebtPaymentController::class, 'getInvoiceHistories'])->name('debtPayment.loadInvoiceHistories');
    Route::get('debt-payment/detail/{id}', [DebtPaymentController::class, 'getDebtPaymentDetailById'])->name('debtPayment.getDebtPaymentDetailById');
    Route::post('debt-payment/income-expenses', [DebtPaymentController::class, 'storeIncomeExpenses'])->name('debtPayment.storeIncomeExpenses');
});
