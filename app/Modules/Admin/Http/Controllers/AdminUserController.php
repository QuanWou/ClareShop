<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customers\Actions\CreateManagedUserAction;
use App\Modules\Customers\Actions\DeleteManagedUserAction;
use App\Modules\Customers\Actions\ListAdminUsersAction;
use App\Modules\Customers\Actions\ShowManagedUserAction;
use App\Modules\Customers\Actions\UpdateManagedUserAction;
use App\Modules\Customers\Http\Requests\DeleteManagedUserRequest;
use App\Modules\Customers\Http\Requests\ListAdminUsersRequest;
use App\Modules\Customers\Http\Requests\StoreManagedUserRequest;
use App\Modules\Customers\Http\Requests\UpdateManagedUserRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminUserController extends Controller
{
    public function index(ListAdminUsersRequest $request, ListAdminUsersAction $listUsers): View
    {
        return view('admin.users.index', [
            'users' => $listUsers->execute($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function edit(User $user, ShowManagedUserAction $showUser): View
    {
        return view('admin.users.edit', ['user' => $showUser->execute($user)]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreManagedUserRequest $request, CreateManagedUserAction $createUser): RedirectResponse
    {
        $user = $createUser->execute($request->validated());

        return redirect()->route('admin.users.edit', $user)->with('success', 'Tài khoản mới đã được tạo.');
    }

    public function update(
        UpdateManagedUserRequest $request,
        User $user,
        UpdateManagedUserAction $updateUser,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updateUser->execute($actor, $user, $request->validated());

        return redirect()->route('admin.users.edit', $user)->with('success', 'Thông tin và quyền truy cập đã được cập nhật.');
    }

    public function destroy(
        DeleteManagedUserRequest $request,
        User $user,
        DeleteManagedUserAction $deleteUser,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $deleteUser->execute($actor, $user);

        return redirect()->route('admin.users.index')->with('success', 'Tài khoản đã được xóa an toàn. Dữ liệu đơn hàng và lịch sử vận hành vẫn được bảo toàn.');
    }
}
