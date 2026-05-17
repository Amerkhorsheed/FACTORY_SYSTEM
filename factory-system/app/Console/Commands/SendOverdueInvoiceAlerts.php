<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Console\Command;

/**
 * Send overdue invoice digest alerts to accounting staff.
 */
class SendOverdueInvoiceAlerts extends Command
{
    protected $signature = 'factory:overdue-alerts';

    protected $description = 'Send overdue invoice alerts to accounting staff';

    public function __construct(
        private readonly NotificationDispatchService $notifications,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->notifications->sendOverdueInvoiceDigest();

        if ($result['invoices'] === 0) {
            $this->info('No overdue invoices found.');

            return self::SUCCESS;
        }

        $this->info("Notified {$result['recipients']} users about {$result['invoices']} overdue invoices.");

        return self::SUCCESS;
    }
}
