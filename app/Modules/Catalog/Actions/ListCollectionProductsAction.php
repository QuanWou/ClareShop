<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;

class ListCollectionProductsAction
{
    public function execute(Category $category): array
    {
        $category = Category::query()
            ->visible()
            ->whereKey($category->getKey())
            ->firstOrFail();

        $categories = Category::query()
            ->visible()
            ->withCount([
                'products as published_products_count' => fn ($query) => $query->published(),
            ])
            ->get();

        $products = $category->products()
            ->published()
            ->withStorefrontSummary()
            ->with(['category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12);

        return compact('category', 'categories', 'products');
    }
}
