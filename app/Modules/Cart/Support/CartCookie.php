<?php

namespace App\Modules\Cart\Support;

use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class CartCookie
{
    public static function make(string $token, bool $secure): SymfonyCookie
    {
        return cookie(
            name: config('commerce.cart.cookie'),
            value: $token,
            minutes: config('commerce.cart.ttl_minutes'),
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );
    }

    public static function forget(): SymfonyCookie
    {
        return Cookie::forget(config('commerce.cart.cookie'));
    }
}
