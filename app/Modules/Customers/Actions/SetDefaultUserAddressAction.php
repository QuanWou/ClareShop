<?php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class SetDefaultUserAddressAction
{
    public function execute(UserAddress $address): void
    {
        DB::transaction(function () use ($address): void {
            $address->user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }
}
