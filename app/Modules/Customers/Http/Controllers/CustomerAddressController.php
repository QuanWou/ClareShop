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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function store(SaveDefaultUserAddressRequest $request, CreateUserAddressAction $action): RedirectResponse
    {
        $action->execute($this->customer($request), $request->validated());

        return back()->with('success', 'Đã thêm địa chỉ nhận hàng.');
    }

    public function update(SaveDefaultUserAddressRequest $request, UserAddress $address, UpdateUserAddressAction $action): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $action->execute($address, $request->validated());

        return back()->with('success', 'Đã cập nhật địa chỉ nhận hàng.');
    }

    public function setDefault(Request $request, UserAddress $address, SetDefaultUserAddressAction $action): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $action->execute($address);

        return back()->with('success', 'Đã đổi địa chỉ mặc định.');
    }

    public function destroy(Request $request, UserAddress $address, DeleteUserAddressAction $action): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $action->execute($address);

        return back()->with('success', 'Đã xóa địa chỉ.');
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
