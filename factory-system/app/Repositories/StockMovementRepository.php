<?php

namespace App\Repositories;

use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new StockMovement);
    }

    public function getForProduct(int $productId, int $limit = 50): Collection
    {
        return StockMovement::where('product_id', $productId)
            ->with('createdByUser')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getForDateRange(int $productId, string $from, string $to): Collection
    {
        return StockMovement::where('product_id', $productId)
            ->whereBetween('created_at', [$from, $to.' 23:59:59'])
            ->with(['product', 'createdByUser'])
            ->latest()
            ->get();
    }

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return StockMovement::with(['product', 'createdByUser'])
            ->when($filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
