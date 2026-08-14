<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;

class ShowManagedUserAction
{
    public function execute(User $user): User
    {
        return User::query()
            ->withCount(['orders', 'appointments'])
            ->findOrFail($user->getKey());
    }
}
