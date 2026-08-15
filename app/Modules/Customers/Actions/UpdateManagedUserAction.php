<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateManagedUserAction
{
    public function execute(User $actor, User $user, array $validated): User
    {
        $removesCurrentAdminAccess = $user->isAdmin()
            && (($validated['role'] !== 'admin') || ! $validated['is_active']);

        if ((int) $actor->getKey() === (int) $user->getKey() && $removesCurrentAdminAccess) {
            throw ValidationException::withMessages([
                'role' => 'Bạn không thể tự gỡ quyền quản trị hoặc khóa tài khoản đang đăng nhập.',
            ]);
        }

        if ($removesCurrentAdminAccess && ! User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Hệ thống phải luôn còn ít nhất một quản trị viên đang hoạt động.',
            ]);
        }

        $attributes = Arr::only($validated, ['name', 'email', 'phone', 'role', 'is_active']);

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user->update($attributes);

        return $user->fresh();
    }
}
