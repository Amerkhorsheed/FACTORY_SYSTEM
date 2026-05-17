<?php

namespace App\Listeners;

use App\Events\Stock\LowStockDetected;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send low-stock alert notifications to admin users.
 *
 * Queued — alert dispatch does not block the stock operation.
 */
class SendLowStockAlert implements ShouldQueue
{
    public string $queue = 'notifications';

    public bool $afterCommit = true;

    public function __construct(
        private readonly NotificationDispatchService $notifications,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $this->notifications->sendLowStockAlert($event->product);
    }
}
