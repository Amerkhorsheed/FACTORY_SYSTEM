<?php

namespace Tests\Unit;

use App\Events\Stock\LowStockDetected;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Products\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockService::class);
    }

    /**
     * @test
     */
    public function it_records_an_incoming_stock_movement(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['stock_quantity' => 10, 'created_by' => $user->id]);

        $movement = $this->service->moveStock($product, 'in', 5);

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertSame('in', $movement->type);
        $this->assertSame(10, $movement->quantity_before);
        $this->assertSame(15, $movement->quantity_after);
    }

    /**
     * @test
     */
    public function it_records_an_outgoing_stock_movement(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['stock_quantity' => 10, 'created_by' => $user->id]);

        $movement = $this->service->moveStock($product, 'out', 3);

        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertSame('out', $movement->type);
    }

    /**
     * @test
     */
    public function it_fires_low_stock_event_when_threshold_is_crossed(): void
    {
        Event::fake([LowStockDetected::class]);

        $user = User::factory()->create();
        $product = $this->product([
            'stock_quantity' => 15,
            'low_stock_threshold' => 10,
            'created_by' => $user->id,
        ]);

        $this->service->moveStock($product, 'out', 6);

        Event::assertDispatched(LowStockDetected::class, fn ($e) => $e->product->id === $product->id);
    }

    /**
     * @test
     */
    public function it_does_not_fire_event_when_stock_was_already_below_threshold(): void
    {
        Event::fake([LowStockDetected::class]);

        $user = User::factory()->create();
        $product = $this->product([
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
            'created_by' => $user->id,
        ]);

        $this->service->moveStock($product, 'out', 1);

        Event::assertNotDispatched(LowStockDetected::class);
    }

    /**
     * @test
     */
    public function it_adjusts_stock_to_an_absolute_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['stock_quantity' => 20, 'created_by' => $user->id]);

        $movement = $this->service->adjustStock($product, 15, 'Inventory count correction');

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertSame('adjustment', $movement->type);
    }

    /**
     * @test
     */
    public function it_returns_low_stock_products(): void
    {
        $user = User::factory()->create();
        $this->product(['stock_quantity' => 3, 'low_stock_threshold' => 10, 'created_by' => $user->id]);
        $this->product(['stock_quantity' => 25, 'low_stock_threshold' => 10, 'created_by' => $user->id]);

        $low = $this->service->getLowStockProducts();

        $this->assertCount(1, $low);
        $this->assertSame(3, $low->first()->stock_quantity);
    }

    /** @param array<string, mixed> $overrides */
    private function product(array $overrides = []): Product
    {
        $category = ProductCategory::create([
            'name' => 'Category '.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Product '.uniqid(),
            'unit' => 'box',
            'unit_price' => 10_000,
            'cost_price' => 7_000,
            'stock_quantity' => 100,
            'low_stock_threshold' => 10,
            'is_active' => true,
        ], $overrides));
    }
}
