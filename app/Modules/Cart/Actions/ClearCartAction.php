<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;
use Illuminate\Support\Facades\DB;

class ClearCartAction
{
    public function execute(Cart $cart): void
    {
        DB::transaction(function () use ($cart): void {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->getKey());
            $lockedCart->items()->delete();

            if ($lockedCart->guest_token !== null) {
                $lockedCart->update(['expires_at' => now()->addMinutes(config('commerce.cart.ttl_minutes'))]);
            }
        });
    }
}
