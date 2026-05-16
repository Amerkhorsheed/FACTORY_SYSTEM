<?php

namespace App\Listeners;

use App\Events\Orders\OrderAccepted;
use App\Events\Orders\OrderCancelled;
use App\Events\Orders\OrderShipped;
use App\Notifications\OrderStatusChanged;

/**
 * Send a notification to the customer when their order changes status.
 *
 * Queued — notification dispatch does not block the main request.
 */
class NotifyCustomerOnOrderStatusChange
{
    /**
     * Handle the event.
     */
    public function handle(OrderAccepted|OrderCancelled|OrderShipped $event): void
    {
        $order = $event->order->load('customer');

        if ($order->customer && $order->customer->user) {
            $order->customer->user->notify(
                new OrderStatusChanged($order, $order->status)
            );
        }
    }
}
