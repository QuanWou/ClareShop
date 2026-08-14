<?php

namespace App\Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->user()?->is_active) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(403, 'Tài khoản này hiện không thể thực hiện giao dịch.');
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Tài khoản này hiện không thể thực hiện giao dịch.']);
    }
}
