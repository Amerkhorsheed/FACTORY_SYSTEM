<?php

namespace App\Listeners;

use App\Events\Orders\OrderAccepted;
use App\Services\Products\StockService;

/**
 * Deduct stock for all items when an order is accepted.
 *
 * Synchronous — runs within the same transaction as the status change.
 */
class DeductStockOnOrderAccepted
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderAccepted $event): void
    {
        $order = $event->order->load('items.product');

        foreach ($order->items as $item) {
            $this->stockService->moveStock(
                product: $item->product,
                quantity: -$item->quantity,
                type: 'out',
                reason: __('orders.stock_deducted', ['number' => $order->order_number]),
                userId: $order->created_by ?? null,
            );
        }
    }
}
