<?php

namespace Tests\Feature;

use App\Livewire\Portal\OrderCart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for the customer portal interactive order cart.
 */
class PortalOrderCartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create()->assignRole('customer');
        $this->customer = Customer::factory()->create([
            'user_id' => $this->user->id,
            'credit_limit' => 500_000,
            'outstanding_balance' => 0,
        ]);
    }

    #[Test]
    public function it_displays_product_catalog(): void
    {
        Product::factory()->count(3)->create(['stock_quantity' => 10]);

        Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->assertViewHas('filteredProducts', fn ($products) => $products->count() === 3);
    }

    #[Test]
    public function it_adds_products_to_cart_and_calculates_totals(): void
    {
        $product = Product::factory()->create([
            'unit_price' => 10_000,
            'stock_quantity' => 10,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->call('addProduct', $product->id);

        $component->assertSet('items', fn ($items) => count($items) === 1)
            ->assertSet('items.0.quantity', 1)
            ->assertSet('items.0.unit_price', 10_000);

        $component->call('updateQuantity', 0, 3);

        $component->assertSet('subtotal', 30_000);
    }

    #[Test]
    public function it_blocks_checkout_when_credit_limit_exceeded(): void
    {
        $product = Product::factory()->create([
            'unit_price' => 600_000,
            'stock_quantity' => 10,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->call('addProduct', $product->id);

        $component->assertSet('showCreditWarning', true)
            ->assertSet('canCheckout', false);
    }

    #[Test]
    public function it_blocks_checkout_when_stock_unavailable(): void
    {
        $product = Product::factory()->create([
            'unit_price' => 10_000,
            'stock_quantity' => 2,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->call('addProduct', $product->id)
            ->call('updateQuantity', 0, 5);

        $component->assertSet('items.0.quantity', 1);
    }

    #[Test]
    public function it_creates_order_with_multiple_items(): void
    {
        $products = Product::factory()->count(2)->create([
            'stock_quantity' => 10,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->call('addProduct', $products[0]->id)
            ->call('addProduct', $products[1]->id)
            ->set('requestedDeliveryDate', now()->addWeek()->toDateString())
            ->set('notes', 'Urgent order')
            ->call('checkout');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'status' => 'pending',
            'notes' => 'Urgent order',
        ]);

        $component->assertDispatched('orderCreated');
    }

    #[Test]
    public function it_filters_products_by_search_query(): void
    {
        Product::factory()->create(['name' => 'Cement Grade A', 'stock_quantity' => 10]);
        Product::factory()->create(['name' => 'Steel Rebar', 'stock_quantity' => 10]);

        Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->set('searchQuery', 'Cement')
            ->assertViewHas('filteredProducts', fn ($products) => $products->count() === 1);
    }

    #[Test]
    public function it_removes_product_from_cart(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->call('addProduct', $product->id)
            ->call('removeProduct', 0);

        $component->assertSet('items', fn ($items) => count($items) === 0);
    }

    #[Test]
    public function it_increments_quantity_when_adding_same_product_twice(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderCart::class, ['customer' => $this->customer])
            ->call('addProduct', $product->id)
            ->call('addProduct', $product->id);

        $component->assertSet('items', fn ($items) => count($items) === 1)
            ->assertSet('items.0.quantity', 2);
    }
}
