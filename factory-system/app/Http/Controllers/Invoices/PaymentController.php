<?php

namespace App\Http\Controllers\Invoices;

use App\Contracts\Services\InvoiceServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Standalone payment listing and management.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoices,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['invoice.customer', 'receivedByUser'])
            ->latest('payment_date')
            ->latest('id');

        if (! empty($request->input('customer_id'))) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if (! empty($request->input('invoice_id'))) {
            $query->where('invoice_id', $request->integer('invoice_id'));
        }

        if (! empty($request->input('payment_method'))) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if (! empty($request->input('date_from'))) {
            $query->whereDate('payment_date', '>=', $request->input('date_from'));
        }

        if (! empty($request->input('date_to'))) {
            $query->whereDate('payment_date', '<=', $request->input('date_to'));
        }

        $payments = $query->paginate(config('factory.pagination.per_page', 20))
            ->withQueryString();

        $total = (int) Payment::whereIn(
            'id',
            $query->clone()->select('id')
        )->sum('amount');

        return view('payments.index', compact('payments', 'total'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice.customer', 'receivedByUser']);

        return view('payments.show', compact('payment'));
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $invoice = $payment->invoice;
        $this->invoices->deletePayment($payment);

        return redirect()
            ->route($invoice ? 'invoices.show' : 'payments.index', $invoice ?? [])
            ->with('success', __('invoices.payment_deleted'));
    }
}
