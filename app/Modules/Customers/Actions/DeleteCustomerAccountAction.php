<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeleteCustomerAccountAction
{
    public function execute(User $user): void
    {
        $this->ensureDeletionIsSafe($user);

        DB::transaction(function () use ($user): void {
            $user->addresses()->delete();

            $user->forceFill([
                'name' => 'Tài khoản đã xóa',
                'email' => sprintf('deleted-user-%d-%s@deleted.clare.local', $user->getKey(), Str::lower(Str::random(24))),
                'email_verified_at' => null,
                'phone' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'is_active' => false,
            ])->save();

            $user->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->delete();
            }
        });
    }

    private function ensureDeletionIsSafe(User $user): void
    {
        if ($user->isAdmin() && ! User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'account_deletion' => 'Hệ thống cần còn ít nhất một quản trị viên đang hoạt động. Hãy cấp quyền cho một tài khoản khác trước khi xóa tài khoản này.',
            ]);
        }

        if (Order::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped'])
            ->exists()) {
            throw ValidationException::withMessages([
                'account_deletion' => 'Bạn đang có đơn hàng cần xử lý. Hãy chờ đơn hoàn tất hoặc liên hệ Clare để được hỗ trợ trước khi xóa tài khoản.',
            ]);
        }

        if (Appointment::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists()) {
            throw ValidationException::withMessages([
                'account_deletion' => 'Bạn đang có yêu cầu tư vấn hoặc lắp đặt cần xử lý. Hãy liên hệ Clare trước khi xóa tài khoản.',
            ]);
        }
    }
}
