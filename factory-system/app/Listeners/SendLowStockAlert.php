<?php

namespace App\Listeners;

use App\Events\Stock\LowStockDetected;
use App\Models\User;
use App\Notifications\LowStockAlert;

/**
 * Send low-stock alert notifications to admin users.
 *
 * Queued — alert dispatch does not block the stock operation.
 */
class SendLowStockAlert
{
    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $admins = User::role('super_admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new LowStockAlert($event->product));
        }
    }
}
