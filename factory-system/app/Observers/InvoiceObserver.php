<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\PdfService;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        activity('invoices')
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->withProperties([
                'number' => $invoice->invoice_number,
                'total' => $invoice->total_amount,
                'status' => $invoice->status,
            ])
            ->log(__('activity.invoices.created'));
    }

    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getChanges();

        if (array_key_exists('status', $changes)) {
            activity('invoices')
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->withProperties([
                    'from' => $invoice->getOriginal('status'),
                    'to' => $invoice->status,
                ])
                ->log(__('activity.invoices.status_changed'));
        }

        if ($this->requiresPdfRegeneration($changes) && class_exists(PdfService::class)) {
            dispatch(fn () => app(PdfService::class)->generateInvoice($invoice))->afterResponse();
        }
    }

    /** @param array<string, mixed> $changes */
    private function requiresPdfRegeneration(array $changes): bool
    {
        return array_intersect(array_keys($changes), ['total_amount', 'paid_amount', 'status']) !== [];
    }
}
