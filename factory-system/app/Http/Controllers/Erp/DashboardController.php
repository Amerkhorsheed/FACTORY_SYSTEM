<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Services\Erp\ExpenseService;
use Illuminate\View\View;

/**
 * ERP dashboard with KPI aggregation.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenses,
    ) {}

    public function index(): View
    {
        $this->authorize('erp.dashboard.view');

        $today = today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        $kpis = [
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

        $recentOrders = Order::with('customer')
            ->latest('order_date')
            ->limit(10)
            ->get();

        $recentInvoices = Invoice::with('customer')
            ->latest('issue_date')
            ->limit(10)
            ->get();

        return view('erp.dashboard', compact('kpis', 'recentOrders', 'recentInvoices'));
    }
}
