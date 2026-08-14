<?php

namespace App\Modules\Cart\Http\Middleware;

use App\Modules\Cart\Actions\ResolveCartAction;
use App\Modules\Cart\Support\CartCookie;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareCartSummary
{
    public function __construct(private readonly ResolveCartAction $resolveCart) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resolution = $this->resolveCart->execute(
            userId: $request->user() === null ? null : (int) $request->user()->getAuthIdentifier(),
            guestToken: $request->cookie(config('commerce.cart.cookie')),
            create: false,
        );

        View::share(
            'cartItemCount',
            (int) ($resolution->cart?->items()->sum('quantity') ?? 0),
        );

        $response = $next($request);

        $hasReplacementCookie = collect($response->headers->getCookies())
            ->contains(fn ($cookie) => $cookie->getName() === config('commerce.cart.cookie'));

        if ($resolution->forgetGuestCookie && ! $hasReplacementCookie) {
            $response->headers->setCookie(CartCookie::forget());
        }

        return $response;
    }
}
