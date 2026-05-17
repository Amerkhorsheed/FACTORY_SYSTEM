<?php

use App\Http\Controllers\Customers\CustomerPortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:customer'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::redirect('dashboard', '/portal')->name('dashboard.redirect');

        Route::get('orders', [CustomerPortalController::class, 'orders'])->name('orders.index');
        Route::get('orders/create', [CustomerPortalController::class, 'createOrder'])->name('orders.create');
        Route::post('orders', [CustomerPortalController::class, 'storeOrder'])->name('orders.store');
        Route::get('orders/{order}', [CustomerPortalController::class, 'showOrder'])->name('orders.show');

        Route::get('invoices', [CustomerPortalController::class, 'invoices'])->name('invoices.index');
        Route::get('invoices/{invoice}', [CustomerPortalController::class, 'showInvoice'])->name('invoices.show');

        Route::get('profile', [CustomerPortalController::class, 'profile'])->name('profile');
        Route::put('profile', [CustomerPortalController::class, 'updateProfile'])->name('profile.update');
    });
