<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;

class GetStorefrontHomeAction
{
    public function execute(): array
    {
        $categories = Category::query()
            ->visible()
            ->withCount([
                'products as published_products_count' => fn ($query) => $query->published(),
            ])
            ->get();

        $featuredProducts = Product::query()
            ->published()
            ->featured()
            ->withStorefrontSummary()
            ->with(['category', 'images'])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return compact('categories', 'featuredProducts');
    }
}
