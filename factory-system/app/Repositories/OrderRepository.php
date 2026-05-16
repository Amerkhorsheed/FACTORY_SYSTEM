<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete order repository.
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Order);
    }

    /**
     * @return Order|null
     */
    public function findById(int $id)
    {
        return Order::with(['customer', 'items.product', 'invoice', 'shipment'])->find($id);
    }

    /**
     * @return Order
     */
    public function findByIdOrFail(int $id)
    {
        return Order::with(['customer', 'items.product', 'invoice', 'shipment'])->findOrFail($id);
    }

    public function findByNumber(string $number): ?Order
    {
        return Order::where('order_number', $number)->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'shipment'])
            ->latest('order_date')
            ->latest('id');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        if (! empty($filters['status'])) {
            is_array($filters['status'])
                ? $query->whereIn('status', $filters['status'])
                : $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['region'])) {
            $query->whereHas('customer', fn ($c) => $c->where('region', $filters['region']));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getForDate(Carbon $date): Collection
    {
        return Order::with(['customer', 'items', 'shipment'])
            ->whereDate('order_date', $date)
            ->orderBy('status')
            ->get();
    }

    public function getPendingForCustomer(int $customerId): Collection
    {
        return Order::where('customer_id', $customerId)
            ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->get();
    }

    public function getReadyOrders(): Collection
    {
        return Order::with(['customer'])
            ->where('status', 'ready')
            ->whereNull('shipment_id')
            ->orderBy('order_date')
            ->get();
    }
}
