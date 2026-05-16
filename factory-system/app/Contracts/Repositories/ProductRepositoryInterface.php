<?php

namespace App\Contracts\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findByIdOrFail(int $id): Product;

    public function findByCode(string $code): ?Product;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function searchForOrder(string $term, int $limit = 10): Collection;

    public function lockForUpdate(int $id): Product;

    public function getLowStock(): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Product;

    /** @param array<string, mixed> $data */
    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;

    public function restore(int $id): Product;
}
