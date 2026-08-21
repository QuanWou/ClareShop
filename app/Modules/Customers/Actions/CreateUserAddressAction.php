<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Customers\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class CreateUserAddressAction
{
    /** @param array<string, mixed> $validated */
    public function execute(User $user, array $validated): UserAddress
    {
        return DB::transaction(function () use ($user, $validated): UserAddress {
            $makeDefault = (bool) ($validated['is_default'] ?? false) || ! $user->addresses()->exists();

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create($this->attributes($validated, $makeDefault));
        });
    }

    /** @param array<string, mixed> $validated */
    private function attributes(array $validated, bool $isDefault): array
    {
        return [
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
            'is_default' => $isDefault,
        ];
    }
}
