<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdminRoleCommand extends Command
{
    protected $signature = 'clare:grant-admin {email : Email của tài khoản cần cấp quyền quản trị}';

    protected $description = 'Cấp vai trò quản trị cho một tài khoản Clare đã tồn tại';

    public function handle(): int
    {
        $user = User::query()
            ->where('email', strtolower((string) $this->argument('email')))
            ->first();

        if ($user === null) {
            $this->error('Không tìm thấy tài khoản với email này.');

            return self::FAILURE;
        }

        $user->update([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info("Đã cấp quyền admin cho {$user->email}.");

        return self::SUCCESS;
    }
}
