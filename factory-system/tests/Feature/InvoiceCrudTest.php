<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Invoices\InvoiceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
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

    private function createDraftInvoice(): Invoice
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        return Invoice::factory()->draft()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'balance_due' => 100_000,
            'paid_amount' => 0,
        ]);
    }

    /** @test */
    public function it_lists_invoices_with_pagination(): void
    {
        $admin = $this->createAdmin();
        Invoice::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('invoices.index'))
            ->assertOk();
    }

    /** @test */
    public function it_shows_an_invoice(): void
    {
        $admin = $this->createAdmin();
        $invoice = $this->createDraftInvoice();

        $this->actingAs($admin)
            ->get(route('invoices.show', $invoice))
            ->assertOk();
    }

    /** @test */
    public function it_issues_a_draft_invoice(): void
    {
        $admin = $this->createAdmin();
        $invoice = $this->createDraftInvoice();

        $this->actingAs($admin)
            ->post(route('invoices.issue', $invoice))
            ->assertRedirect();

        $this->assertSame('issued', $invoice->fresh()->status);
    }

    /** @test */
    public function it_records_a_payment_and_updates_invoice(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->issued()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'balance_due' => 100_000,
            'paid_amount' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => 50_000,
                'payment_method' => 'cash',
                'payment_date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(50_000, $invoice->paid_amount);
        $this->assertSame(50_000, $invoice->balance_due);
        $this->assertSame('partial', $invoice->status);
    }

    /** @test */
    public function it_marks_invoice_paid_when_fully_paid(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->issued()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'balance_due' => 100_000,
            'paid_amount' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => 100_000,
                'payment_method' => 'cash',
                'payment_date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance_due);
    }

    /** @test */
    public function it_blocks_payment_exceeding_balance(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->issued()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'balance_due' => 100_000,
            'paid_amount' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => 150_000,
                'payment_method' => 'cash',
                'payment_date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(0, $invoice->fresh()->paid_amount);
    }

    /** @test */
    public function it_deletes_a_payment_and_recalculates_invoice(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->issued()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'balance_due' => 100_000,
            'paid_amount' => 0,
        ]);
        $payment = Payment::factory()->forInvoice($invoice)->create([
            'amount' => 40_000,
            'customer_id' => $customer->id,
            'received_by' => $admin->id,
        ]);
        $invoice->update(['paid_amount' => 40_000, 'balance_due' => 60_000, 'status' => 'partial']);

        $this->actingAs($admin)
            ->delete(route('invoices.payments.destroy', [$invoice, $payment]))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(100_000, $invoice->balance_due);
        $this->assertSame('issued', $invoice->status);
    }

    /** @test */
    public function it_does_not_delete_a_payment_from_a_different_invoice(): void
    {
        $admin = $this->createAdmin();
        $firstCustomer = Customer::factory()->create();
        $secondCustomer = Customer::factory()->create();
        $firstOrder = Order::factory()->create(['customer_id' => $firstCustomer->id]);
        $secondOrder = Order::factory()->create(['customer_id' => $secondCustomer->id]);
        $firstInvoice = Invoice::factory()->issued()->create([
            'order_id' => $firstOrder->id,
            'customer_id' => $firstCustomer->id,
        ]);
        $secondInvoice = Invoice::factory()->issued()->create([
            'order_id' => $secondOrder->id,
            'customer_id' => $secondCustomer->id,
        ]);
        $foreignPayment = Payment::factory()->forInvoice($secondInvoice)->create([
            'customer_id' => $secondCustomer->id,
            'received_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('invoices.payments.destroy', [$firstInvoice, $foreignPayment]))
            ->assertNotFound();

        $this->assertDatabaseHas('payments', ['id' => $foreignPayment->id]);
    }

    /** @test */
    public function it_voids_an_invoice_with_no_payments(): void
    {
        $admin = $this->createAdmin();
        $invoice = $this->createDraftInvoice();

        $this->actingAs($admin)
            ->post(route('invoices.void', $invoice), ['reason' => 'test'])
            ->assertRedirect();

        $this->assertSame('void', $invoice->fresh()->status);
    }

    /** @test */
    public function it_blocks_voiding_invoice_with_payments(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->paid()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.void', $invoice))
            ->assertForbidden();

        $this->assertNotSame('void', $invoice->fresh()->status);
    }

    /** @test */
    public function it_blocks_unauthorized_invoice_access(): void
    {
        $regularUser = User::factory()->create()->assignRole('customer');
        $invoice = $this->createDraftInvoice();

        $this->actingAs($regularUser)
            ->get(route('invoices.index'))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($regularUser)
            ->get(route('invoices.show', $invoice))
            ->assertRedirect(route('portal.dashboard'));
    }

    /** @test */
    public function it_creates_invoice_from_order_via_service(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'subtotal' => 100_000,
            'total_amount' => 100_000,
            'created_by' => $admin->id,
        ]);

        $invoice = app(InvoiceService::class)->createFromOrder($order);

        $this->assertSame($order->id, $invoice->order_id);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame(100_000, $invoice->total_amount);
    }
}
