<?php

namespace App\Notifications;

use App\Models\Payment;
use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notify the customer when a payment is received on their invoice.
 */
class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment,
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
        $amount = Money::of($this->payment->amount)->format();
        $invoiceNumber = $this->invoiceNumber();

        return (new MailMessage)
            ->subject(__('notifications.payment_received.subject', ['invoice' => $invoiceNumber]))
            ->view('emails.payment-confirmed', [
                'action_url' => $this->urlFor($notifiable),
                'amount' => $amount,
                'invoice_number' => $invoiceNumber,
                'name' => $notifiable->name,
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
        $invoiceNumber = $this->invoiceNumber();

        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'amount' => $this->payment->amount,
            'method' => $this->payment->payment_method,
            'message' => __('notifications.payment_received.message', [
                'amount' => Money::of($this->payment->amount)->format(),
                'invoice' => $invoiceNumber,
            ]),
            'url' => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        return method_exists($notifiable, 'hasRole') && $notifiable->hasRole('customer')
            ? route('portal.invoices.show', $this->payment->invoice_id)
            : route('invoices.show', $this->payment->invoice_id);
    }

    private function invoiceNumber(): string
    {
        $this->payment->loadMissing('invoice');

        return $this->payment->invoice?->invoice_number ?? '';
    }
}
