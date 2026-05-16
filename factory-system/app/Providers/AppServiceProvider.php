<?php

namespace App\Providers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Contracts\Services\CustomerServiceInterface;
use App\Contracts\Services\InvoiceServiceInterface;
use App\Contracts\Services\OrderServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Contracts\Services\ShipmentServiceInterface;
use App\Factories\CodeGeneratorFactory;
use App\Repositories\CustomerRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ShipmentRepository;
use App\Repositories\StockMovementRepository;
use App\Services\Customers\CustomerService;
use App\Services\Distribution\ShipmentService;
use App\Services\Invoices\InvoiceService;
use App\Services\Orders\OrderService;
use App\Services\PdfService;
use App\Services\Products\ProductService;
use App\StateMachines\OrderStateMachine;
use App\StateMachines\ShipmentStateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private const REPOSITORY_BINDINGS = [
        OrderRepositoryInterface::class => OrderRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        InvoiceRepositoryInterface::class => InvoiceRepository::class,
        ShipmentRepositoryInterface::class => ShipmentRepository::class,
        StockMovementRepositoryInterface::class => StockMovementRepository::class,
    ];

    /** @var array<class-string, class-string> */
    private const SERVICE_BINDINGS = [
        OrderServiceInterface::class => OrderService::class,
        ProductServiceInterface::class => ProductService::class,
        CustomerServiceInterface::class => CustomerService::class,
        InvoiceServiceInterface::class => InvoiceService::class,
        ShipmentServiceInterface::class => ShipmentService::class,
        PdfServiceInterface::class => PdfService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindContracts(self::REPOSITORY_BINDINGS);
        $this->bindContracts(self::SERVICE_BINDINGS);

        $this->app->singleton(CodeGeneratorFactory::class);
        $this->app->singleton(OrderStateMachine::class);
        $this->app->singleton(ShipmentStateMachine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->isProduction()) {
            Model::shouldBeStrict();
        }
    }

    /**
     * @param  array<class-string, class-string>  $bindings
     */
    private function bindContracts(array $bindings): void
    {
        foreach ($bindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
