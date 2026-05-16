<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Remind customer about an overdue invoice.
 */
class InvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $balance = Money::of($this->invoice->balance_due)->format();

        return (new MailMessage)
            ->subject(__('invoices.overdue_notification_subject', ['number' => $this->invoice->invoice_number]))
            ->greeting(__('invoices.greeting', ['name' => $notifiable->name]))
            ->line(__('invoices.overdue_message', [
                'number' => $this->invoice->invoice_number,
                'balance' => $balance,
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
            ]))
            ->action(__('invoices.view_invoice'), route('invoices.show', $this->invoice))
            ->salutation(__('invoices.salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'balance_due' => $this->invoice->balance_due,
            'due_date' => $this->invoice->due_date?->toDateString(),
            'message' => __('invoices.overdue_message', [
                'number' => $this->invoice->invoice_number,
                'balance' => Money::of($this->invoice->balance_due)->format(),
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
            ]),
        ];
    }
}
