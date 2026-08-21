<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Shared\Support\Money;

class SuggestPublishedProductsAction
{
    public function execute(string $term): array
    {
        $escaped = '%'.addcslashes($term, '%_\\').'%';

        return Product::query()
            ->published()
            ->where(fn ($query) => $query->where('name', 'like', $escaped)->orWhere('short_description', 'like', $escaped))
            ->withStorefrontSummary()
            ->with('images')
            ->orderByDesc('is_featured')
            ->limit(6)
            ->get()
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'url' => route('catalog.products.show', $product),
                'image' => $product->images->first()?->url,
                'price' => Money::formatVnd($product->minimum_price),
            ])
            ->all();
    }
}
