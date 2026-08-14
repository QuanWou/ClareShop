<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminAppointmentsAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Appointment::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
