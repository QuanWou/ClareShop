<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class ShowPublishedProductAction
{
    public function execute(Product $product): array
    {
        $product = Product::query()
            ->published()
            ->withStorefrontSummary()
            ->with(['category', 'activeVariants.images', 'images'])
            ->whereKey($product->getKey())
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->published()
            ->withStorefrontSummary()
            ->with(['category', 'images'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->orderByDesc('is_featured')
            ->limit(4)
            ->get();

        return compact('product', 'relatedProducts');
    }
}
