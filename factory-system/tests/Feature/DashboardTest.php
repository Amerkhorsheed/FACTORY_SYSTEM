<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_displays_dashboard_with_kpis(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        Order::factory()->create(['order_date' => today(), 'total_amount' => 100_000]);
        Shipment::factory()->create(['status' => 'planned']);
        Invoice::factory()->overdue()->create(['due_date' => today()->subDay(), 'balance_due' => 50_000]);
        Expense::factory()->create(['expense_date' => today(), 'amount' => 20_000]);
        Product::factory()->create(['stock_quantity' => 2, 'low_stock_threshold' => 5]);

        $response = $this->actingAs($admin)
            ->get(route('erp.dashboard'));

        $response->assertOk();
    }

    /** @test */
    public function it_blocks_dashboard_for_customers(): void
    {
        $customerUser = User::factory()->create()->assignRole('customer');

        $this->actingAs($customerUser)
            ->get(route('erp.dashboard'))
            ->assertRedirect(route('portal.dashboard'));
    }

    /** @test */
    public function it_shows_sales_report(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Order::factory()->create(['order_date' => today(), 'total_amount' => 100_000]);

        $this->actingAs($admin)
            ->get(route('erp.reports.sales'))
            ->assertOk();
    }

    /** @test */
    public function it_shows_receivables_report(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Invoice::factory()->overdue()->create();

        $this->actingAs($admin)
            ->get(route('erp.reports.receivables'))
            ->assertOk();
    }

    /** @test */
    public function it_shows_stock_report(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(route('erp.reports.stock'))
            ->assertOk();
    }

    /** @test */
    public function it_shows_profit_loss_report(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Order::factory()->create(['order_date' => today(), 'total_amount' => 200_000]);
        Expense::factory()->create(['expense_date' => today(), 'amount' => 50_000]);

        $this->actingAs($admin)
            ->get(route('erp.reports.profit-loss'))
            ->assertOk();
    }
}
