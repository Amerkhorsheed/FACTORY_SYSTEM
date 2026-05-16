<?php

use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\ExpenseController;
use App\Http\Controllers\Erp\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ERP Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant'])->group(function () {
    Route::get('/erp', [DashboardController::class, 'index'])->name('erp.dashboard');

    Route::prefix('erp')->name('erp.')->middleware('role:accountant|super_admin')->group(function () {
        Route::resource('expenses', ExpenseController::class);

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('receivables', [ReportController::class, 'receivables'])->name('receivables');
            Route::get('stock', [ReportController::class, 'stock'])->name('stock');
            Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        });
    });
});
