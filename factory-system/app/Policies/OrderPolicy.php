<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Order model.
 */
class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        if (! $user->hasPermissionTo('orders.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $user->customer?->id === $order->customer_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        if (! $user->hasPermissionTo('orders.edit')) {
            return false;
        }

        return $order->isEditable();
    }

    public function changeStatus(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.edit');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.cancel')
            && $order->isCancellable();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.delete')
            && in_array($order->status, ['pending', 'cancelled'], true);
    }

    public function confirmDelivery(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.confirm_delivery')
            && in_array($order->status, ['shipped', 'ready'], true);
    }

    public function assignShipment(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.assign_shipment')
            && $order->status === 'ready';
    }
}
