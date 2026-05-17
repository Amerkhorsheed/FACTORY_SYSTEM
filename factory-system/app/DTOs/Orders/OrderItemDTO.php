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
        public readonly int $discountBasisPoints = 0,
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
            discountBasisPoints: self::parseDiscountBasisPoints($data['discount_percent'] ?? 0),
            notes: $data['notes'] ?? null,
        );
    }

    /** Calculate line total in smallest currency unit. */
    public function lineTotal(): int
    {
        $gross = $this->unitPrice * $this->quantity;
        $discount = $this->discountAmount();

        return $gross - $discount;
    }

    /** Calculate discount amount in smallest currency unit. */
    public function discountAmount(): int
    {
        $gross = $this->unitPrice * $this->quantity;

        return intdiv(($gross * $this->discountBasisPoints) + 5000, 10000);
    }

    public function discountPercentForStorage(): string
    {
        $whole = intdiv($this->discountBasisPoints, 100);
        $decimal = str_pad((string) ($this->discountBasisPoints % 100), 2, '0', STR_PAD_LEFT);

        return "{$whole}.{$decimal}";
    }

    private static function parseDiscountBasisPoints(mixed $value): int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $raw)) {
            throw new \InvalidArgumentException('Discount percent must have at most two decimal places.');
        }

        [$whole, $decimal] = array_pad(explode('.', $raw, 2), 2, '');
        $basisPoints = ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');

        if ($basisPoints > 10000) {
            throw new \InvalidArgumentException('Discount percent cannot exceed 100.');
        }

        return $basisPoints;
    }
}
