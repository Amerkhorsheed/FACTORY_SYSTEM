<?php

use App\Http\Controllers\Invoices\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoice Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant|shipping_staff'])->group(function () {
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'destroy']);

    Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])
        ->name('invoices.issue');

    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])
        ->name('invoices.void');

    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])
        ->name('invoices.payments.store');

    Route::delete('invoices/{invoice}/payments/{payment}', [InvoiceController::class, 'deletePayment'])
        ->name('invoices.payments.destroy');

    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])
        ->name('invoices.print');

    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])
        ->name('invoices.download');
});
