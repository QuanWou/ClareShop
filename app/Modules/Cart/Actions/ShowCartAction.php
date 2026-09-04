<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;

class ShowCartAction
{
    public function execute(?Cart $cart, bool $selectedOnly = false): array
    {
        if ($cart === null) {
            return [
                'cart' => null,
                'cartLines' => collect(),
                'subtotal' => 0,
                'selectedSubtotal' => 0,
                'selectedQuantity' => 0,
            ];
        }

        $cart->load([
            'items' => fn ($query) => $query->when($selectedOnly, fn ($query) => $query->where('is_selected', true)),
            'items.variant.product.category',
            'items.variant.product.images',
            'items.variant.images',
        ]);

        $subtotal = 0;
        $selectedSubtotal = 0;
        $selectedQuantity = 0;
        $cartLines = $cart->items->map(function ($item) use (&$subtotal, &$selectedSubtotal, &$selectedQuantity): array {
            $variant = $item->variant;
            $product = $variant?->product;
            $unitPrice = $variant === null ? 0 : (int) round((float) $variant->price);
            $lineTotal = $unitPrice * $item->quantity;
            $isAvailable = $variant?->isPurchasable() ?? false;

            if ($isAvailable) {
                $subtotal += $lineTotal;

                if ($item->is_selected) {
                    $selectedSubtotal += $lineTotal;
                    $selectedQuantity += $item->quantity;
                }
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

        return compact('cart', 'cartLines', 'subtotal', 'selectedSubtotal', 'selectedQuantity');
    }
}
