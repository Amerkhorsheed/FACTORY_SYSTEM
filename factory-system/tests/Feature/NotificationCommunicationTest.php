<?php

namespace Tests\Feature;

use App\DTOs\Invoices\RecordPaymentDTO;
use App\Events\Orders\OrderAccepted;
use App\Livewire\NotificationBell;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\InvoiceIssued as InvoiceIssuedNotification;
use App\Notifications\InvoiceOverdue;
use App\Notifications\LowStockAlert;
use App\Notifications\OrderStatusChanged;
use App\Notifications\PaymentReceived as PaymentReceivedNotification;
use App\Services\Invoices\InvoiceService;
use App\Services\Products\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_notifies_the_customer_when_an_order_is_accepted(): void
    {
        Notification::fake();

        [$customerUser, $customer] = $this->portalCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'accepted',
        ]);

        event(new OrderAccepted($order));

        Notification::assertSentTo(
            $customerUser,
            OrderStatusChanged::class,
            fn (OrderStatusChanged $notification, array $channels): bool => in_array('database', $channels, true) && in_array('mail', $channels, true)
        );
    }

    /** @test */
    public function it_notifies_the_customer_when_an_invoice_is_issued(): void
    {
        Notification::fake();

        [$customerUser, $customer] = $this->portalCustomer();
        $invoice = Invoice::factory()->draft()->create([
            'customer_id' => $customer->id,
            'total_amount' => 250_000,
            'balance_due' => 250_000,
        ]);

        app(InvoiceService::class)->issue($invoice);

        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertSame(250_000, $customer->fresh()->outstanding_balance);
        Notification::assertSentTo($customerUser, InvoiceIssuedNotification::class);
    }

    /** @test */
    public function it_notifies_the_customer_when_a_payment_is_recorded(): void
    {
        Notification::fake();

        [$customerUser, $customer] = $this->portalCustomer();
        $accountant = User::factory()->create()->assignRole('accountant');
        $invoice = Invoice::factory()->issued()->create([
            'customer_id' => $customer->id,
            'total_amount' => 300_000,
            'paid_amount' => 0,
            'balance_due' => 300_000,
        ]);

        app(InvoiceService::class)->recordPayment(new RecordPaymentDTO(
            invoiceId: $invoice->id,
            customerId: $customer->id,
            amount: 125_000,
            method: 'cash',
            paymentDate: now()->toDateString(),
            receivedBy: $accountant->id,
        ));

        $this->assertSame(175_000, $invoice->fresh()->balance_due);
        Notification::assertSentTo($customerUser, PaymentReceivedNotification::class);
    }

    /** @test */
    public function it_alerts_accounting_staff_when_stock_crosses_the_low_threshold(): void
    {
        Notification::fake();

        $admin = User::factory()->create()->assignRole('super_admin');
        $accountant = User::factory()->create()->assignRole('accountant');
        $shipping = User::factory()->create()->assignRole('shipping_staff');
        $product = Product::factory()->create([
            'created_by' => $admin->id,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
        ]);

        $this->actingAs($admin);
        app(StockService::class)->moveStock($product, 'out', 5);

        Notification::assertSentTo($admin, LowStockAlert::class);
        Notification::assertSentTo($accountant, LowStockAlert::class);
        Notification::assertNotSentTo($shipping, LowStockAlert::class);
    }

    /** @test */
    public function it_sends_scheduled_overdue_and_low_stock_digests(): void
    {
        Notification::fake();

        $admin = User::factory()->create()->assignRole('super_admin');
        $accountant = User::factory()->create()->assignRole('accountant');
        [, $customer] = $this->portalCustomer();

        Invoice::factory()->overdue()->create([
            'customer_id' => $customer->id,
            'balance_due' => 90_000,
        ]);
        Product::factory()->create([
            'created_by' => $admin->id,
            'stock_quantity' => 3,
            'low_stock_threshold' => 5,
        ]);

        $this->assertSame(0, Artisan::call('factory:overdue-alerts'));
        $this->assertSame(0, Artisan::call('factory:low-stock-check'));

        Notification::assertSentTo($admin, InvoiceOverdue::class);
        Notification::assertSentTo($accountant, InvoiceOverdue::class);
        Notification::assertSentTo($admin, LowStockAlert::class);
        Notification::assertSentTo($accountant, LowStockAlert::class);
    }

    /** @test */
    public function notification_bell_loads_and_marks_notifications_as_read(): void
    {
        [$customerUser, $customer] = $this->portalCustomer();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $notification = $customerUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => [
                'message' => 'Test alert',
                'url' => route('portal.orders.show', $order),
            ],
        ]);

        Livewire::actingAs($customerUser)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 1)
            ->call('markAsRead', $notification->id)
            ->assertSet('unreadCount', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @return array{0:User,1:Customer} */
    private function portalCustomer(): array
    {
        $user = User::factory()->create()->assignRole('customer');
        $customer = Customer::factory()->create([
            'email' => $user->email,
            'portal_access' => true,
            'user_id' => $user->id,
        ]);

        return [$user, $customer];
    }
}
