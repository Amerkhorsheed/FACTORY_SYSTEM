<?php

namespace App\DTOs\Orders;

/**
 * Represents a single line item in an order DTO.
 */
final class OrderItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity,
        public readonly int $unitPrice,
        public readonly float $discountPercent = 0.0,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Build from validated request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
            unitPrice: (int) $data['unit_price'],
            discountPercent: (float) ($data['discount_percent'] ?? 0.0),
            notes: $data['notes'] ?? null,
        );
    }

    /** Calculate line total in smallest currency unit. */
    public function lineTotal(): int
    {
        $gross = $this->unitPrice * $this->quantity;
        $discount = (int) round($gross * ($this->discountPercent / 100));

        return $gross - $discount;
    }

    /** Calculate discount amount in smallest currency unit. */
    public function discountAmount(): int
    {
        $gross = $this->unitPrice * $this->quantity;

        return (int) round($gross * ($this->discountPercent / 100));
    }
}
