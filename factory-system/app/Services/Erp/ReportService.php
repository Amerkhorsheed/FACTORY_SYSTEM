<?php

namespace App\Services\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds ERP report datasets used by controllers and exports.
 */
class ReportService
{
    public function __construct(
        private readonly ExpenseService $expenses,
    ) {}

    /**
     * Generate a customer statement for the given date range.
     *
     * @return array<string, mixed>
     */
    public function getCustomerStatement(Customer $customer, Carbon $from, Carbon $to): array
    {
        $invoices = $customer->invoices()
            ->whereBetween('issue_date', [$from, $to])
            ->with('payments')
            ->latest('issue_date')
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

    /** @return array<string, mixed> */
    public function getSalesReport(Carbon $from, Carbon $to, int $perPage = 50): array
    {
        $base = Order::whereBetween('order_date', [$from, $to]);

        return [
            'orders' => Order::with('customer')
                ->whereBetween('order_date', [$from, $to])
                ->latest('order_date')
                ->paginate($perPage)
                ->withQueryString(),
            'summary' => [
                'count' => (clone $base)->count(),
                'total' => (int) (clone $base)->sum('total_amount'),
                'delivered' => (clone $base)->where('status', 'delivered')->count(),
            ],
        ];
    }

    /** @return array{invoices: LengthAwarePaginator, totalOverdue: int} */
    public function getReceivablesReport(int $perPage = 50): array
    {
        return [
            'invoices' => Invoice::with('customer')
                ->overdue()
                ->orderBy('due_date')
                ->paginate($perPage),
            'totalOverdue' => (int) Invoice::overdue()->sum('balance_due'),
        ];
    }

    /** @return array{movements: LengthAwarePaginator, lowStock: Collection} */
    public function getStockReport(Carbon $from, Carbon $to, int $perPage = 50): array
    {
        return [
            'movements' => StockMovement::with('product')
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'lowStock' => Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->orderBy('stock_quantity')
                ->limit(20)
                ->get(),
        ];
    }

    /** @return array{revenue: int, expenses: int, profit: int} */
    public function getProfitLoss(Carbon $from, Carbon $to): array
    {
        $revenue = (int) Order::whereBetween('order_date', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('total_amount');
        $expenses = $this->expenses->getTotalForPeriod($from, $to);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $revenue - $expenses,
        ];
    }
}
