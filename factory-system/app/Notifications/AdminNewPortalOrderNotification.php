<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notify admins when a customer places an order via the self-service portal.
 */
class AdminNewPortalOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
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
        return (new MailMessage)
            ->subject(__('notifications.portal_order.subject', [
                'number' => $this->order->order_number,
            ]))
            ->view('emails.admin-portal-order', [
                'action_url' => route('orders.show', $this->order),
                'name' => $notifiable->name,
                'order_number' => $this->order->order_number,
                'customer_name' => $this->order->customer->name,
                'total' => $this->order->formatted_total_amount,
            ]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'type' => 'portal_order_placed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_id' => $this->order->customer_id,
            'customer_name' => $this->order->customer->name,
            'message' => __('notifications.portal_order.message', [
                'number' => $this->order->order_number,
                'customer' => $this->order->customer->name,
            ]),
            'url' => route('orders.show', $this->order),
        ];
    }
}
