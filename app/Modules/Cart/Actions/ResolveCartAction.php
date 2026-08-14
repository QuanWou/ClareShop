<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Data\CartResolution;
use App\Modules\Cart\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResolveCartAction
{
    public function __construct(private readonly MergeGuestCartAction $mergeGuestCart) {}

    public function execute(?int $userId, ?string $guestToken, bool $create): CartResolution
    {
        return DB::transaction(function () use ($userId, $guestToken, $create): CartResolution {
            $guestCart = $this->findGuestCart($guestToken);
            $forgetGuestCookie = $guestToken !== null && $guestCart === null;

            if ($userId !== null) {
                $userCart = Cart::query()
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if ($guestCart !== null) {
                    $userCart ??= Cart::query()->create([
                        'user_id' => $userId,
                        'currency' => config('commerce.currency'),
                    ]);

                    $this->mergeGuestCart->execute($guestCart, $userCart);

                    return new CartResolution(
                        cart: $userCart->fresh(),
                        forgetGuestCookie: true,
                    );
                }

                if ($userCart === null && $create) {
                    $userCart = Cart::query()->create([
                        'user_id' => $userId,
                        'currency' => config('commerce.currency'),
                    ]);
                }

                return new CartResolution(
                    cart: $userCart,
                    forgetGuestCookie: $forgetGuestCookie,
                );
            }

            if ($guestCart !== null) {
                return new CartResolution(
                    cart: $guestCart,
                    guestToken: $guestCart->guest_token,
                );
            }

            if (! $create) {
                return new CartResolution(
                    cart: null,
                    forgetGuestCookie: $forgetGuestCookie,
                );
            }

            $newToken = (string) Str::uuid();
            $cart = Cart::query()->create([
                'guest_token' => $newToken,
                'currency' => config('commerce.currency'),
                'expires_at' => now()->addMinutes(config('commerce.cart.ttl_minutes')),
            ]);

            return new CartResolution(
                cart: $cart,
                guestToken: $newToken,
                attachGuestCookie: true,
                forgetGuestCookie: $forgetGuestCookie,
            );
        });
    }

    private function findGuestCart(?string $guestToken): ?Cart
    {
        if ($guestToken === null || ! Str::isUuid($guestToken)) {
            return null;
        }

        return Cart::query()
            ->where('guest_token', $guestToken)
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->lockForUpdate()
            ->first();
    }
}
