<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole('super_admin');
    }

    /** @test */
    public function it_streams_invoice_pdf_inline(): void
    {
        $invoice = $this->invoiceWithItems();

        $response = $this->actingAs($this->admin)->get(route('invoices.print', $invoice));

        $this->assertPdfResponse($response, 'inline');
        $response->assertHeader('Content-Disposition', 'inline; filename="invoice-'.$invoice->invoice_number.'.pdf"');
    }

    /** @test */
    public function it_downloads_and_persists_invoice_pdf(): void
    {
        $invoice = $this->invoiceWithItems();

        $response = $this->actingAs($this->admin)->get(route('invoices.download', $invoice));

        $this->assertPdfResponse($response, 'attachment');
        $fresh = $invoice->fresh();
        $this->assertStringStartsWith('private/pdfs/invoices/', $fresh->pdf_path);
        Storage::disk('local')->assertExists($fresh->pdf_path);
    }

    /** @test */
    public function it_downloads_and_persists_shipment_manifest_pdf(): void
    {
        $shipment = $this->shipmentWithOrders();

        $response = $this->actingAs($this->admin)->get(route('shipments.manifest', $shipment));

        $this->assertPdfResponse($response, 'attachment');
        $fresh = $shipment->fresh();
        $this->assertStringStartsWith('private/pdfs/manifests/', $fresh->manifest_path);
        Storage::disk('local')->assertExists($fresh->manifest_path);
    }

    /** @test */
    public function it_downloads_customer_statement_pdf_for_date_range(): void
    {
        $invoice = $this->invoiceWithItems(total: 150_000);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => 50_000,
            'payment_method' => 'cash',
            'payment_date' => today(),
            'received_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('customers.statement.pdf', [
            'customer' => $invoice->customer,
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ]));

        $this->assertPdfResponse($response, 'attachment');
    }

    /** @test */
    public function it_requires_authentication_for_invoice_pdf_download(): void
    {
        $invoice = $this->invoiceWithItems();

        $this->get(route('invoices.download', $invoice))
            ->assertRedirect(route('login'));
    }

    private function assertPdfResponse(TestResponse $response, string $disposition): void
    {
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith($disposition.'; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function invoiceWithItems(int $total = 120_000): Invoice
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['unit_price' => 40_000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'delivered',
            'subtotal' => $total,
            'total_amount' => $total,
            'created_by' => $this->admin->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 40_000,
            'line_total' => $total,
        ]);

        return Invoice::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'issued',
            'issue_date' => today(),
            'subtotal' => $total,
            'total_amount' => $total,
            'balance_due' => $total,
            'created_by' => $this->admin->id,
        ]);
    }

    private function shipmentWithOrders(): Shipment
    {
        $shipment = Shipment::factory()->create([
            'truck_id' => Truck::factory()->create(['status' => 'available'])->id,
            'driver_id' => Driver::factory()->create(['is_active' => true])->id,
            'created_by' => $this->admin->id,
        ]);
        $invoice = $this->invoiceWithItems();

        $invoice->order->update([
            'shipment_id' => $shipment->id,
            'status' => 'ready',
        ]);

        return $shipment->fresh();
    }
}
