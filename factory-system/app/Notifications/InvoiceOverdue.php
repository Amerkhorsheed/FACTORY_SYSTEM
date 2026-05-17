<?php

namespace App\Notifications;

use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Send a staff digest for overdue invoices.
 */
class InvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Collection $invoices,
    ) {
        $this->onQueue('notifications')->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.invoice_overdue.subject', ['count' => $this->invoices->count()]))
            ->view('emails.invoice-overdue', [
                'action_url' => route('erp.reports.receivables'),
                'invoices' => $this->invoices,
                'name' => $notifiable->name,
                'total_due' => Money::of($this->totalDue())->format(),
            ]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'type' => 'invoice_overdue',
            'count' => $this->invoices->count(),
            'total_due' => $this->totalDue(),
            'message' => __('notifications.invoice_overdue.message', [
                'amount' => Money::of($this->totalDue())->format(),
                'count' => $this->invoices->count(),
            ]),
            'url' => route('erp.reports.receivables'),
        ];
    }

    private function totalDue(): int
    {
        return (int) $this->invoices->sum('balance_due');
    }
}
