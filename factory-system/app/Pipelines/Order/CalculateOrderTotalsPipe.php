<?php

namespace App\Pipelines\Order;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\Models\Product;
use Closure;

/**
 * Enriches DTO items with current product prices and returns a new DTO.
 */
class CalculateOrderTotalsPipe
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(CreateOrderDTO $dto, Closure $next): CreateOrderDTO
    {
        $enrichedItems = $dto->items->map(function (OrderItemDTO $item) {
            /** @var Product $product */
            $product = $this->products->findByIdOrFail($item->productId);

            return new OrderItemDTO(
                productId: $item->productId,
                quantity: $item->quantity,
                unitPrice: $product->unit_price,
                discountPercent: $item->discountPercent,
                notes: $item->notes,
            );
        });

        return new CreateOrderDTO(
            customerId: $dto->customerId,
            orderDate: $dto->orderDate,
            items: $enrichedItems,
            requestedDeliveryDate: $dto->requestedDeliveryDate,
            notes: $dto->notes,
            createdBy: $dto->createdBy,
        );
    }
}
