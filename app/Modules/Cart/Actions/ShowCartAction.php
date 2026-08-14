<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;

class ShowCartAction
{
    public function execute(?Cart $cart): array
    {
        if ($cart === null) {
            return [
                'cart' => null,
                'cartLines' => collect(),
                'subtotal' => 0,
            ];
        }

        $cart->load([
            'items.variant.product.category',
            'items.variant.product.images',
            'items.variant.images',
        ]);

        $subtotal = 0;
        $cartLines = $cart->items->map(function ($item) use (&$subtotal): array {
            $variant = $item->variant;
            $product = $variant?->product;
            $unitPrice = $variant === null ? 0 : (int) round((float) $variant->price);
            $lineTotal = $unitPrice * $item->quantity;
            $isAvailable = $variant?->isPurchasable() ?? false;

            if ($isAvailable) {
                $subtotal += $lineTotal;
            }

            return [
                'item' => $item,
                'variant' => $variant,
                'product' => $product,
                'image' => $variant?->images->first() ?? $product?->images->first(),
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'is_available' => $isAvailable,
            ];
        });

        return compact('cart', 'cartLines', 'subtotal');
    }
}
