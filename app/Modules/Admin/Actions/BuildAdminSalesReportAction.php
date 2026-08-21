<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BuildAdminSalesReportAction
{
    /** @param array{from: string, to: string} $filters */
    public function execute(array $filters): array
    {
        $from = CarbonImmutable::parse($filters['from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['to'])->endOfDay();
        $orders = Order::query()->whereBetween('placed_at', [$from, $to]);
        $validOrders = (clone $orders)->where('status', '!=', 'cancelled');

        return [
            'filters' => $filters,
            'metrics' => [
                'orders' => (clone $orders)->count(),
                'completedOrders' => (clone $orders)->where('status', 'completed')->count(),
                'revenue' => (float) (clone $validOrders)->sum('total'),
                'paidRevenue' => (float) (clone $validOrders)->where('payment_status', 'paid')->sum('total'),
                'averageOrderValue' => (float) (clone $validOrders)->avg('total'),
                'newCustomers' => User::query()->where('role', 'customer')->whereBetween('created_at', [$from, $to])->count(),
                'productsSold' => (int) DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->whereBetween('orders.placed_at', [$from, $to])->where('orders.status', '!=', 'cancelled')->sum('order_items.quantity'),
            ],
            'statusBreakdown' => (clone $orders)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->orderByDesc('aggregate')->get(),
            'topProducts' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereBetween('orders.placed_at', [$from, $to])
                ->where('orders.status', '!=', 'cancelled')
                ->groupBy('order_items.product_slug', 'order_items.product_name')
                ->selectRaw('order_items.product_slug, order_items.product_name, SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as revenue')
                ->orderByDesc('quantity')
                ->limit(10)
                ->get(),
            'recentOrders' => (clone $orders)->latest('placed_at')->limit(12)->get(),
        ];
    }
}
