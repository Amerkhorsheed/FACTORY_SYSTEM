<?php

namespace App\Contracts\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * @return Product|null
     */
    public function findById(int $id);

    /**
     * @return Product
     */
    public function findByIdOrFail(int $id);

    public function findByCode(string $code): ?Product;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function searchForOrder(string $term, int $limit = 10): Collection;

    public function lockForUpdate(int $id): Product;

    public function getLowStock(): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @return Product
     */
    public function create(array $data);

    /**
     * @param  array<string, mixed>  $data
     * @return Product
     */
    public function update(Product $product, array $data);

    public function delete(Product $product): void;

    /**
     * @return Product
     */
    public function restore(int $id);
}
