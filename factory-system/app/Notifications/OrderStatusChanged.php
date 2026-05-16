<?php

namespace App\Notifications;

use App\Models\Order;
use App\ValueObjects\Money;
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
        $statusLabel = config("factory.order_statuses.{$this->newStatus}", $this->newStatus);

        return (new MailMessage)
            ->subject(__('orders.status_notification_subject', ['number' => $this->order->order_number]))
            ->greeting(__('orders.greeting', ['name' => $notifiable->name]))
            ->line(__('orders.status_changed_to', [
                'number' => $this->order->order_number,
                'status' => $statusLabel,
            ]))
            ->action(__('orders.view_order'), route('orders.show', $this->order))
            ->salutation(__('orders.salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'new_status' => $this->newStatus,
            'message' => __('orders.status_changed_to', [
                'number' => $this->order->order_number,
                'status' => config("factory.order_statuses.{$this->newStatus}", $this->newStatus),
            ]),
        ];
    }
}
