<?php

namespace App\Services\Notifications;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;
use App\Notifications\InvoiceOverdue;
use App\Notifications\LowStockAlert;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Notification;

class NotificationDispatchService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly ProductRepositoryInterface $products,
        private readonly UserRepository $users,
    ) {}

    /** @return array{recipients:int,invoices:int} */
    public function sendOverdueInvoiceDigest(): array
    {
        $overdueInvoices = $this->invoices->getOverdue()
            ->where('balance_due', '>', 0)
            ->values();

        if ($overdueInvoices->isEmpty()) {
            return ['recipients' => 0, 'invoices' => 0];
        }

        $recipients = $this->staffRecipients();
        Notification::send($recipients, new InvoiceOverdue($overdueInvoices));

        return ['recipients' => $recipients->count(), 'invoices' => $overdueInvoices->count()];
    }

    /** @return array{recipients:int,products:int} */
    public function sendLowStockDigest(): array
    {
        $products = $this->products->getLowStock();

        if ($products->isEmpty()) {
            return ['recipients' => 0, 'products' => 0];
        }

        $recipients = $this->staffRecipients();
        Notification::send($recipients, new LowStockAlert($products));

        return ['recipients' => $recipients->count(), 'products' => $products->count()];
    }

    public function sendLowStockAlert(Product $product): int
    {
        $recipients = $this->staffRecipients();
        Notification::send($recipients, new LowStockAlert(collect([$product])));

        return $recipients->count();
    }

    /** @return EloquentCollection<int, User> */
    private function staffRecipients(): EloquentCollection
    {
        return $this->users->activeWithRoles(['accountant', 'super_admin']);
    }
}
