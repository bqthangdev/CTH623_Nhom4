<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSummary(): array
    {
        return [
            'total_orders'    => Order::count(),
            'total_revenue'   => Order::where('status', 'delivered')->sum('total'),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_products'  => Product::count(),
        ];
    }

    public function getLowStockProducts(int $threshold = 5): Collection
    {
        return Product::where('stock', '<=', $threshold)
            ->where('status', true)
            ->get();
    }

    public function getRecentOrders(int $limit = 10): Collection
    {
        return Order::with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getTopProducts(int $limit = 5): Collection
    {
        return DB::table('order_items')
            ->select('product_id', DB::raw('product_name as name'), DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }
}
