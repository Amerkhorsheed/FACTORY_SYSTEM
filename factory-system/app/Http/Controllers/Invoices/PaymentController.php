<?php

namespace App\Http\Controllers\Invoices;

use App\Contracts\Services\InvoiceServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Invoices\PaymentService;
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
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $request->only([
            'customer_id', 'invoice_id', 'payment_method', 'date_from', 'date_to',
        ]);
        $payments = $this->payments->list($filters);
        $total = $this->payments->total($filters);

        return view('payments.index', compact('payments', 'total'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $this->payments->loadDetails($payment);

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
