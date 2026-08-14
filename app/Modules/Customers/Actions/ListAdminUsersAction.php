<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminUsersAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->withCount(['orders', 'appointments'])
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
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }
}
