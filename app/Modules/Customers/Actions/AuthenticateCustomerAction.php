<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateCustomerAction
{
    public function execute(string $email, string $password, bool $remember, string $ipAddress): User
    {
        $throttleKey = $this->throttleKey($email, $ipAddress);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau '.RateLimiter::availableIn($throttleKey).' giây.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ], $remember)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu chưa đúng.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function throttleKey(string $email, string $ipAddress): string
    {
        return Str::transliterate(Str::lower($email).'|'.$ipAddress);
    }
}
