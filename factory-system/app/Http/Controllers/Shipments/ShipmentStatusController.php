<?php

namespace App\Http\Controllers\Shipments;

use App\Contracts\Services\ShipmentServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Distribution\ShipmentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShipmentStatusController extends Controller
{
    public function __construct(
        private readonly ShipmentServiceInterface $shipmentService,
        private readonly ShipmentStatusService $statusService,
    ) {}

    public function cancel(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('cancel', $shipment);

        $reason = $request->input('reason');
        $this->shipmentService->cancel($shipment, $reason);

        return back()->with('success', __('shipments.cancelled'));
    }

    public function markOrderDelivered(Request $request, Shipment $shipment, Order $order): RedirectResponse
    {
        $this->authorize('updateStatus', $shipment);

        $this->shipmentService->markOrderDelivered($shipment, $order);

        return back()->with('success', __('shipments.order_delivered'));
    }
}
