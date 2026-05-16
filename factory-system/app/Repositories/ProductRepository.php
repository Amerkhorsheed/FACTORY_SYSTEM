<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Product);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with('category')->orderBy('sort_order')->orderBy('name');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%")
                    ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['low_stock'])) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function searchForOrder(string $term, int $limit = 10): Collection
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'unit', 'unit_price', 'stock_quantity']);
    }

    public function findByCode(string $code): ?Product
    {
        return Product::where('code', $code)->first();
    }

    public function lockForUpdate(int $id): Product
    {
        return Product::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function getLowStock(): Collection
    {
        return Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get();
    }
}
