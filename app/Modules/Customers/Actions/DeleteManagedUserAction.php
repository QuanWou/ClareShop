<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteManagedUserAction
{
    public function __construct(private readonly DeleteCustomerAccountAction $deleteCustomerAccount)
    {
    }

    public function execute(User $actor, User $user): void
    {
        if ((int) $actor->getKey() === (int) $user->getKey()) {
            throw ValidationException::withMessages([
                'account_deletion' => 'Bạn không thể xóa tài khoản quản trị đang đăng nhập. Hãy dùng trang Tài khoản nếu cần xóa tài khoản của chính mình.',
            ]);
        }

        $this->deleteCustomerAccount->execute($user);
    }
}
