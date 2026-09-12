<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ListPromotionProductsAction
{
    /** @return Collection<int, Product> */
    public function execute(?User $customer = null, int $limit = 8): Collection
    {
        return Product::query()
            ->published()
            ->withStorefrontSummary()
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->approved()])
            ->withAvg(['reviews as approved_reviews_average' => fn ($query) => $query->approved()], 'rating')
            ->when($customer !== null, fn ($query) => $query->withExists([
                'wishlistedBy as is_wishlisted' => fn ($query) => $query->whereKey($customer->getKey()),
            ]))
            ->with(['category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
