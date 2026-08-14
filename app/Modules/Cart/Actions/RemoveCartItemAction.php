<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use Illuminate\Support\Facades\DB;

class RemoveCartItemAction
{
    public function execute(Cart $cart, int $cartItemId): void
    {
        DB::transaction(function () use ($cart, $cartItemId): void {
            $item = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->lockForUpdate()
                ->findOrFail($cartItemId);

            $item->delete();

            if ($cart->guest_token !== null) {
                $cart->update([
                    'expires_at' => now()->addMinutes(config('commerce.cart.ttl_minutes')),
                ]);
            }
        });
    }
}
