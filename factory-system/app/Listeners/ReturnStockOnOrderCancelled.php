<?php

namespace App\Listeners;

use App\Events\Orders\OrderCancelled;
use App\Services\Products\StockService;

/**
 * Return stock for all items when an order is cancelled.
 *
 * Synchronous — ensures stock is restored in the same transaction.
 */
class ReturnStockOnOrderCancelled
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order->load('items.product');

        foreach ($order->items as $item) {
            $this->stockService->moveStock(
                product: $item->product,
                quantity: $item->quantity,
                type: 'return',
                reason: __('orders.stock_returned', ['number' => $order->order_number]),
                userId: null,
            );
        }
    }
}
