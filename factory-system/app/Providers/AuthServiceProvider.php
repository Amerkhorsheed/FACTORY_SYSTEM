<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShipmentPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Activitylog\Models\Activity;

/**
 * Register application policies.
 */
class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Customer::class => CustomerPolicy::class,
        Order::class => OrderPolicy::class,
        Shipment::class => ShipmentPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        Expense::class => ExpensePolicy::class,
        User::class => UserPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        Activity::class => ActivityLogPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
