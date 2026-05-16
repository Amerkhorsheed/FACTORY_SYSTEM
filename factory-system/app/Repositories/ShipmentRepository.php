<?php

namespace App\Repositories;

use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Truck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShipmentRepository extends BaseRepository implements ShipmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Shipment);
    }

    public function findById(int $id): ?Shipment
    {
        return Shipment::find($id);
    }

    public function findByIdOrFail(int $id): Shipment
    {
        return Shipment::findOrFail($id);
    }

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Shipment::query()
            ->with(['truck', 'driver', 'orders'])
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['truck_id'])) {
            $query->where('truck_id', $filters['truck_id']);
        }

        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('shipment_date', $filters['date']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getTodayActive(): Collection
    {
        return Shipment::with(['truck', 'driver'])
            ->active()
            ->forDate(today())
            ->get();
    }

    public function availableTrucks(): Collection
    {
        return Truck::available()->orderBy('plate_number')->get();
    }

    public function availableDrivers(): Collection
    {
        return Driver::active()->orderBy('name')->get();
    }

    public function readyOrders(): Collection
    {
        return Order::with('customer')
            ->where('status', 'ready')
            ->whereNull('shipment_id')
            ->latest('order_date')
            ->limit(100)
            ->get();
    }
}
