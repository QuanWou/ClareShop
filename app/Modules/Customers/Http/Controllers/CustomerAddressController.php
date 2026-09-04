<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customers\Actions\CreateUserAddressAction;
use App\Modules\Customers\Actions\DeleteUserAddressAction;
use App\Modules\Customers\Actions\SetDefaultUserAddressAction;
use App\Modules\Customers\Actions\UpdateUserAddressAction;
use App\Modules\Customers\Http\Requests\SaveDefaultUserAddressRequest;
use App\Modules\Customers\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function store(SaveDefaultUserAddressRequest $request, CreateUserAddressAction $action): RedirectResponse|JsonResponse
    {
        $address = $action->execute($this->customer($request), $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->addressData($address), 'message' => 'Đã thêm địa chỉ nhận hàng.'], 201);
        }

        return back()->with('success', 'Đã thêm địa chỉ nhận hàng.');
    }

    public function update(SaveDefaultUserAddressRequest $request, UserAddress $address, UpdateUserAddressAction $action): RedirectResponse|JsonResponse
    {
        $this->authorizeAddress($request, $address);
        $address = $action->execute($address, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->addressData($address), 'message' => 'Đã cập nhật địa chỉ nhận hàng.']);
        }

        return back()->with('success', 'Đã cập nhật địa chỉ nhận hàng.');
    }

    public function setDefault(Request $request, UserAddress $address, SetDefaultUserAddressAction $action): RedirectResponse|JsonResponse
    {
        $this->authorizeAddress($request, $address);
        $action->execute($address);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->addressData($address->refresh()), 'message' => 'Đã đổi địa chỉ mặc định.']);
        }

        return back()->with('success', 'Đã đổi địa chỉ mặc định.');
    }

    public function destroy(Request $request, UserAddress $address, DeleteUserAddressAction $action): RedirectResponse|JsonResponse
    {
        $this->authorizeAddress($request, $address);
        $action->execute($address);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa địa chỉ.']);
        }

        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    /** @return array<string, mixed> */
    private function addressData(UserAddress $address): array
    {
        return [
            'id' => $address->getKey(),
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'ward' => $address->ward,
            'district' => $address->district,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
            'is_default' => (bool) $address->is_default,
        ];
    }

    private function authorizeAddress(Request $request, UserAddress $address): void
    {
        abort_unless((int) $address->user_id === (int) $request->user()?->getAuthIdentifier(), 404);
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
