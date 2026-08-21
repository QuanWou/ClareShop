<?php

namespace App\Modules\Catalog\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;

class ToggleWishlistProductAction
{
    public function execute(User $user, Product $product): bool
    {
        $result = $user->wishlistProducts()->toggle($product->getKey());

        return $result['attached'] !== [];
    }
}
