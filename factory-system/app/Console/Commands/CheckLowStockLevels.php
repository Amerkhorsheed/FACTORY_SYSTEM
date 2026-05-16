<?php

namespace App\Console\Commands;

use App\Events\Stock\LowStockDetected;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Check all products for low stock levels and fire alerts.
 *
 * Scheduled: Every 6 hours.
 */
class CheckLowStockLevels extends Command
{
    protected $signature = 'factory:check-low-stock';

    protected $description = 'فحص مستويات المخزون المنخفض';

    public function handle(): int
    {
        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->get();

        foreach ($lowStock as $product) {
            LowStockDetected::dispatch($product);
        }

        $this->info("Found {$lowStock->count()} products below threshold.");

        return self::SUCCESS;
    }
}
