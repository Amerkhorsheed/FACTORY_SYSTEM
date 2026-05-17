<?php

namespace App\Services;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Production PDF service using DomPDF.
 * Generates Arabic RTL PDFs for invoices, manifests, and statements.
 */
class PdfService implements PdfServiceInterface
{
    /**
     * Generate an invoice PDF and store it.
     */
    public function generateInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'order.items.product']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'settings' => config('factory.settings', []),
        ])->setPaper(config('pdf.paper_size', 'a4'), 'portrait');

        $path = "invoices/{$invoice->invoice_number}.pdf";
        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate a shipment manifest PDF and store it.
     */
    public function generateManifest(Shipment $shipment): string
    {
        $shipment->loadMissing(['truck', 'driver', 'orders.customer']);

        $pdf = Pdf::loadView('pdf.shipment-manifest', [
            'shipment' => $shipment,
        ])->setPaper(config('pdf.paper_size', 'a4'), 'portrait');

        $path = "manifests/{$shipment->shipment_number}.pdf";
        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate a customer statement PDF and store it.
     */
    public function generateStatement(Customer $customer, Carbon $from, Carbon $to): string
    {
        // Example implementation for fetching statement transactions.
        // In a real application, this might query an accounting ledger or invoices/payments.
        $invoices = $customer->invoices()
            ->whereBetween('issue_date', [$from, $to])
            ->whereNotIn('status', ['void'])
            ->get()
            ->map(fn ($inv) => [
                'date' => $inv->issue_date?->format('Y-m-d'),
                'type' => 'فاتورة مبيعات',
                'reference' => $inv->invoice_number,
                'debit' => $inv->total_amount,
                'credit' => 0,
            ]);

        $payments = $customer->invoices()
            ->with('payments')
            ->get()
            ->pluck('payments')
            ->flatten()
            ->whereBetween('payment_date', [$from, $to])
            ->map(fn ($pay) => [
                'date' => $pay->payment_date->format('Y-m-d'),
                'type' => 'دفعة مستلمة',
                'reference' => $pay->transaction_id,
                'debit' => 0,
                'credit' => $pay->amount,
            ]);

        $transactions = collect($invoices)
            ->merge($payments)
            ->sortBy('date')
            ->values();

        $pdf = Pdf::loadView('pdf.customer-statement', [
            'customer' => $customer,
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
            'openingBalance' => 0, // This would ideally be calculated from prior dates
            'transactions' => $transactions,
        ])->setPaper(config('pdf.paper_size', 'a4'), 'portrait');

        $path = "statements/{$customer->customer_code}_{$from->format('Ymd')}_{$to->format('Ymd')}.pdf";
        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Stream a dynamically generated PDF to the browser.
     *
     * @param  array<string, mixed>  $data
     */
    public function stream(string $view, array $data, string $filename = 'document.pdf'): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper(config('pdf.paper_size', 'a4'), 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download an existing PDF from storage.
     */
    public function download(string $storagePath, string $filename): Response
    {
        abort_unless(Storage::exists($storagePath), 404, 'PDF file not found.');

        $content = Storage::get($storagePath);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
