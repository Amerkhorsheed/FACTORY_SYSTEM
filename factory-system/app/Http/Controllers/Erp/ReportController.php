<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\Erp\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ERP report generation controllers.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function sales(Request $request): View
    {
        $this->authorize('erp.reports.view');

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));
        ['orders' => $orders, 'summary' => $summary] = $this->reports->getSalesReport($from, $to);

        return view('erp.reports.sales', compact('orders', 'summary', 'from', 'to'));
    }

    public function receivables(): View
    {
        $this->authorize('erp.reports.view');

        ['invoices' => $invoices, 'totalOverdue' => $totalOverdue] = $this->reports->getReceivablesReport();

        return view('erp.reports.receivables', compact('invoices', 'totalOverdue'));
    }

    public function stock(Request $request): View
    {
        $this->authorize('erp.reports.view');

        $from = Carbon::parse($request->get('from', now()->subDays(30)));
        $to = Carbon::parse($request->get('to', now()));
        ['movements' => $movements, 'lowStock' => $lowStock] = $this->reports->getStockReport($from, $to);

        return view('erp.reports.stock', compact('movements', 'lowStock', 'from', 'to'));
    }

    public function profitLoss(Request $request): View
    {
        $this->authorize('erp.reports.view');

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));
        ['revenue' => $revenue, 'expenses' => $expenses, 'profit' => $profit] = $this->reports->getProfitLoss($from, $to);

        return view('erp.reports.profit-loss', compact('revenue', 'expenses', 'profit', 'from', 'to'));
    }
}
