<?php

namespace App\Services;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Stub PDF service for invoice / manifest / statement generation.
 * Full implementation (e.g. DomPDF / Browsershot) will be added in a later phase.
 */
class PdfService implements PdfServiceInterface
{
    public function generateInvoice(Invoice $invoice): string
    {
        $html = View::make('pdf.invoice', ['invoice' => $invoice])->render();

        return $this->storeHtml($html, "invoices/{$invoice->invoice_number}.html");
    }

    public function generateManifest(Shipment $shipment): string
    {
        $html = View::make('pdf.manifest', ['shipment' => $shipment])->render();

        return $this->storeHtml($html, "manifests/{$shipment->shipment_number}.html");
    }

    public function generateStatement(Customer $customer, Carbon $from, Carbon $to): string
    {
        $html = View::make('pdf.statement', [
            'customer' => $customer,
            'from' => $from,
            'to' => $to,
        ])->render();

        return $this->storeHtml($html, "statements/{$customer->id}_{$from->format('Ymd')}_{$to->format('Ymd')}.html");
    }

    /** @param array<string, mixed> $data */
    public function stream(string $view, array $data, string $filename = 'document.pdf'): Response
    {
        $html = View::make($view, $data)->render();

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function download(string $storagePath, string $filename): Response
    {
        $content = Storage::get($storagePath);

        return response($content, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function storeHtml(string $html, string $path): string
    {
        Storage::put($path, $html);

        return $path;
    }
}
