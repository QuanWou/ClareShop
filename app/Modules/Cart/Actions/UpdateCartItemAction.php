<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCartItemAction
{
    public function execute(Cart $cart, int $cartItemId, int $quantity): CartItem
    {
        if ($quantity < 1 || $quantity > config('commerce.cart.maximum_quantity')) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng phải từ 1 đến '.config('commerce.cart.maximum_quantity').'.',
            ]);
        }

        return DB::transaction(function () use ($cart, $cartItemId, $quantity): CartItem {
            $item = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->with('variant.product')
                ->lockForUpdate()
                ->findOrFail($cartItemId);

            if (! $item->variant?->isPurchasable()) {
                throw ValidationException::withMessages([
                    'quantity' => 'Sản phẩm này hiện không còn có thể cập nhật trong giỏ.',
                ]);
            }

            if ($quantity > $item->variant->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Chỉ còn {$item->variant->stock_quantity} sản phẩm cho màu này.",
                ]);
            }

            $item->update(['quantity' => $quantity]);

            if ($cart->guest_token !== null) {
                $cart->update([
                    'expires_at' => now()->addMinutes(config('commerce.cart.ttl_minutes')),
                ]);
            }

            return $item;
        });
    }
}
