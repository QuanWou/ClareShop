<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectCartItemsForCheckoutAction
{
    /** @param array<int, int> $cartItemIds */
    public function execute(Cart $cart, array $cartItemIds): void
    {
        $ids = collect($cartItemIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'cart_item_ids' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.',
            ]);
        }

        DB::transaction(function () use ($cart, $ids): void {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->getKey());
            $items = CartItem::query()
                ->where('cart_id', $lockedCart->getKey())
                ->with('variant.product')
                ->lockForUpdate()
                ->get();
            $selectedItems = $items->whereIn('id', $ids->all());

            if ($selectedItems->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'cart_item_ids' => 'Một sản phẩm được chọn không thuộc giỏ hàng của bạn.',
                ]);
            }

            foreach ($selectedItems as $item) {
                if (! $item->variant?->isPurchasable()) {
                    throw ValidationException::withMessages([
                        'cart_item_ids' => "{$item->variant?->product?->name} hiện không còn có thể thanh toán.",
                    ]);
                }

                if ($item->quantity > $item->variant->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart_item_ids' => "{$item->variant->product->name} chỉ còn {$item->variant->stock_quantity} sản phẩm.",
                    ]);
                }
            }

            CartItem::query()
                ->where('cart_id', $lockedCart->getKey())
                ->update(['is_selected' => false]);
            CartItem::query()
                ->where('cart_id', $lockedCart->getKey())
                ->whereIn('id', $ids->all())
                ->update(['is_selected' => true]);
        });
    }
}
