<?php

namespace App\Services\Invoices;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Services\CustomerServiceInterface;
use App\Contracts\Services\InvoiceServiceInterface;
use App\DTOs\Invoices\RecordPaymentDTO;
use App\Events\InvoiceIssued;
use App\Events\PaymentReceived;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Production invoice service with transaction-safe payments and balance tracking.
 */
class InvoiceService extends BaseService implements InvoiceServiceInterface
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly CustomerServiceInterface $customers,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->invoices->paginateWithFilters($filters, $perPage ?: 20);
    }

    public function createFromOrder(Order $order): Invoice
    {
        return $this->transaction(function () use ($order) {
            $invoice = $this->invoices->create([
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

            return $invoice->fresh();
        });
    }

    public function syncFromOrder(Order $order): ?Invoice
    {
        return $this->transaction(function () use ($order) {
            $order->loadMissing('invoice');

            if (! $order->invoice) {
                return null;
            }

            $invoice = $order->invoice;
            if ($invoice->paid_amount > 0 || in_array($invoice->status, ['paid', 'void'], true)) {
                throw new \DomainException(__('invoices.cannot_sync_paid_order'));
            }

            $oldCustomer = $invoice->customer;
            $updated = $this->invoices->update($invoice, [
                'customer_id' => $order->customer_id,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'paid_amount' => 0,
                'balance_due' => $order->total_amount,
            ]);

            if ($oldCustomer) {
                $this->customers->recalculateBalance($oldCustomer);
            }

            if ($updated->customer_id !== $oldCustomer?->id) {
                $this->customers->recalculateBalance($updated->customer);
            }

            return $updated->fresh();
        });
    }

    public function issue(Invoice $invoice): Invoice
    {
        return $this->transaction(function () use ($invoice) {
            $issued = $this->invoices->update($invoice, ['status' => 'issued']);

            event(new InvoiceIssued($issued));

            return $issued->fresh();
        });
    }

    public function void(Invoice $invoice, string $reason): Invoice
    {
        return $this->transaction(function () use ($invoice, $reason) {
            if (! $invoice->canBeVoided()) {
                throw new \DomainException(__('invoices.cannot_void'));
            }

            $this->invoices->update($invoice, [
                'status' => 'void',
                'void_reason' => $reason,
                'voided_at' => now(),
                'balance_due' => 0,
            ]);

            $this->customers->recalculateBalance($invoice->customer);

            return $invoice->fresh();
        });
    }

    public function recordPayment(RecordPaymentDTO $dto): Payment
    {
        return $this->transaction(function () use ($dto) {
            $invoice = $this->invoices->findByIdOrFail($dto->invoiceId);

            if (in_array($invoice->status, ['paid', 'void', 'draft'], true)) {
                throw new \DomainException(__('invoices.cannot_record_payment'));
            }

            if ($dto->amount > $invoice->balance_due) {
                throw new \DomainException(__('invoices.payment_exceeds_balance'));
            }

            $payment = Payment::create([
                'invoice_id' => $dto->invoiceId,
                'customer_id' => $dto->customerId,
                'amount' => $dto->amount,
                'payment_method' => $dto->method,
                'payment_date' => $dto->paymentDate,
                'reference_number' => $dto->reference,
                'notes' => $dto->notes,
                'received_by' => $dto->receivedBy ?? auth()->id(),
            ]);

            $newPaid = $invoice->paid_amount + $dto->amount;
            $newBalance = max(0, $invoice->total_amount - $newPaid);
            $newStatus = $newBalance === 0 ? 'paid' : 'partial';

            $this->invoices->update($invoice, [
                'paid_amount' => $newPaid,
                'balance_due' => $newBalance,
                'status' => $newStatus,
            ]);

            $this->customers->recalculateBalance($invoice->customer);

            event(new PaymentReceived($payment->fresh()));

            return $payment->fresh();
        });
    }

    public function deletePayment(Payment $payment, ?Invoice $invoice = null): void
    {
        $this->transaction(function () use ($payment, $invoice) {
            if ($invoice && $payment->invoice_id !== $invoice->id) {
                throw new \DomainException(__('invoices.payment_not_found'));
            }

            $invoice = $payment->invoice;

            $payment->delete();

            if ($invoice) {
                $totalPaid = (int) $invoice->payments()->sum('amount');
                $balance = max(0, $invoice->total_amount - $totalPaid);
                $status = match (true) {
                    $balance === 0 => 'paid',
                    $totalPaid === 0 => 'issued',
                    default => 'partial',
                };

                $this->invoices->update($invoice, [
                    'paid_amount' => $totalPaid,
                    'balance_due' => $balance,
                    'status' => $status,
                ]);

                $this->customers->recalculateBalance($invoice->customer);
            }
        });
    }
}
