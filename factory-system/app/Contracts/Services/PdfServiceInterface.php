<?php

namespace App\Contracts\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Response;

interface PdfServiceInterface
{
    public function generateInvoice(Invoice $invoice): string;

    public function generateManifest(Shipment $shipment): string;

    public function generateStatement(Customer $customer, Carbon $from, Carbon $to): string;

    public function streamInvoice(Invoice $invoice): Response;

    public function downloadInvoice(Invoice $invoice): Response;

    public function downloadManifest(Shipment $shipment): Response;

    public function downloadStatement(Customer $customer, Carbon $from, Carbon $to): Response;

    /** @param array<string, mixed> $data */
    public function stream(string $view, array $data, string $filename = 'document.pdf'): Response;

    public function download(string $storagePath, string $filename): Response;
}
