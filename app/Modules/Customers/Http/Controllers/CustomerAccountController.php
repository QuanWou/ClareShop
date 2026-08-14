<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customers\Actions\DeleteCustomerAccountAction;
use App\Modules\Customers\Actions\SaveDefaultUserAddressAction;
use App\Modules\Customers\Actions\ShowCustomerAccountAction;
use App\Modules\Customers\Actions\UpdateCustomerPasswordAction;
use App\Modules\Customers\Actions\UpdateCustomerProfileAction;
use App\Modules\Customers\Http\Requests\DeleteCustomerAccountRequest;
use App\Modules\Customers\Http\Requests\SaveDefaultUserAddressRequest;
use App\Modules\Customers\Http\Requests\UpdateCustomerPasswordRequest;
use App\Modules\Customers\Http\Requests\UpdateCustomerProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAccountController extends Controller
{
    public function show(Request $request, ShowCustomerAccountAction $showAccount): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('customers.account.show', [
            'user' => $user,
            ...$showAccount->execute($user),
        ]);
    }

    public function updateProfile(
        UpdateCustomerProfileRequest $request,
        UpdateCustomerProfileAction $updateProfile,
    ): RedirectResponse {
        $updateProfile->execute($this->customer($request), $request->validated());

        return redirect()
            ->route('account.show')
            ->with('success', 'Thông tin cá nhân đã được cập nhật.');
    }

    public function updatePassword(
        UpdateCustomerPasswordRequest $request,
        UpdateCustomerPasswordAction $updatePassword,
    ): RedirectResponse {
        $updatePassword->execute($this->customer($request), $request->validated());

        return redirect()
            ->route('account.show')
            ->with('success', 'Mật khẩu đã được thay đổi.');
    }

    public function updateDefaultAddress(
        SaveDefaultUserAddressRequest $request,
        SaveDefaultUserAddressAction $saveDefaultAddress,
    ): RedirectResponse {
        $saveDefaultAddress->execute($this->customer($request), $request->validated());

        return redirect()
            ->route('account.show')
            ->with('success', 'Địa chỉ nhận hàng mặc định đã được lưu.');
    }

    public function destroy(
        DeleteCustomerAccountRequest $request,
        DeleteCustomerAccountAction $deleteAccount,
    ): RedirectResponse {
        $deleteAccount->execute($this->customer($request));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('catalog.home')
            ->with('success', 'Tài khoản đã được xóa. Các snapshot đơn hàng cần lưu cho vận hành vẫn được bảo toàn.');
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
