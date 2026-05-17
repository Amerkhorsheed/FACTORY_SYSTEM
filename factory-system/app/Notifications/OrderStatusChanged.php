<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notify the customer when their order status changes.
 */
class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $newStatus,
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
        $statusLabel = config("factory.order_statuses.{$this->newStatus}", $this->newStatus);

        return (new MailMessage)
            ->subject(__('notifications.order_status.subject', [
                'number' => $this->order->order_number,
                'status' => $statusLabel,
            ]))
            ->view('emails.order-status', [
                'action_url' => $this->urlFor($notifiable),
                'name' => $notifiable->name,
                'order_number' => $this->order->order_number,
                'status' => $statusLabel,
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
        $statusLabel = config("factory.order_statuses.{$this->newStatus}", $this->newStatus);

        return [
            'type' => 'order_status_changed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'new_status' => $this->newStatus,
            'status_label' => $statusLabel,
            'message' => __('notifications.order_status.message', [
                'number' => $this->order->order_number,
                'status' => $statusLabel,
            ]),
            'url' => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        return method_exists($notifiable, 'hasRole') && $notifiable->hasRole('customer')
            ? route('portal.orders.show', $this->order)
            : route('orders.show', $this->order);
    }
}
