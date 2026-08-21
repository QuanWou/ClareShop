<?php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class UpdateUserAddressAction
{
    /** @param array<string, mixed> $validated */
    public function execute(UserAddress $address, array $validated): UserAddress
    {
        return DB::transaction(function () use ($address, $validated): UserAddress {
            $makeDefault = (bool) ($validated['is_default'] ?? false) || $address->is_default;

            if ($makeDefault) {
                $address->user->addresses()->whereKeyNot($address->getKey())->update(['is_default' => false]);
            }

            $address->update([
                'label' => ($validated['label'] ?? null) ?: 'Nhà riêng',
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['address_phone'],
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'ward' => $validated['ward'],
                'district' => $validated['district'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'] ?? null,
                'country_code' => $validated['country_code'],
                'is_default' => $makeDefault,
            ]);

            return $address->refresh();
        });
    }
}
