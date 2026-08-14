<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Customers\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class SaveDefaultUserAddressAction
{
    public function execute(User $user, array $validated): UserAddress
    {
        $addressData = [
            ...$validated,
            'phone' => $validated['address_phone'],
        ];
        unset($addressData['address_phone']);

        return DB::transaction(function () use ($user, $addressData): UserAddress {
            $address = $user->addresses()
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if ($address !== null) {
                $address->update($addressData);

                return $address->fresh();
            }

            $user->addresses()->update(['is_default' => false]);

            return $user->addresses()->create([
                ...$addressData,
                'is_default' => true,
            ]);
        });
    }
}
