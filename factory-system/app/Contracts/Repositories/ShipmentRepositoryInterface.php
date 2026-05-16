<?php

namespace App\Contracts\Repositories;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Truck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentRepositoryInterface
{
    public function findById(int $id): ?Shipment;

    public function findByIdOrFail(int $id): Shipment;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getTodayActive(): Collection;

    /** @return Collection<int, Truck> */
    public function availableTrucks(): Collection;

    /** @return Collection<int, Driver> */
    public function availableDrivers(): Collection;

    /** @return Collection<int, Order> */
    public function readyOrders(): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @return Shipment
     */
    public function create(array $data);

    /**
     * @param  array<string, mixed>  $data
     * @return Shipment
     */
    public function update(Shipment $shipment, array $data);

    public function delete(Shipment $shipment): void;
}
