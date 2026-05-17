<?php

namespace App\Listeners;

use App\Events\Orders\OrderAccepted;
use App\Events\Orders\OrderCancelled;
use App\Events\Orders\OrderDelivered;
use App\Events\Orders\OrderShipped;
use App\Notifications\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send a notification to the customer when their order changes status.
 *
 * Queued — notification dispatch does not block the main request.
 */
class NotifyCustomerOnOrderStatusChange implements ShouldQueue
{
    public string $queue = 'notifications';

    public bool $afterCommit = true;

    /**
     * Handle the event.
     */
    public function handle(OrderAccepted|OrderCancelled|OrderShipped|OrderDelivered $event): void
    {
        $order = $event->order->load('customer.user');

        if ($order->customer && $order->customer->user) {
            $order->customer->user->notify(
                new OrderStatusChanged($order, $order->status)
            );
        }
    }
}
