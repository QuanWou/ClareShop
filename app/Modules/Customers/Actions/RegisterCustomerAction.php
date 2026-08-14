<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterCustomerAction
{
    public function execute(array $validated): User
    {
        return DB::transaction(fn (): User => User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => true,
        ]));
    }
}
