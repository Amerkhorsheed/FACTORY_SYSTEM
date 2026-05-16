<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        RateLimiter::clear('login:127.0.0.1');
    }

    /**
     * @test
     */
    public function it_shows_login_page_in_arabic(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('تسجيل الدخول');
    }

    /**
     * @test
     */
    public function it_logs_in_with_valid_email_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')])->assignRole('super_admin');

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('erp.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_logs_in_with_valid_phone_credentials(): void
    {
        $user = User::factory()->create([
            'phone' => '0912345678',
            'password' => bcrypt('password'),
        ])->assignRole('super_admin');

        $this->post(route('login'), ['login' => '0912345678', 'password' => 'password'])
            ->assertRedirect(route('erp.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_blocks_login_with_wrong_password(): void
    {
        $user = User::factory()->create()->assignRole('super_admin');

        $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_blocks_inactive_user_login(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'password' => bcrypt('password'),
        ])->assignRole('super_admin');

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertStringContainsString(
            __('auth.account_deactivated'),
            session('errors')->first('login')
        );
    }

    /**
     * @test
     */
    public function it_redirects_customers_to_portal_after_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')])->assignRole('customer');

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_rate_limits_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('login');

        $this->assertStringContainsString('دقيقة', session('errors')->first('login'));
    }

    /**
     * @test
     */
    public function it_updates_last_login_at_on_successful_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')])->assignRole('accountant');

        $this->post(route('login'), ['login' => $user->email, 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    /**
     * @test
     */
    public function it_blocks_shipping_staff_from_erp_routes(): void
    {
        $staff = User::factory()->create(['password' => bcrypt('password')])->assignRole('shipping_staff');

        $this->actingAs($staff)
            ->get(route('erp.expenses.index'))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function it_redirects_customer_away_from_admin_routes(): void
    {
        $customer = User::factory()->create(['password' => bcrypt('password')])->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('products.index'))
            ->assertRedirect(route('portal.dashboard'));
    }

    /**
     * @test
     */
    public function it_logs_out_authenticated_user(): void
    {
        $user = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_blocks_deactivated_user_from_authenticated_routes(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ])->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
