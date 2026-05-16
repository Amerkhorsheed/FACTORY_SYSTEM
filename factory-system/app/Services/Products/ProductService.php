<?php

namespace App\Services\Products;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Product CRUD service — manages product lifecycle.
 * Stock operations are handled exclusively by StockService.
 */
class ProductService extends BaseService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products
    ) {}

    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->products->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    /**
     * Create a new product with optional image upload.
     *
     * @throws \Throwable
     */
    public function create(array $data, ?UploadedFile $image = null): Product
    {
        return $this->transaction(function () use ($data, $image) {
            if ($image) {
                $data['image'] = $image->store('products', 'public');
            }

            $data['created_by'] = auth()->id();

            return $this->products->create($data);
        });
    }

    /**
     * Update a product. Replaces image if a new one is provided.
     *
     * @throws \Throwable
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return $this->transaction(function () use ($product, $data, $image) {
            if ($image) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $image->store('products', 'public');
            }

            return $this->products->update($product, $data);
        });
    }

    /**
     * Soft-delete a product.
     *
     * @throws \DomainException if active orders exist
     * @throws \Throwable
     */
    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(int $id): Product
    {
        return $this->products->restore($id);
    }
}
