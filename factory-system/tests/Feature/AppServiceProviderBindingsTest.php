<?php

namespace Tests\Feature;

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
use App\StateMachines\OrderStateMachine;
use App\StateMachines\ShipmentStateMachine;
use Tests\TestCase;

class AppServiceProviderBindingsTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_repository_contract_bindings(): void
    {
        foreach ($this->repositoryContracts() as $contract) {
            $this->assertTrue($this->app->bound($contract), "{$contract} is not bound.");
        }
    }

    /**
     * @test
     */
    public function it_registers_service_contract_bindings(): void
    {
        foreach ($this->serviceContracts() as $contract) {
            $this->assertTrue($this->app->bound($contract), "{$contract} is not bound.");
        }
    }

    /**
     * @test
     */
    public function it_resolves_stateless_state_machines_as_singletons(): void
    {
        $this->assertSame(
            $this->app->make(OrderStateMachine::class),
            $this->app->make(OrderStateMachine::class)
        );

        $this->assertSame(
            $this->app->make(ShipmentStateMachine::class),
            $this->app->make(ShipmentStateMachine::class)
        );
    }

    /**
     * @return array<int, class-string>
     */
    private function repositoryContracts(): array
    {
        return [
            OrderRepositoryInterface::class,
            ProductRepositoryInterface::class,
            CustomerRepositoryInterface::class,
            InvoiceRepositoryInterface::class,
            ShipmentRepositoryInterface::class,
            StockMovementRepositoryInterface::class,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    private function serviceContracts(): array
    {
        return [
            OrderServiceInterface::class,
            ProductServiceInterface::class,
            CustomerServiceInterface::class,
            InvoiceServiceInterface::class,
            ShipmentServiceInterface::class,
            PdfServiceInterface::class,
        ];
    }
}
