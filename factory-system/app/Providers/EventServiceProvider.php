<?php

namespace App\Providers;

use App\Events\InvoiceIssued;
use App\Events\Orders\OrderAccepted;
use App\Events\Orders\OrderCancelled;
use App\Events\Orders\OrderDelivered;
use App\Events\Orders\OrderPlacedByCustomer;
use App\Events\Orders\OrderShipped;
use App\Events\PaymentReceived;
use App\Events\Stock\LowStockDetected;
use App\Listeners\CreateInvoiceOnOrderAccepted;
use App\Listeners\NotifyAdminsOfNewPortalOrder;
use App\Listeners\NotifyCustomerOnInvoiceIssued;
use App\Listeners\NotifyCustomerOnOrderStatusChange;
use App\Listeners\NotifyCustomerOnPaymentReceived;
use App\Listeners\SendLowStockAlert;
use App\Listeners\UpdateCustomerBalanceOnInvoiceIssued;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Observers\InvoiceObserver;
use App\Observers\OrderObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Register domain events, listeners, and model observers.
 *
 * Event map follows SKILLS.md Pattern 11 — Event/Listener.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ─── Order Domain ───────────────────────────────────────
        OrderAccepted::class => [
            CreateInvoiceOnOrderAccepted::class,
            NotifyCustomerOnOrderStatusChange::class,
        ],

        OrderCancelled::class => [
            NotifyCustomerOnOrderStatusChange::class,
        ],

        OrderShipped::class => [
            NotifyCustomerOnOrderStatusChange::class,
        ],

        OrderDelivered::class => [
            NotifyCustomerOnOrderStatusChange::class,
        ],

        OrderPlacedByCustomer::class => [
            NotifyAdminsOfNewPortalOrder::class,
        ],

        // ─── Invoice Domain ─────────────────────────────────────
        InvoiceIssued::class => [
            UpdateCustomerBalanceOnInvoiceIssued::class,
            NotifyCustomerOnInvoiceIssued::class,
        ],

        // ─── Payment Domain ─────────────────────────────────────
        PaymentReceived::class => [
            NotifyCustomerOnPaymentReceived::class,
        ],

        // ─── Stock Domain ───────────────────────────────────────
        LowStockDetected::class => [
            SendLowStockAlert::class,
        ],
    ];

    /**
     * Register model observers.
     */
    public function boot(): void
    {
        parent::boot();

        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
