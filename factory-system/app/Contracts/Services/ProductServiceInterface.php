<?php

namespace App\Contracts\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

interface ProductServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(array $data, ?UploadedFile $image = null): Product;

    /** @param array<string, mixed> $data */
    public function update(Product $product, array $data, ?UploadedFile $image = null): Product;

    public function delete(Product $product): void;

    public function restore(int $id): Product;
}
