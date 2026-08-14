<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateCustomerProfileAction
{
    public function execute(User $user, array $validated): User
    {
        return DB::transaction(function () use ($user, $validated): User {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            return $user->fresh();
        });
    }
}
