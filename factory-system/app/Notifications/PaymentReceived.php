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
        $amount = Money::of($this->payment->amount)->format();

        return (new MailMessage)
            ->subject(__('payments.received_notification_subject'))
            ->greeting(__('payments.greeting', ['name' => $notifiable->name]))
            ->line(__('payments.received_message', ['amount' => $amount]))
            ->action(__('payments.view_invoice'), route('invoices.show', $this->payment->invoice_id))
            ->salutation(__('payments.salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'amount' => $this->payment->amount,
            'method' => $this->payment->method,
            'message' => __('payments.received_message', [
                'amount' => Money::of($this->payment->amount)->format(),
            ]),
        ];
    }
}
