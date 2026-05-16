<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        activity('orders')
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->withProperties([
                'order_number' => $order->order_number,
                'customer' => $order->customer()->value('name'),
                'total' => $order->total_amount,
            ])
            ->log(__('activity.orders.created'));
    }

    public function updated(Order $order): void
    {
        $changes = $this->trackedChanges($order->getChanges(), ['status', 'total_amount', 'discount_amount', 'shipment_id']);

        if ($changes === []) {
            return;
        }

        $description = array_key_exists('status', $changes)
            ? __('activity.orders.status_changed')
            : __('activity.orders.updated');

        activity('orders')
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->withProperties([
                'before' => array_intersect_key($order->getOriginal(), $changes),
                'after' => $changes,
                'number' => $order->order_number,
            ])
            ->log($description);
    }

    public function deleted(Order $order): void
    {
        activity('orders')
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->withProperties(['order_number' => $order->order_number])
            ->log(__('activity.orders.deleted'));
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<int, string>  $allowed
     * @return array<string, mixed>
     */
    private function trackedChanges(array $changes, array $allowed): array
    {
        return array_intersect_key($changes, array_flip($allowed));
    }
}
