<?php

use App\Http\Controllers\Shipments\ShipmentController;
use App\Http\Controllers\Shipments\ShipmentStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shipment Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant|shipping_staff'])->group(function () {
    Route::resource('shipments', ShipmentController::class);

    Route::post('shipments/{shipment}/dispatch', [ShipmentController::class, 'dispatch'])
        ->name('shipments.dispatch');

    Route::post('shipments/{shipment}/attach-orders', [ShipmentController::class, 'attachOrders'])
        ->name('shipments.attach-orders');

    Route::post('shipments/{shipment}/detach-order/{order}', [ShipmentController::class, 'detachOrder'])
        ->name('shipments.detach-order');

    Route::get('shipments/{shipment}/manifest', [ShipmentController::class, 'manifest'])
        ->name('shipments.manifest');

    Route::prefix('shipments/{shipment}')->name('shipments.status.')->group(function () {
        Route::post('cancel', [ShipmentStatusController::class, 'cancel'])->name('cancel');
    });

    Route::post('shipments/{shipment}/orders/{order}/delivered', [ShipmentStatusController::class, 'markOrderDelivered'])
        ->name('shipments.orders.delivered');
});
