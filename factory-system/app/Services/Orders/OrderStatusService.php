<?php

namespace App\Services\Orders;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Events\Orders\OrderAccepted;
use App\Events\Orders\OrderCancelled;
use App\Events\Orders\OrderDelivered;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Invoices\InvoiceService;
use App\Services\Products\StockService;
use App\StateMachines\OrderStateMachine;
use Carbon\Carbon;

/**
 * Manages all order status transitions.
 */
class OrderStatusService extends BaseService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderStateMachine $stateMachine,
        private readonly StockService $stock,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * Accept a pending order: deduct stock, create draft invoice.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function accept(Order $order, User $actor): Order
    {
        $this->stateMachine->transition($order->status, 'accepted');

        return $this->transaction(function () use ($order, $actor) {
            $order->load('items.product');

            foreach ($order->items as $item) {
                $this->stock->moveStock(
                    $item->product,
                    'out',
                    $item->quantity,
                    [
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'unit_cost' => $item->product->cost_price,
                    ]
                );
            }

            $this->invoices->createFromOrder($order);

            $updated = $this->orders->update($order, [
                'status' => 'accepted',
                'accepted_by' => $actor->id,
                'accepted_at' => Carbon::now(),
            ]);

            event(new OrderAccepted($updated));

            return $updated;
        });
    }

    /**
     * Mark order as preparing.
     *
     * @throws InvalidStatusTransitionException
     */
    public function markPreparing(Order $order): Order
    {
        $this->stateMachine->transition($order->status, 'preparing');

        return $this->orders->update($order, ['status' => 'preparing']);
    }

    /**
     * Mark order as ready for shipment.
     *
     * @throws InvalidStatusTransitionException
     */
    public function markReady(Order $order): Order
    {
        $this->stateMachine->transition($order->status, 'ready');

        return $this->orders->update($order, ['status' => 'ready']);
    }

    /**
     * Confirm delivery.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function confirmDelivery(Order $order, User $actor): Order
    {
        $this->stateMachine->transition($order->status, 'delivered');

        return $this->transaction(function () use ($order, $actor) {
            if ($order->invoice && $order->invoice->status === 'draft') {
                $this->invoices->issue($order->invoice);
            }

            $updated = $this->orders->update($order, [
                'status' => 'delivered',
                'delivered_at' => Carbon::now(),
                'shipped_by' => $actor->id,
            ]);

            event(new OrderDelivered($updated));

            return $updated;
        });
    }

    /**
     * Cancel an order: return stock if already deducted, void invoice.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function cancel(Order $order, string $reason, User $actor): Order
    {
        $this->stateMachine->transition($order->status, 'cancelled');

        return $this->transaction(function () use ($order, $reason) {
            $order->load('items.product');

            if (in_array($order->status, ['accepted', 'preparing', 'ready'], true)) {
                foreach ($order->items as $item) {
                    $this->stock->moveStock(
                        $item->product,
                        'return',
                        $item->quantity,
                        ['reference_type' => 'order', 'reference_id' => $order->id]
                    );
                }
            }

            if ($order->invoice && $order->invoice->status === 'draft') {
                $this->invoices->void($order->invoice, __('invoices.voided_due_to_cancellation'));
            }

            $updated = $this->orders->update($order, [
                'status' => 'cancelled',
                'cancel_reason' => $reason,
            ]);

            event(new OrderCancelled($updated));

            return $updated;
        });
    }

    /**
     * Record a full or partial order return.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function recordReturn(Order $order, string $notes): Order
    {
        $this->stateMachine->transition($order->status, 'returned');

        return $this->transaction(function () use ($order, $notes) {
            $order->load('items.product');

            foreach ($order->items as $item) {
                $this->stock->moveStock(
                    $item->product,
                    'return',
                    $item->quantity - $item->returned_qty,
                    ['reference_type' => 'order', 'reference_id' => $order->id]
                );
            }

            return $this->orders->update($order, [
                'status' => 'returned',
                'returned_at' => Carbon::now(),
                'return_notes' => $notes,
            ]);
        });
    }
}
