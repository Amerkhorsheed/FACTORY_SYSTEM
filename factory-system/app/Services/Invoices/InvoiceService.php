<?php

namespace App\Services\Invoices;

use App\Contracts\Services\InvoiceServiceInterface;
use App\DTOs\Invoices\RecordPaymentDTO;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

/**
 * Invoice service — minimal stub for order lifecycle support.
 * Full invoicing module will be implemented in Phase 07 Module 06.
 */
class InvoiceService implements InvoiceServiceInterface
{
    public function createFromOrder(Order $order): Invoice
    {
        return Invoice::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => 'sale',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_rate' => 0,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'paid_amount' => 0,
            'balance_due' => $order->total_amount,
            'created_by' => $order->created_by,
        ]);
    }

    public function issue(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'issued']);

        return $invoice->fresh();
    }

    public function void(Invoice $invoice, string $reason): Invoice
    {
        $invoice->update([
            'status' => 'void',
            'void_reason' => $reason,
            'voided_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function recordPayment(RecordPaymentDTO $dto): Payment
    {
        $payment = Payment::create([
            'invoice_id' => $dto->invoiceId,
            'amount' => $dto->amount,
            'method' => $dto->method,
            'reference' => $dto->reference,
            'notes' => $dto->notes,
            'payment_date' => now(),
        ]);

        $invoice = Invoice::find($dto->invoiceId);
        if ($invoice) {
            $newPaid = $invoice->paid_amount + $dto->amount;
            $invoice->update([
                'paid_amount' => $newPaid,
                'balance_due' => max(0, $invoice->total_amount - $newPaid),
                'status' => $newPaid >= $invoice->total_amount ? 'paid' : 'issued',
            ]);
        }

        return $payment;
    }

    public function deletePayment(Payment $payment): void
    {
        $invoice = $payment->invoice;
        $payment->delete();

        if ($invoice) {
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->update([
                'paid_amount' => $totalPaid,
                'balance_due' => max(0, $invoice->total_amount - $totalPaid),
                'status' => $totalPaid >= $invoice->total_amount ? 'paid' : 'issued',
            ]);
        }
    }
}
