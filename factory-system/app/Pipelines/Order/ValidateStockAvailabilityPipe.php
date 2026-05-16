<?php

namespace App\Pipelines\Order;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\Orders\CreateOrderDTO;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Closure;

/**
 * Validates stock availability for all order items.
 */
class ValidateStockAvailabilityPipe
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @throws InsufficientStockException
     */
    public function handle(CreateOrderDTO $dto, Closure $next): CreateOrderDTO
    {
        $errors = [];

        foreach ($dto->items as $item) {
            /** @var Product $product */
            $product = $this->products->findByIdOrFail($item->productId);

            if ($product->stock_quantity < $item->quantity) {
                $errors[] = __('orders.insufficient_stock', [
                    'product' => $product->name,
                    'available' => $product->stock_quantity,
                    'requested' => $item->quantity,
                ]);
            }
        }

        if (! empty($errors)) {
            throw new InsufficientStockException(implode("\n", $errors));
        }

        return $next($dto);
    }
}
