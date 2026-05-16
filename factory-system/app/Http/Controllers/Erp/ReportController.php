<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Erp\ExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ERP report generation controllers.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenses,
    ) {}

    public function sales(Request $request): View
    {
        $this->authorize('erp.reports.view');

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));

        $orders = Order::with('customer')
            ->whereBetween('order_date', [$from, $to])
            ->latest('order_date')
            ->paginate(50)
            ->withQueryString();

        $summary = [
            'count' => Order::whereBetween('order_date', [$from, $to])->count(),
            'total' => (int) Order::whereBetween('order_date', [$from, $to])->sum('total_amount'),
            'delivered' => Order::whereBetween('order_date', [$from, $to])->where('status', 'delivered')->count(),
        ];

        return view('erp.reports.sales', compact('orders', 'summary', 'from', 'to'));
    }

    public function receivables(): View
    {
        $this->authorize('erp.reports.view');

        $invoices = Invoice::with('customer')
            ->overdue()
            ->orderBy('due_date')
            ->paginate(50);

        $totalOverdue = (int) Invoice::overdue()->sum('balance_due');

        return view('erp.reports.receivables', compact('invoices', 'totalOverdue'));
    }

    public function stock(Request $request): View
    {
        $this->authorize('erp.reports.view');

        $from = Carbon::parse($request->get('from', now()->subDays(30)));
        $to = Carbon::parse($request->get('to', now()));

        $movements = StockMovement::with('product')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $lowStock = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(20)
            ->get();

        return view('erp.reports.stock', compact('movements', 'lowStock', 'from', 'to'));
    }

    public function profitLoss(Request $request): View
    {
        $this->authorize('erp.reports.view');

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));

        $revenue = (int) Order::whereBetween('order_date', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('total_amount');

        $expenses = $this->expenses->getTotalForPeriod($from, $to);

        $profit = $revenue - $expenses;

        return view('erp.reports.profit-loss', compact('revenue', 'expenses', 'profit', 'from', 'to'));
    }
}
