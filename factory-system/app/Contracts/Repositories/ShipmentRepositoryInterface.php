<?php

namespace App\Contracts\Repositories;

use App\Models\Shipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ShipmentRepositoryInterface
{
    public function findById(int $id): ?Shipment;

    public function findByIdOrFail(int $id): Shipment;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getTodayActive(): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Shipment;

    /** @param array<string, mixed> $data */
    public function update(Shipment $shipment, array $data): Shipment;

    public function delete(Shipment $shipment): void;
}
