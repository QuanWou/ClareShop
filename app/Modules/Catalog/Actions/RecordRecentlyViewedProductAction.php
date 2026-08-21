<?php

namespace App\Modules\Catalog\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;

class RecordRecentlyViewedProductAction
{
    public function execute(?User $user, Product $product): void
    {
        if ($user === null) {
            return;
        }

        $user->recentlyViewedProducts()->syncWithoutDetaching([
            $product->getKey() => ['viewed_at' => now()],
        ]);

        $user->recentlyViewedProducts()->updateExistingPivot($product->getKey(), ['viewed_at' => now()]);

        $idsToRemove = $user->recentlyViewedProducts()->skip(30)->take(100)->pluck('products.id');
        if ($idsToRemove->isNotEmpty()) {
            $user->recentlyViewedProducts()->detach($idsToRemove);
        }
    }
}
