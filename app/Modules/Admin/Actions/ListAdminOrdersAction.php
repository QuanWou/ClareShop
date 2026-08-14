<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminOrdersAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Order::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();
    }
}
