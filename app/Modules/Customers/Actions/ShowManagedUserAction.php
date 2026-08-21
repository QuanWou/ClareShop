<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;

class ShowManagedUserAction
{
    public function execute(User $user): User
    {
        return User::query()
            ->withCount([
                'orders',
                'appointments',
                'addresses',
                'orders as completed_orders_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->withSum([
                'orders as total_spent' => fn ($query) => $query->where('status', 'completed'),
            ], 'total')
            ->with([
                'orders' => fn ($query) => $query
                    ->withCount('items')
                    ->latest('placed_at')
                    ->latest('id')
                    ->limit(6),
                'addresses' => fn ($query) => $query
                    ->orderByDesc('is_default')
                    ->latest('id'),
            ])
            ->findOrFail($user->getKey());
    }
}
