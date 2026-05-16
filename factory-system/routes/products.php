<?php

use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\StockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product & Stock Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant|shipping_staff'])->group(function () {
    Route::resource('products', ProductController::class);
    Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');

    Route::get('stock-movements', [StockController::class, 'index'])->name('stock-movements.index');
    Route::post('stock-adjustments', [StockController::class, 'adjust'])->name('stock-adjustments.store');
    Route::get('low-stock-alert', [StockController::class, 'lowAlert'])->name('low-stock-alert');
});
