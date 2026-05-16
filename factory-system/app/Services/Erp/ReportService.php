<?php

namespace App\Services\Erp;

use App\Models\Customer;
use Carbon\Carbon;

/**
 * Minimal ERP report service stub.
 * Full report engine will be implemented in a later phase.
 */
class ReportService
{
    /**
     * Generate a customer statement for the given date range.
     *
     * @return array<string, mixed>
     */
    public function getCustomerStatement(Customer $customer, Carbon $from, Carbon $to): array
    {
        $invoices = $customer->invoices()
            ->whereBetween('invoice_date', [$from, $to])
            ->with('payments')
            ->latest('invoice_date')
            ->get();

        $payments = $customer->payments()
            ->whereBetween('payment_date', [$from, $to])
            ->latest('payment_date')
            ->get();

        return [
            'customer' => $customer,
            'invoices' => $invoices,
            'payments' => $payments,
            'from' => $from,
            'to' => $to,
            'opening_balance' => 0,
            'closing_balance' => $customer->outstanding_balance,
        ];
    }
}
