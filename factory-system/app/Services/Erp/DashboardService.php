<?php

namespace App\Services\Erp;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aggregates ERP dashboard metrics and recent activity.
 */
class DashboardService
{
    public function __construct(
        private readonly ExpenseService $expenses,
    ) {}

    /** @return array<string, int> */
    public function kpis(): array
    {
        $today = today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        return [
            'today_orders' => Order::whereDate('order_date', $today)->count(),
            'today_sales' => (int) Order::whereDate('order_date', $today)->sum('total_amount'),
            'month_sales' => (int) Order::whereBetween('order_date', [$startOfMonth, $endOfMonth])->sum('total_amount'),
            'active_shipments' => Shipment::active()->count(),
            'overdue_invoices' => Invoice::overdue()->count(),
            'overdue_amount' => (int) Invoice::overdue()->sum('balance_due'),
            'low_stock_count' => Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'today_expenses' => $this->expenses->getTotalForPeriod($today, $today),
            'pending_orders' => Order::pending()->count(),
            'ready_orders' => Order::byStatus('ready')->count(),
        ];
    }

    /** @return Collection<int, Order> */
    public function recentOrders(int $limit = 10): Collection
    {
        return Order::with('customer')
            ->latest('order_date')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Invoice> */
    public function recentInvoices(int $limit = 10): Collection
    {
        return Invoice::with('customer')
            ->latest('issue_date')
            ->limit($limit)
            ->get();
    }
}
