<?php

namespace App\DTOs\Orders;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Immutable data transfer object for order creation.
 */
final class CreateOrderDTO
{
    /**
     * @param  Collection<int, OrderItemDTO>  $items
     */
    public function __construct(
        public readonly int $customerId,
        public readonly Carbon $orderDate,
        public readonly Collection $items,
        public readonly ?Carbon $requestedDeliveryDate = null,
        public readonly ?string $notes = null,
        public readonly int $createdBy = 0,
    ) {}

    /**
     * Build from validated request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            orderDate: Carbon::parse($data['order_date']),
            items: collect($data['items'])->map(
                fn (array $item) => OrderItemDTO::fromArray($item)
            ),
            requestedDeliveryDate: isset($data['requested_delivery_date'])
                ? Carbon::parse($data['requested_delivery_date'])
                : null,
            notes: $data['notes'] ?? null,
            createdBy: (int) ($data['created_by'] ?? auth()->id()),
        );
    }

    /** Total item count across all line items. */
    public function totalQuantity(): int
    {
        return $this->items->sum(fn (OrderItemDTO $i) => $i->quantity);
    }
}
