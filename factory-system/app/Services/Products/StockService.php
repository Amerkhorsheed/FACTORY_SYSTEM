<?php

namespace App\Services\Products;

use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Events\Stock\LowStockDetected;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Manages all stock movement operations.
 * All stock changes go through this service exclusively.
 */
class StockService extends BaseService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movements
    ) {}

    /**
     * Record a stock movement and atomically update product quantity.
     *
     * @throws InsufficientStockException when outgoing qty exceeds stock
     * @throws \Throwable on transaction failure
     */
    public function moveStock(Product $product, string $type, int $quantity, array $meta = []): StockMovement
    {
        return $this->transaction(function () use ($product, $type, $quantity, $meta) {
            $before = $product->stock_quantity;
            $after = $this->calculateNewStock($before, $type, $quantity);

            $movement = $this->movements->create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id' => $meta['reference_id'] ?? null,
                'unit_cost' => $meta['unit_cost'] ?? null,
                'notes' => $meta['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $product->update(['stock_quantity' => $after]);

            $this->checkLowStockThreshold($product, $before, $after);

            return $movement;
        });
    }

    /**
     * Adjust stock to a specific absolute quantity.
     */
    public function adjustStock(Product $product, int $newQuantity, string $reason): StockMovement
    {
        $diff = $newQuantity - $product->stock_quantity;

        return $this->moveStock($product, 'adjustment', $diff, [
            'notes' => $reason,
        ]);
    }

    public function getLowStockProducts(): Collection
    {
        return Product::query()
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get();
    }

    /** @param array<string, mixed> $filters */
    public function listMovements(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->movements->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    public function getRecentMovements(Product $product, int $limit = 50): Collection
    {
        return $this->movements->getForProduct($product->id, $limit);
    }

    private function calculateNewStock(int $before, string $type, int $qty): int
    {
        return match ($type) {
            'in', 'return', 'adjustment' => $before + $qty,
            'out' => $before - $qty,
            default => throw new \InvalidArgumentException("Unknown movement type: {$type}"),
        };
    }

    private function checkLowStockThreshold(Product $product, int $before, int $after): void
    {
        $threshold = $product->low_stock_threshold;

        if ($before > $threshold && $after <= $threshold) {
            event(new LowStockDetected($product));
        }
    }
}
