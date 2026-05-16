<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alert admin users when a product falls below its low-stock threshold.
 */
class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Product $product,
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
        return (new MailMessage)
            ->subject(__('products.low_stock_alert_subject', ['name' => $this->product->name]))
            ->greeting(__('products.greeting', ['name' => $notifiable->name]))
            ->line(__('products.low_stock_message', [
                'product' => $this->product->name,
                'current' => $this->product->current_stock,
                'threshold' => $this->product->low_stock_threshold,
            ]))
            ->action(__('products.view_product'), route('products.show', $this->product))
            ->salutation(__('products.salutation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => $this->product->current_stock,
            'threshold' => $this->product->low_stock_threshold,
            'message' => __('products.low_stock_message', [
                'product' => $this->product->name,
                'current' => $this->product->current_stock,
                'threshold' => $this->product->low_stock_threshold,
            ]),
        ];
    }
}
