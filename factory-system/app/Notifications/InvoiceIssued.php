<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notify the customer when an invoice is formally issued.
 */
class InvoiceIssued extends Notification implements ShouldQueue
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
        $total = Money::of($this->invoice->total_amount)->format();

        return (new MailMessage)
            ->subject(__('invoices.issued_notification_subject', ['number' => $this->invoice->invoice_number]))
            ->greeting(__('invoices.greeting', ['name' => $notifiable->name]))
            ->line(__('invoices.issued_message', [
                'number' => $this->invoice->invoice_number,
                'total' => $total,
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
            'total_amount' => $this->invoice->total_amount,
            'message' => __('invoices.issued_message', [
                'number' => $this->invoice->invoice_number,
                'total' => Money::of($this->invoice->total_amount)->format(),
            ]),
        ];
    }
}
