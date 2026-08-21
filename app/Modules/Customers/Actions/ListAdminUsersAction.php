<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminUsersAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount(['orders', 'appointments', 'addresses'])
            ->withSum([
                'orders as total_spent' => fn ($query) => $query->where('status', 'completed'),
            ], 'total')
            ->withMax('orders as last_order_at', 'placed_at')
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        match ($filters['sort'] ?? 'newest') {
            'spent_desc' => $query->orderByDesc('total_spent')->latest('id'),
            'orders_desc' => $query->orderByDesc('orders_count')->latest('id'),
            'last_order_desc' => $query->orderByDesc('last_order_at')->latest('id'),
            default => $query->latest('id'),
        };

        return $query
            ->paginate(20)
            ->withQueryString();
    }
}
