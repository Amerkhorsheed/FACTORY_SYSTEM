<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @test
     */
    public function it_lists_products_with_pagination(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);
        Product::factory(3)->create(['category_id' => $category->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk();
    }

    /**
     * @test
     */
    public function it_creates_a_product_with_auto_generated_code(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('products.store'), [
                'name' => 'منتج اختباري',
                'category_id' => $category->id,
                'unit' => 'كرتون',
                'unit_price' => 50_000,
                'cost_price' => 30_000,
                'stock_quantity' => 100,
                'low_stock_threshold' => 10,
            ])
            ->assertRedirect();

        $product = Product::where('name', 'منتج اختباري')->first();
        $this->assertNotNull($product);
        $this->assertMatchesRegularExpression('/^PRD-\d{4}-\d{5}$/', $product->code);
    }

    /**
     * @test
     */
    public function it_uploads_product_image_to_public_storage(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('products.store'), [
                'code' => 'TEST-002',
                'name' => 'منتج بصورة',
                'category_id' => $category->id,
                'unit' => 'كيس',
                'unit_price' => 10_000,
                'cost_price' => 6_000,
                'stock_quantity' => 50,
                'low_stock_threshold' => 5,
                'image' => UploadedFile::fake()->image('product.jpg'),
            ])
            ->assertRedirect();

        $product = Product::where('name', 'منتج بصورة')->first();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    /**
     * @test
     */
    public function it_prevents_duplicate_product_code(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);
        Product::factory()->create(['code' => 'TEST-DUP', 'category_id' => $category->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('products.store'), [
                'code' => 'TEST-DUP',
                'name' => 'منتج مكرر',
                'category_id' => $category->id,
                'unit' => 'كيس',
                'unit_price' => 1_000,
                'cost_price' => 500,
                'stock_quantity' => 10,
                'low_stock_threshold' => 2,
            ])
            ->assertSessionHasErrors('code');
    }

    /**
     * @test
     */
    public function it_blocks_shipping_staff_from_creating_products(): void
    {
        $staff = User::factory()->create()->assignRole('shipping_staff');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);

        $this->actingAs($staff)
            ->post(route('products.store'), [
                'code' => 'TEST-003',
                'name' => 'test',
                'category_id' => $category->id,
                'unit' => 'test',
                'unit_price' => 1_000,
                'cost_price' => 500,
                'stock_quantity' => 10,
                'low_stock_threshold' => 2,
            ])
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function it_soft_deletes_a_product_and_allows_restore(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));

        $this->actingAs($admin)
            ->post(route('products.restore', $product->id))
            ->assertRedirect();

        $this->assertNotNull(Product::find($product->id));
    }

    /**
     * @test
     */
    public function it_blocks_deletion_when_active_orders_exist(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);
        $customer = Customer::create([
            'name' => 'Customer',
            'phone' => '0912345678',
            'address' => 'Main St',
            'category' => 'B',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'order_date' => today(),
            'subtotal' => 10_000,
            'total_amount' => 10_000,
            'created_by' => $admin->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10_000,
            'line_total' => 10_000,
        ]);

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error', __('products.has_active_orders'));

        $this->assertNotNull(Product::find($product->id));
    }

    /**
     * @test
     */
    public function it_records_stock_adjustment_and_creates_movement(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $category = ProductCategory::create(['name' => 'Cat', 'is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 50,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('stock-adjustments.store'), [
                'product_id' => $product->id,
                'new_quantity' => 45,
                'reason' => 'Physical count',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
        ]);

        $this->assertSame(45, $product->fresh()->stock_quantity);
    }
}
