<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\Erp\DashboardService;
use Illuminate\View\View;

/**
 * ERP dashboard with KPI aggregation.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(): View
    {
        $this->authorize('erp.dashboard.view');

        $kpis = $this->dashboard->kpis();
        $recentOrders = $this->dashboard->recentOrders();
        $recentInvoices = $this->dashboard->recentInvoices();

        return view('erp.dashboard', compact('kpis', 'recentOrders', 'recentInvoices'));
    }
}
