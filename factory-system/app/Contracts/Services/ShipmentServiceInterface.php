<?php

namespace App\Contracts\Services;

use App\DTOs\Shipments\CreateShipmentDTO;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Truck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator;

    public function create(CreateShipmentDTO $dto): Shipment;

    /** @param array<int, int> $orderIds */
    public function attachOrders(Shipment $shipment, array $orderIds): void;

    public function detachOrder(Shipment $shipment, Order $order): void;

    public function dispatch(Shipment $shipment): Shipment;

    public function markOrderDelivered(Shipment $shipment, Order $order): void;

    public function complete(Shipment $shipment): Shipment;

    public function cancel(Shipment $shipment, string $reason): Shipment;

    public function update(Shipment $shipment, CreateShipmentDTO $dto): Shipment;

    public function delete(Shipment $shipment): void;

    /** @return Collection<int, Truck> */
    public function availableTrucks(): Collection;

    /** @return Collection<int, Driver> */
    public function availableDrivers(): Collection;

    /** @return Collection<int, Order> */
    public function readyOrders(): Collection;
}
