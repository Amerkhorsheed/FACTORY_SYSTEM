<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdmin(): User
    {
        return User::factory()->create()->assignRole('super_admin');
    }

    /** @test */
    public function it_lists_expenses_with_pagination(): void
    {
        $admin = $this->createAdmin();
        Expense::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('erp.expenses.index'))
            ->assertOk();
    }

    /** @test */
    public function it_creates_an_expense(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('erp.expenses.store'), [
                'category' => 'fuel',
                'amount' => 50_000,
                'expense_date' => today()->toDateString(),
                'description' => 'Fuel for trucks',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'category' => 'fuel',
            'amount' => 50_000,
            'created_by' => $admin->id,
        ]);
    }

    /** @test */
    public function it_shows_an_expense(): void
    {
        $admin = $this->createAdmin();
        $expense = Expense::factory()->create();

        $this->actingAs($admin)
            ->get(route('erp.expenses.show', $expense))
            ->assertOk();
    }

    /** @test */
    public function it_updates_an_expense(): void
    {
        $admin = $this->createAdmin();
        $expense = Expense::factory()->create(['category' => 'fuel']);

        $this->actingAs($admin)
            ->put(route('erp.expenses.update', $expense), [
                'category' => 'maintenance',
                'amount' => 75_000,
                'expense_date' => today()->toDateString(),
                'description' => 'Truck maintenance',
            ])
            ->assertRedirect();

        $this->assertSame('maintenance', $expense->fresh()->category);
        $this->assertSame(75_000, $expense->fresh()->amount);
    }

    /** @test */
    public function it_deletes_an_expense(): void
    {
        $admin = $this->createAdmin();
        $expense = Expense::factory()->create();

        $this->actingAs($admin)
            ->delete(route('erp.expenses.destroy', $expense))
            ->assertRedirect();

        $this->assertSoftDeleted($expense);
    }

    /** @test */
    public function it_filters_expenses_by_category(): void
    {
        $admin = $this->createAdmin();
        Expense::factory()->create(['category' => 'fuel']);
        Expense::factory()->create(['category' => 'rent']);

        $response = $this->actingAs($admin)
            ->get(route('erp.expenses.index', ['category' => 'fuel']));

        $response->assertOk();
    }

    /** @test */
    public function it_blocks_unauthorized_expense_access(): void
    {
        $regularUser = User::factory()->create()->assignRole('customer');
        $expense = Expense::factory()->create();

        $this->actingAs($regularUser)
            ->get(route('erp.expenses.index'))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($regularUser)
            ->get(route('erp.expenses.show', $expense))
            ->assertRedirect(route('portal.dashboard'));
    }
}
