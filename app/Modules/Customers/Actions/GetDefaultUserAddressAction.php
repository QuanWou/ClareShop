<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Customers\Models\UserAddress;

class GetDefaultUserAddressAction
{
    public function execute(User $user): ?UserAddress
    {
        return $user->addresses()
            ->where('is_default', true)
            ->first();
    }
}
