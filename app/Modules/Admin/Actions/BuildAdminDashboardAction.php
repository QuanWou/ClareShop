<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Models\User;

class BuildAdminDashboardAction
{
    public function execute(): array
    {
        $statusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Chờ lấy hàng',
            'processing' => 'Đang chuẩn bị giao',
            'shipped' => 'Đang giao hàng',
            'completed' => 'Đã giao',
            'cancelled' => 'Đã hủy',
        ];
        $statusColors = [
            'pending' => '#c69b3c',
            'confirmed' => '#5d806b',
            'processing' => '#6b8c7a',
            'shipped' => '#597b98',
            'completed' => '#4f765a',
            'cancelled' => '#a75b5f',
        ];
        $countsByStatus = Order::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $totalOrders = (int) $countsByStatus->sum();
        $statusBreakdown = collect($statusLabels)->map(function (string $label, string $status) use ($countsByStatus, $statusColors, $totalOrders): array {
            $count = (int) ($countsByStatus[$status] ?? 0);

            return [
                'status' => $status,
                'label' => $label,
                'count' => $count,
                'percentage' => $totalOrders > 0 ? (int) round($count / $totalOrders * 100) : 0,
                'share' => $totalOrders > 0 ? $count / $totalOrders * 100 : 0,
                'color' => $statusColors[$status],
            ];
        })->values();
        $donutSegments = [];
        $position = 0;

        foreach ($statusBreakdown->where('count', '>', 0) as $status) {
            $nextPosition = $position + $status['share'];
            $donutSegments[] = $status['color'].' '.$position.'% '.$nextPosition.'%';
            $position = $nextPosition;
        }

        $sevenDayRevenue = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = now()->subDays($daysAgo);
            $amount = (int) Order::query()
                ->whereDate('placed_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total');
            $orders = Order::query()->whereDate('placed_at', $date)->count();

            return [
                'label' => $date->format('d/m'),
                'amount' => $amount,
                'orders' => $orders,
            ];
        });
        $maximumDailyRevenue = max(1, (int) $sevenDayRevenue->max('amount'));

        return [
            'metrics' => [
                'pendingOrders' => Order::query()->where('status', 'pending')->count(),
                'pendingPayments' => Order::query()->where('payment_status', 'pending')->count(),
                'activeDeliveries' => Order::query()->where('status', 'shipped')->count(),
                'pendingAppointments' => Appointment::query()->where('status', 'pending')->count(),
                'activeOrderValue' => (float) Order::query()
                    ->where('status', '!=', 'cancelled')
                    ->sum('total'),
                'paidRevenue' => (float) Order::query()
                    ->where('status', '!=', 'cancelled')
                    ->where('payment_status', 'paid')
                    ->sum('total'),
                'products' => Product::query()->count(),
                'lowStockVariants' => ProductVariant::query()
                    ->where('is_active', true)
                    ->where('stock_quantity', '<=', 3)
                    ->count(),
                'activeCustomers' => User::query()
                    ->where('role', 'customer')
                    ->where('is_active', true)
                    ->count(),
            ],
            'totalOrders' => $totalOrders,
            'statusBreakdown' => $statusBreakdown,
            'statusDonut' => $donutSegments === []
                ? 'conic-gradient(#d9d1c6 0 100%)'
                : 'conic-gradient('.implode(', ', $donutSegments).')',
            'sevenDayRevenue' => $sevenDayRevenue->map(fn (array $day): array => [
                ...$day,
                'height' => max(6, (int) round($day['amount'] / $maximumDailyRevenue * 100)),
            ]),
            'lowStockVariants' => ProductVariant::query()
                ->with('product')
                ->where('is_active', true)
                ->where('stock_quantity', '<=', 3)
                ->orderBy('stock_quantity')
                ->limit(6)
                ->get(),
            'recentOrders' => Order::query()
                ->latest('placed_at')
                ->limit(6)
                ->get(),
            'recentAppointments' => Appointment::query()
                ->latest()
                ->limit(6)
                ->get(),
        ];
    }
}
