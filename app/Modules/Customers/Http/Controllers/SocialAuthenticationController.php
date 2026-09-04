<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Settings\Support\SiteSettingsRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthenticationController extends Controller
{
    public function redirect(string $provider, SiteSettingsRegistry $settings): SymfonyRedirectResponse|RedirectResponse
    {
        $this->ensureProvider($provider, $settings);
        $this->configureProvider($provider, $settings);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, SiteSettingsRegistry $settings): RedirectResponse
    {
        $this->ensureProvider($provider, $settings);
        $this->configureProvider($provider, $settings);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['social' => 'Không thể xác thực tài khoản mạng xã hội. Vui lòng thử lại.']);
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('login')->withErrors(['social' => 'Tài khoản này chưa cung cấp email để liên kết với Clare.']);
        }

        $providerColumn = "{$provider}_id";
        $user = User::withTrashed()
            ->where($providerColumn, $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        if ($user?->trashed() || ($user && ! $user->is_active)) {
            return redirect()->route('login')->withErrors(['social' => 'Tài khoản Clare này hiện không hoạt động.']);
        }

        if ($user === null) {
            $user = User::query()->create([
                'name' => $socialUser->getName() ?: Str::before($socialUser->getEmail(), '@'),
                'email' => $socialUser->getEmail(),
                $providerColumn => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'role' => 'customer',
                'is_active' => true,
                'password' => Hash::make(Str::random(48)),
            ]);
        } else {
            $user->update([$providerColumn => $socialUser->getId(), 'avatar_url' => $socialUser->getAvatar() ?: $user->avatar_url]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('catalog.home'));
    }

    private function ensureProvider(string $provider, SiteSettingsRegistry $settings): void
    {
        abort_unless(in_array($provider, ['google', 'facebook'], true), 404);

        if (! $settings->socialProviderConfigured($provider)) {
            abort(503, 'Kênh đăng nhập này chưa được cấu hình.');
        }
    }

    private function configureProvider(string $provider, SiteSettingsRegistry $settings): void
    {
        config([
            "services.{$provider}.client_id" => $settings->get("{$provider}_client_id"),
            "services.{$provider}.client_secret" => $settings->get("{$provider}_client_secret"),
            "services.{$provider}.redirect" => $settings->get("{$provider}_redirect_url"),
        ]);
    }
}
