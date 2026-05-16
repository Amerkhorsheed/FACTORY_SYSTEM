<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdue;
use Illuminate\Console\Command;

/**
 * Send overdue invoice reminders to customers.
 *
 * Scheduled: Daily at 9:00 AM (Asia/Damascus).
 */
class SendOverdueInvoiceAlerts extends Command
{
    protected $signature = 'factory:send-overdue-alerts';

    protected $description = 'إرسال تنبيهات الفواتير المتأخرة';

    public function handle(): int
    {
        $overdue = Invoice::query()
            ->where('status', 'issued')
            ->where('due_date', '<', now())
            ->where('balance_due', '>', 0)
            ->with('customer.user')
            ->get();

        $count = 0;

        foreach ($overdue as $invoice) {
            if ($invoice->customer?->user) {
                $invoice->customer->user->notify(new InvoiceOverdue($invoice));
                $count++;
            }
        }

        $this->info("Sent {$count} overdue invoice alerts.");

        return self::SUCCESS;
    }
}
