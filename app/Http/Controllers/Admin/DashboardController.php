<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalOrders    = Order::count();
        $totalRevenue   = Order::where('status', 'delivered')->sum('total');
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProducts  = Product::count();

        $lowStockProducts = Product::where('stock', '<=', 5)
            ->where('status', true)
            ->get();

        $recentOrders = Order::with('user')
            ->latest()
            ->limit(10)
            ->get();

        $topProducts = DB::table('order_items')
            ->select('product_id', DB::raw('product_name as name'), DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalOrders', 'totalRevenue', 'totalCustomers', 'totalProducts',
            'lowStockProducts', 'recentOrders', 'topProducts'
        ));
    }
}
