<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Data\CartResolution;
use Illuminate\Support\Facades\DB;

class AddItemToCartAction
{
    public function __construct(
        private readonly ResolveCartAction $resolveCart,
        private readonly AddCartItemAction $addCartItem,
    ) {}

    public function execute(
        ?int $userId,
        ?string $guestToken,
        int $variantId,
        int $quantity,
    ): CartResolution {
        return DB::transaction(function () use ($userId, $guestToken, $variantId, $quantity): CartResolution {
            $resolution = $this->resolveCart->execute($userId, $guestToken, create: true);

            $this->addCartItem->execute(
                cart: $resolution->cart,
                variantId: $variantId,
                quantity: $quantity,
            );

            return $resolution;
        });
    }
}
