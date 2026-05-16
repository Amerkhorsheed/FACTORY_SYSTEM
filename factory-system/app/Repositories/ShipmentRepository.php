<?php

namespace App\Repositories;

use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\Models\Shipment;
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
}
