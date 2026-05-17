<?php

namespace App\Listeners;

use App\Events\Orders\OrderPlacedByCustomer;
use App\Models\User;
use App\Notifications\AdminNewPortalOrderNotification;

/**
 * Notify admin and accountant users when a customer places an order via the portal.
 */
class NotifyAdminsOfNewPortalOrder
{
    public function handle(OrderPlacedByCustomer $event): void
    {
        $order = $event->order;

        User::query()
            ->role(['super_admin', 'accountant'])
            ->active()
            ->chunkById(50, function ($users) use ($order): void {
                foreach ($users as $user) {
                    $user->notify(new AdminNewPortalOrderNotification($order));
                }
            });
    }
}
