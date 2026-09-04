<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;

class MergeGuestCartAction
{
    public function execute(Cart $guestCart, Cart $userCart): void
    {
        $guestCart->load('items.variant.product');

        foreach ($guestCart->items as $guestItem) {
            $variant = $guestItem->variant;

            if (! $variant?->isPurchasable()) {
                continue;
            }

            $userItem = CartItem::query()
                ->where('cart_id', $userCart->getKey())
                ->where('product_variant_id', $variant->getKey())
                ->lockForUpdate()
                ->first();

            $quantity = min(
                ($userItem?->quantity ?? 0) + $guestItem->quantity,
                $variant->stock_quantity,
                config('commerce.cart.maximum_quantity'),
            );

            CartItem::query()->updateOrCreate(
                [
                    'cart_id' => $userCart->getKey(),
                    'product_variant_id' => $variant->getKey(),
                ],
                [
                    'quantity' => $quantity,
                    'is_selected' => (bool) ($userItem?->is_selected || $guestItem->is_selected),
                ],
            );
        }

        $guestCart->delete();
    }
}
