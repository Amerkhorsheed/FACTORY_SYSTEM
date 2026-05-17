<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Alert admin users when a product falls below its low-stock threshold.
 */
class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Collection $products,
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
            ->subject(__('notifications.low_stock.subject', ['count' => $this->products->count()]))
            ->view('emails.low-stock', [
                'action_url' => route('low-stock-alert'),
                'name' => $notifiable->name,
                'products' => $this->products,
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
            'type' => 'low_stock_alert',
            'count' => $this->products->count(),
            'message' => $this->message(),
            'url' => route('low-stock-alert'),
            'items' => $this->products->map(fn ($product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock_quantity,
                'threshold' => $product->low_stock_threshold,
            ])->values()->all(),
        ];
    }

    private function message(): string
    {
        if ($this->products->count() === 1) {
            $product = $this->products->first();

            return __('notifications.low_stock.single_message', [
                'current' => $product->stock_quantity,
                'product' => $product->name,
                'threshold' => $product->low_stock_threshold,
            ]);
        }

        return __('notifications.low_stock.message', ['count' => $this->products->count()]);
    }
}
