<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Data\CheckoutLineData;
use App\Modules\Orders\Data\CheckoutTotalsData;
use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Promotions\Actions\CalculatePromotionDiscountAction;
use App\Modules\Promotions\Data\PromotionDiscountData;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CalculateCheckoutTotalsAction
{
    public function __construct(
        private readonly ResolveShippingQuoteAction $resolveShippingQuote,
        private readonly CalculatePromotionDiscountAction $calculatePromotionDiscount,
    ) {}

    public function execute(
        Cart $cart,
        ShippingAddressData $address,
        ?string $discountCode = null,
        ?string $shippingOption = null,
        bool $lockForUpdate = false,
        bool $ignoreInvalidDiscount = false,
        bool $includeShippingOptions = false,
    ): CheckoutTotalsData {
        $cartItemsQuery = CartItem::query()
            ->where('cart_id', $cart->getKey())
            ->orderBy('id');

        if ($lockForUpdate) {
            $cartItemsQuery->lockForUpdate();
        }

        $cartItems = $cartItemsQuery->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Giỏ hàng đang trống.',
            ]);
        }

        $variantsQuery = ProductVariant::query()
            ->with(['product.images', 'images'])
            ->whereIn('id', $cartItems->pluck('product_variant_id'))
            ->orderBy('id');

        if ($lockForUpdate) {
            $variantsQuery->lockForUpdate();
        }

        /** @var Collection<int, ProductVariant> $variants */
        $variants = $variantsQuery->get()->keyBy('id');
        $subtotal = 0;
        $totalWeightGrams = 0;
        $lines = [];

        foreach ($cartItems as $cartItem) {
            $variant = $variants->get($cartItem->product_variant_id);

            if (! $variant?->isPurchasable()) {
                throw ValidationException::withMessages([
                    'cart' => 'Một sản phẩm trong giỏ hiện không còn có thể đặt hàng.',
                ]);
            }

            if ($cartItem->quantity > $variant->stock_quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Biến thể {$variant->color_name} chỉ còn {$variant->stock_quantity} sản phẩm.",
                ]);
            }

            $unitPrice = (int) round((float) $variant->price);
            $lineTotal = $unitPrice * $cartItem->quantity;
            $weightGrams = $variant->weight_grams * $cartItem->quantity;
            $imagePath = $variant->images->first()?->path ?? $variant->product?->images->first()?->path;

            $lines[] = new CheckoutLineData(
                variantId: $variant->getKey(),
                productName: $variant->product->name,
                productSlug: $variant->product->slug,
                colorName: $variant->color_name,
                sku: $variant->sku,
                imagePath: $imagePath,
                unitPrice: $unitPrice,
                quantity: $cartItem->quantity,
                lineTotal: $lineTotal,
                weightGrams: $weightGrams,
                stockQuantity: $variant->stock_quantity,
            );

            $subtotal += $lineTotal;
            $totalWeightGrams += $weightGrams;
        }

        $shippingQuote = $this->resolveShippingQuote->execute($address, $totalWeightGrams, $shippingOption);

        try {
            $discount = $this->calculatePromotionDiscount->execute($discountCode, $subtotal, $lockForUpdate);
        } catch (ValidationException $exception) {
            if (! $ignoreInvalidDiscount || ! isset($exception->errors()['discount_code'])) {
                throw $exception;
            }

            $discount = PromotionDiscountData::none(
                $exception->errors()['discount_code'][0],
            );
        }

        return new CheckoutTotalsData(
            lines: $lines,
            subtotal: $subtotal,
            shipping: $shippingQuote,
            shippingOptions: $includeShippingOptions ? $this->resolveShippingQuote->all($address, $totalWeightGrams) : [],
            discount: $discount,
            total: $subtotal + $shippingQuote->fee - $discount->amount,
        );
    }
}
