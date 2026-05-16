<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('shipments.view');
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shipments.create');
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.edit')
            && in_array($shipment->status, ['planned', 'loading'], true);
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.edit')
            && $shipment->status === 'planned';
    }

    public function dispatch(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.dispatch')
            && $shipment->status === 'planned';
    }

    public function cancel(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.update_status')
            && ! in_array($shipment->status, ['completed', 'cancelled'], true);
    }

    public function updateStatus(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.update_status');
    }

    public function viewManifest(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.view_manifest');
    }
}
