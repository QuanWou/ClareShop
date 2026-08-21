<?php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class DeleteUserAddressAction
{
    public function execute(UserAddress $address): void
    {
        DB::transaction(function () use ($address): void {
            $user = $address->user;
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $user->addresses()->oldest()->first()?->update(['is_default' => true]);
            }
        });
    }
}
