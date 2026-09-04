<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Actions\AuthenticateCustomerAction;
use App\Modules\Customers\Actions\RegisterCustomerAction;
use App\Modules\Customers\Http\Requests\LoginRequest;
use App\Modules\Customers\Http\Requests\RegisterCustomerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    public function createLogin(): View
    {
        return view('customers.auth.login');
    }

    public function storeLogin(LoginRequest $request, AuthenticateCustomerAction $authenticate): RedirectResponse
    {
        $authenticate->execute(
            email: $request->validated('email'),
            password: $request->validated('password'),
            remember: $request->boolean('remember'),
            ipAddress: $request->ip(),
        );

        $request->session()->regenerate();

        if ($request->session()->has('promotions.pending_claim_id')) {
            return redirect()->route('promotions.claim.resume');
        }

        return redirect()
            ->intended(route('catalog.home'))
            ->with('success', 'Chào mừng bạn trở lại Clare.');
    }

    public function createRegister(): View
    {
        return view('customers.auth.register');
    }

    public function storeRegister(RegisterCustomerRequest $request, RegisterCustomerAction $register): RedirectResponse
    {
        $user = $register->execute($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->session()->has('promotions.pending_claim_id')) {
            return redirect()->route('promotions.claim.resume');
        }

        return redirect()
            ->intended(route('account.show'))
            ->with('success', 'Tài khoản Clare của bạn đã được tạo.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('catalog.home')
            ->with('success', 'Bạn đã đăng xuất khỏi tài khoản Clare.');
    }
}
