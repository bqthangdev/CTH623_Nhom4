<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        $summary          = $this->dashboardService->getSummary();
        $lowStockProducts = $this->dashboardService->getLowStockProducts();
        $recentOrders     = $this->dashboardService->getRecentOrders();
        $topProducts      = $this->dashboardService->getTopProducts();

        return view('admin.dashboard', array_merge($summary, compact(
            'lowStockProducts', 'recentOrders', 'topProducts'
        )));
    }
}
