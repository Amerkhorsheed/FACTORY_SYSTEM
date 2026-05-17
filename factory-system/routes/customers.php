<?php

use App\Http\Controllers\Customers\CustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant|shipping_staff'])->group(function () {
    Route::resource('customers', CustomerController::class);

    Route::post('customers/{customer}/activate', [CustomerController::class, 'activate'])
        ->name('customers.activate');

    Route::post('customers/{customer}/portal-access', [CustomerController::class, 'togglePortalAccess'])
        ->name('customers.portal-access');

    Route::get('customers/{customer}/statement', [CustomerController::class, 'statement'])
        ->name('customers.statement');

    Route::get('customers/{customer}/statement/pdf', [CustomerController::class, 'statementPdf'])
        ->name('customers.statement.pdf');
});
