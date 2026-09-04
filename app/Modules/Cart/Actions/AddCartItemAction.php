<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddCartItemAction
{
    public function execute(Cart $cart, int $variantId, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng tối thiểu là 1.',
            ]);
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartItem {
            $variant = ProductVariant::query()
                ->with('product')
                ->lockForUpdate()
                ->find($variantId);

            if (! $variant?->isPurchasable()) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Biến thể này hiện không thể thêm vào giỏ.',
                ]);
            }

            $item = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->where('product_variant_id', $variant->getKey())
                ->lockForUpdate()
                ->first();

            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            $this->ensureQuantityIsAvailable($newQuantity, $variant->stock_quantity);

            $item ??= new CartItem([
                'cart_id' => $cart->getKey(),
                'product_variant_id' => $variant->getKey(),
            ]);
            $item->quantity = $newQuantity;
            $item->is_selected = true;
            $item->save();

            $this->refreshGuestCartExpiry($cart);

            return $item;
        });
    }

    private function ensureQuantityIsAvailable(int $quantity, int $stockQuantity): void
    {
        if ($quantity > $stockQuantity) {
            throw ValidationException::withMessages([
                'quantity' => "Chỉ còn {$stockQuantity} sản phẩm cho màu này.",
            ]);
        }

        if ($quantity > config('commerce.cart.maximum_quantity')) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng tối đa cho mỗi màu là '.config('commerce.cart.maximum_quantity').'.',
            ]);
        }
    }

    private function refreshGuestCartExpiry(Cart $cart): void
    {
        if ($cart->guest_token === null) {
            return;
        }

        $cart->update([
            'expires_at' => now()->addMinutes(config('commerce.cart.ttl_minutes')),
        ]);
    }
}
