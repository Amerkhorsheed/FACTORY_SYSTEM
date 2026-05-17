<?php

namespace App\Services;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Support\ArabicAmount;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Generates official Arabic RTL PDFs and stores them under private storage.
 */
class PdfService implements PdfServiceInterface
{
    public function __construct(private readonly SettingService $settings) {}

    public function generateInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'order.items.product', 'payments.receivedByUser']);

        $path = $this->storagePath('invoices', $invoice->invoice_number.'.pdf');
        $this->store($this->invoicePdf($invoice), $path);

        DB::transaction(fn () => $invoice->forceFill(['pdf_path' => $path])->save());

        return $path;
    }

    public function generateManifest(Shipment $shipment): string
    {
        $shipment->loadMissing(['truck', 'driver', 'orders.customer', 'orders.items.product']);

        $path = $this->storagePath('manifests', $shipment->shipment_number.'.pdf');
        $this->store($this->manifestPdf($shipment), $path);

        DB::transaction(fn () => $shipment->forceFill(['manifest_path' => $path])->save());

        return $path;
    }

    public function generateStatement(Customer $customer, Carbon $from, Carbon $to): string
    {
        $statement = $this->statementData($customer, $from, $to);
        $path = $this->storagePath(
            'statements',
            $customer->code.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf'
        );

        $this->store($this->statementPdf($customer, $statement, $from, $to), $path);

        return $path;
    }

    public function streamInvoice(Invoice $invoice): Response
    {
        $invoice->loadMissing(['customer', 'order.items.product', 'payments.receivedByUser']);

        return $this->inline($this->invoicePdf($invoice), 'invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function downloadInvoice(Invoice $invoice): Response
    {
        $path = $this->storedPath($invoice->pdf_path) ?: $this->generateInvoice($invoice);

        return $this->download($path, 'invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function downloadManifest(Shipment $shipment): Response
    {
        $path = $this->storedPath($shipment->manifest_path) ?: $this->generateManifest($shipment);

        return $this->download($path, 'manifest-'.$shipment->shipment_number.'.pdf');
    }

    public function downloadStatement(Customer $customer, Carbon $from, Carbon $to): Response
    {
        $path = $this->generateStatement($customer, $from, $to);

        return $this->download($path, 'statement-'.$customer->code.'-'.$from->format('Ymd').'.pdf');
    }

    /** @param array<string, mixed> $data */
    public function stream(string $view, array $data, string $filename = 'document.pdf'): Response
    {
        return $this->inline($this->build($view, $data), $filename);
    }

    public function download(string $storagePath, string $filename): Response
    {
        abort_unless(Storage::exists($storagePath), 404, 'PDF file not found.');

        return response(Storage::get($storagePath), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function invoicePdf(Invoice $invoice): DomPdf
    {
        return $this->build('pdf.invoice', [
            'invoice' => $invoice,
            'settings' => $this->documentSettings(),
            'amountWords' => ArabicAmount::toSyp((int) $invoice->total_amount),
        ]);
    }

    private function manifestPdf(Shipment $shipment): DomPdf
    {
        return $this->build('pdf.shipment-manifest', [
            'shipment' => $shipment,
            'settings' => $this->documentSettings(),
        ]);
    }

    /** @param array<string, mixed> $statement */
    private function statementPdf(Customer $customer, array $statement, Carbon $from, Carbon $to): DomPdf
    {
        return $this->build('pdf.customer-statement', [
            'customer' => $customer,
            'statement' => $statement,
            'from' => $from,
            'to' => $to,
            'settings' => $this->documentSettings(),
        ]);
    }

    private function build(string $view, array $data): DomPdf
    {
        return Pdf::loadView($view, $data)
            ->setPaper(config('pdf.paper_size', 'a4'), config('pdf.orientation', 'portrait'))
            ->setOption('defaultFont', config('pdf.default_font', 'dejavu sans'))
            ->setOption('isRemoteEnabled', config('pdf.remote_enabled', true))
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', config('pdf.font_subsetting', true))
            ->setOption('dpi', config('pdf.dpi', 150));
    }

    private function inline(DomPdf $pdf, string $filename): Response
    {
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function store(DomPdf $pdf, string $path): void
    {
        Storage::makeDirectory(dirname($path));
        Storage::put($path, $pdf->output());
    }

    private function storagePath(string $type, string $filename): string
    {
        $root = trim(config('pdf.storage_path', 'private/pdfs'), '/');
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?: 'document.pdf';

        return $root.'/'.$type.'/'.$safeFilename;
    }

    private function storedPath(?string $path): ?string
    {
        return $path && Storage::exists($path) ? $path : null;
    }

    /** @return array<string, mixed> */
    private function documentSettings(): array
    {
        $settings = $this->settings->all();

        return [
            'factory_name' => $settings['factory_name'] ?? config('factory.name'),
            'factory_address' => $settings['factory_address'] ?? '',
            'factory_phone' => $settings['factory_phone'] ?? '',
            'factory_tax_number' => $settings['factory_tax_number'] ?? '',
            'invoice_footer_text' => $settings['invoice_footer_text'] ?? '',
            'invoice_terms' => $settings['invoice_terms'] ?? '',
            'currency_label' => __('ui.currency.syp'),
            'generated_at' => now()->format('Y-m-d H:i'),
        ];
    }

    /** @return array<string, mixed> */
    private function statementData(Customer $customer, Carbon $from, Carbon $to): array
    {
        $openingBalance = (int) $customer->invoices()
            ->where('issue_date', '<', $from)
            ->where('status', '!=', 'void')
            ->sum('total_amount')
            - (int) $customer->payments()->where('payment_date', '<', $from)->sum('amount');

        $invoices = $customer->invoices()
            ->whereBetween('issue_date', [$from, $to])
            ->where('status', '!=', 'void')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date' => $invoice->issue_date,
                'reference' => $invoice->invoice_number,
                'description' => __('pdf.statement.invoice_description'),
                'debit' => (int) $invoice->total_amount,
                'credit' => 0,
                'sort' => '1-'.$invoice->id,
            ]);

        $payments = $customer->payments()
            ->whereBetween('payment_date', [$from, $to])
            ->get()
            ->map(fn ($payment) => [
                'date' => $payment->payment_date,
                'reference' => $payment->payment_number ?? $payment->reference_number,
                'description' => __('pdf.statement.payment_description').' - '.$payment->method_label,
                'debit' => 0,
                'credit' => (int) $payment->amount,
                'sort' => '2-'.$payment->id,
            ]);

        $balance = $openingBalance;
        $transactions = $invoices->merge($payments)
            ->sortBy(fn (array $row) => $row['date']->format('Ymd').'-'.$row['sort'])
            ->values()
            ->map(function (array $row) use (&$balance): array {
                $balance += $row['debit'] - $row['credit'];
                $row['balance'] = $balance;

                return $row;
            });

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $balance,
            'total_debit' => (int) $transactions->sum('debit'),
            'total_credit' => (int) $transactions->sum('credit'),
            'transactions' => $transactions,
        ];
    }
}
