<?php

namespace App\Http\Controllers\Shipments;

use App\Contracts\Services\PdfServiceInterface;
use App\Contracts\Services\ShipmentServiceInterface;
use App\DTOs\Shipments\CreateShipmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipments\AttachOrdersRequest;
use App\Http\Requests\Shipments\DispatchShipmentRequest;
use App\Http\Requests\Shipments\StoreShipmentRequest;
use App\Http\Requests\Shipments\UpdateShipmentRequest;
use App\Models\Order;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentServiceInterface $shipmentService,
        private readonly PdfServiceInterface $pdfService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Shipment::class);

        $shipments = $this->shipmentService->list(
            request()->only(['status', 'truck_id', 'driver_id', 'date'])
        );

        return view('shipments.index', compact('shipments'));
    }

    public function create(): View
    {
        $this->authorize('create', Shipment::class);
        $trucks = $this->shipmentService->availableTrucks();
        $drivers = $this->shipmentService->availableDrivers();

        return view('shipments.create', compact('trucks', 'drivers'));
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Shipment::class);

        $dto = new CreateShipmentDTO(
            truck_id: $request->integer('truck_id'),
            driver_id: $request->integer('driver_id'),
            shipment_date: Carbon::parse($request->input('shipment_date')),
            notes: $request->input('notes'),
        );

        $shipment = $this->shipmentService->create($dto);

        return redirect()->route('shipments.show', $shipment)
            ->with('success', __('shipments.created'));
    }

    public function show(Shipment $shipment): View
    {
        $this->authorize('view', $shipment);

        $shipment->load(['truck', 'driver', 'orders.customer']);
        $readyOrders = $this->shipmentService->readyOrders();

        return view('shipments.show', compact('shipment', 'readyOrders'));
    }

    public function edit(Shipment $shipment): View
    {
        $this->authorize('update', $shipment);
        $trucks = $this->shipmentService->availableTrucks();
        $drivers = $this->shipmentService->availableDrivers();

        return view('shipments.edit', compact('shipment', 'trucks', 'drivers'));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);

        $dto = new CreateShipmentDTO(
            truck_id: $request->integer('truck_id'),
            driver_id: $request->integer('driver_id'),
            shipment_date: Carbon::parse($request->input('shipment_date')),
            notes: $request->input('notes'),
        );

        $this->shipmentService->update($shipment, $dto);

        return redirect()->route('shipments.show', $shipment)
            ->with('success', __('shipments.updated'));
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $this->authorize('delete', $shipment);

        $shipment->delete();

        return redirect()->route('shipments.index')
            ->with('success', __('shipments.deleted'));
    }

    public function dispatch(Shipment $shipment, DispatchShipmentRequest $request): RedirectResponse
    {
        $this->authorize('dispatch', $shipment);

        $this->shipmentService->dispatch($shipment);

        return back()->with('success', __('shipments.dispatched'));
    }

    public function attachOrders(Shipment $shipment, AttachOrdersRequest $request): RedirectResponse
    {
        $this->authorize('update', $shipment);

        $this->shipmentService->attachOrders($shipment, $request->input('order_ids', []));

        return back()->with('success', __('shipments.orders_attached'));
    }

    public function detachOrder(Shipment $shipment, Order $order): RedirectResponse
    {
        $this->authorize('update', $shipment);

        $this->shipmentService->detachOrder($shipment, $order);

        return back()->with('success', __('shipments.order_detached'));
    }

    public function manifest(Shipment $shipment): Response
    {
        $this->authorize('viewManifest', $shipment);

        return $this->pdfService->downloadManifest($shipment);
    }
}
