<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Console\Command;

/**
 * Send a daily low-stock digest to accounting staff.
 */
class CheckLowStockLevels extends Command
{
    protected $signature = 'factory:low-stock-check';

    protected $description = 'Check low stock products and send a digest alert';

    public function __construct(
        private readonly NotificationDispatchService $notifications,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->notifications->sendLowStockDigest();

        if ($result['products'] === 0) {
            $this->info('All products are above threshold.');

            return self::SUCCESS;
        }

        $this->info("Sent low-stock alert for {$result['products']} products to {$result['recipients']} users.");

        return self::SUCCESS;
    }
}
