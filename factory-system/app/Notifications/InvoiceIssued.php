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
    ) {
        $this->onQueue('notifications')->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = Money::of($this->invoice->total_amount)->format();

        return (new MailMessage)
            ->subject(__('notifications.invoice_issued.subject', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->view('emails.invoice-issued', [
                'action_url' => $this->urlFor($notifiable),
                'invoice_number' => $this->invoice->invoice_number,
                'name' => $notifiable->name,
                'total' => $total,
            ]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload($notifiable);
    }

    /** @return array<string, mixed> */
    private function payload(object $notifiable): array
    {
        return [
            'type' => 'invoice_issued',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total_amount' => $this->invoice->total_amount,
            'message' => __('notifications.invoice_issued.message', [
                'amount' => Money::of($this->invoice->total_amount)->format(),
                'number' => $this->invoice->invoice_number,
            ]),
            'url' => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        return method_exists($notifiable, 'hasRole') && $notifiable->hasRole('customer')
            ? route('portal.invoices.show', $this->invoice)
            : route('invoices.show', $this->invoice);
    }
}
