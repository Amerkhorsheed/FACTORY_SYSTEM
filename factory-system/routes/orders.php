<?php

use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Orders\OrderStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant|shipping_staff'])->group(function () {
    Route::resource('orders', OrderController::class);

    Route::get('orders-daily', [OrderController::class, 'daily'])
        ->name('orders.daily');

    Route::prefix('orders/{order}')->name('orders.status.')->group(function () {
        Route::post('accept', [OrderStatusController::class, 'accept'])->name('accept');
        Route::post('preparing', [OrderStatusController::class, 'preparing'])->name('preparing');
        Route::post('ready', [OrderStatusController::class, 'ready'])->name('ready');
        Route::post('deliver', [OrderStatusController::class, 'deliver'])->name('deliver');
        Route::post('cancel', [OrderStatusController::class, 'cancel'])->name('cancel');
        Route::post('returned', [OrderStatusController::class, 'returned'])->name('returned');
    });
});
