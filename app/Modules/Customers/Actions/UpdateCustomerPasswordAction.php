<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateCustomerPasswordAction
{
    public function execute(User $user, array $validated): void
    {
        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => null,
            ])->save();
        });
    }
}
