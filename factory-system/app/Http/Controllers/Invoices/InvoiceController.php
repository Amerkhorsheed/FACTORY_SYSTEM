<?php

namespace App\Http\Controllers\Invoices;

use App\Contracts\Services\InvoiceServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\DTOs\Invoices\RecordPaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Invoice display, voiding, and payment management.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoices,
        private readonly PdfServiceInterface $pdfService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->invoices->list($request->only([
            'search', 'status', 'customer_id', 'date_from', 'date_to', 'overdue',
        ]));

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['order', 'customer', 'payments.receivedByUser']);

        return view('invoices.show', compact('invoice'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        try {
            $this->invoices->void($invoice, __('invoices.voided_manually'));
        } catch (\DomainException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', __('invoices.voided'));
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->authorize('issue', $invoice);

        $this->invoices->issue($invoice);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', __('invoices.issued'));
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $reason = $request->input('reason', __('invoices.voided_manually'));

        try {
            $this->invoices->void($invoice, $reason);
        } catch (\DomainException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', __('invoices.voided'));
    }

    public function recordPayment(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('recordPayment', $invoice);

        try {
            $dto = new RecordPaymentDTO(
                invoiceId: $invoice->id,
                customerId: $invoice->customer_id,
                amount: $request->integer('amount'),
                method: $request->input('payment_method'),
                paymentDate: $request->input('payment_date'),
                reference: $request->input('reference_number'),
                notes: $request->input('notes'),
                receivedBy: auth()->id(),
            );

            $this->invoices->recordPayment($dto);
        } catch (\DomainException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', __('invoices.payment_recorded'));
    }

    public function deletePayment(Invoice $invoice, Payment $payment): RedirectResponse
    {
        $this->authorize('deletePayment', $invoice);

        try {
            $this->invoices->deletePayment($payment, $invoice);
        } catch (\DomainException) {
            abort(404);
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', __('invoices.payment_deleted'));
    }

    public function print(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return $this->pdfService->streamInvoice($invoice);
    }

    public function download(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return $this->pdfService->downloadInvoice($invoice);
    }
}
