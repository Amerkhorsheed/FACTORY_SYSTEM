<?php

namespace App\Services\Distribution;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class ShipmentStatusService
{
    public function dispatch(Shipment $shipment): void
    {
        if ($shipment->status !== 'planned') {
            throw new InvalidStatusTransitionException('Only planned shipments can be dispatched');
        }

        $shipment->update(['status' => 'dispatched', 'departure_time' => now()]);
    }

    public function markOrderDelivered(Shipment $shipment, Order $order): void
    {
        DB::transaction(function () use ($shipment, $order) {
            if ($order->shipment_id !== $shipment->id) {
                throw new \InvalidArgumentException('Order does not belong to this shipment.');
            }

            $order->status = 'delivered';
            $order->delivered_at = now();
            $order->save();

            $shipment->refresh();
            if ($shipment->allOrdersResolved()) {
                $shipment->update([
                    'status' => 'completed',
                    'return_time' => now(),
                ]);
            }
        });
    }
}
