<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
    ];

    public function boot(): void
    {
        //
    }
}
