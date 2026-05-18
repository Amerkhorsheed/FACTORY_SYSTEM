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
        return $this->transaction(function () use ($order, $actor) {
            $order = $this->lockOrder($order, ['items.product', 'invoice']);
            $this->stateMachine->transition($order->status, 'accepted');

            if ($order->invoice) {
                throw new \DomainException(__('invoices.already_exists'));
            }

            foreach ($order->items->groupBy('product_id') as $items) {
                $item = $items->first();
                $this->stock->moveStock(
                    $item->product,
                    'out',
                    $items->sum('quantity'),
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
        return $this->transaction(function () use ($order) {
            $order = $this->lockOrder($order);
            $this->stateMachine->transition($order->status, 'preparing');

            return $this->orders->update($order, ['status' => 'preparing']);
        });
    }

    /**
     * Mark order as ready for shipment.
     *
     * @throws InvalidStatusTransitionException
     */
    public function markReady(Order $order): Order
    {
        return $this->transaction(function () use ($order) {
            $order = $this->lockOrder($order);
            $this->stateMachine->transition($order->status, 'ready');

            return $this->orders->update($order, ['status' => 'ready']);
        });
    }

    /**
     * Confirm delivery.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function confirmDelivery(Order $order, User $actor): Order
    {
        return $this->transaction(function () use ($order, $actor) {
            $order = $this->lockOrder($order, ['invoice']);
            $this->stateMachine->transition($order->status, 'delivered');

            if ($order->invoice && $order->invoice->status === 'draft') {
                $this->invoices->issue($order->invoice);
            }

            $updated = $this->orders->update($order, [
                'status' => 'delivered',
                'delivered_at' => Carbon::now(),
                'delivered_by' => $actor->id,
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
        return $this->transaction(function () use ($order, $reason) {
            $order = $this->lockOrder($order, ['items.product', 'invoice']);
            $this->stateMachine->transition($order->status, 'cancelled');

            if ($order->invoice && ! $order->invoice->canBeVoided()) {
                throw new \DomainException(__('invoices.cannot_void'));
            }

            if (in_array($order->status, ['accepted', 'preparing', 'ready'], true)) {
                foreach ($order->items->groupBy('product_id') as $items) {
                    $item = $items->first();
                    $this->stock->moveStock(
                        $item->product,
                        'return',
                        $items->sum('quantity'),
                        ['reference_type' => 'order', 'reference_id' => $order->id]
                    );
                }
            }

            if ($order->invoice) {
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
        return $this->transaction(function () use ($order, $notes) {
            $order = $this->lockOrder($order, ['items.product']);
            $this->stateMachine->transition($order->status, 'returned');

            foreach ($order->items as $item) {
                $returnQuantity = max(0, $item->quantity - $item->returned_qty);

                if ($returnQuantity === 0) {
                    continue;
                }

                $this->stock->moveStock(
                    $item->product,
                    'return',
                    $returnQuantity,
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

    /** @param array<int, string> $relations */
    private function lockOrder(Order $order, array $relations = []): Order
    {
        return Order::query()
            ->with($relations)
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
