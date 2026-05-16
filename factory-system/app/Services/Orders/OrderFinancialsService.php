<?php

namespace App\Services\Orders;

use App\DTOs\Orders\OrderItemDTO;
use App\Services\BaseService;
use App\Services\SettingService;
use Illuminate\Support\Collection;

/**
 * Calculates order financial totals.
 */
class OrderFinancialsService extends BaseService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * Calculate subtotal, discount, tax, and grand total.
     *
     * @param  Collection<int, OrderItemDTO>  $items
     * @return array<string, int>
     */
    public function calculateTotals(Collection $items): array
    {
        $subtotal = $items->sum(fn (OrderItemDTO $i) => $i->unitPrice * $i->quantity);
        $discount = $items->sum(fn (OrderItemDTO $i) => $i->discountAmount());
        $taxRate = (float) $this->settings->get('invoice_tax_rate', 0);
        $taxable = $subtotal - $discount;
        $tax = (int) round($taxable * ($taxRate / 100));
        $total = $taxable + $tax;

        return compact('subtotal', 'discount', 'tax', 'total');
    }

    /**
     * Calculate the order value total for credit checks.
     *
     * @param  Collection<int, OrderItemDTO>  $items
     */
    public function calculateOrderValue(Collection $items): int
    {
        return $this->calculateTotals($items)['total'];
    }
}
