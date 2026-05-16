<?php

namespace App\Repositories;

use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Models\StockMovement;
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
}
