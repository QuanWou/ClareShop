<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;

class SearchPublishedProductsAction
{
    public function execute(string $query): array
    {
        $categories = Category::query()
            ->visible()
            ->withCount([
                'products as published_products_count' => fn ($categoryQuery) => $categoryQuery->published(),
            ])
            ->get();

        $products = Product::query()
            ->published()
            ->where(function ($productQuery) use ($query): void {
                $term = '%'.addcslashes($query, '%_\\\\').'%';

                $productQuery
                    ->where('name', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('material', 'like', $term)
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', $term));
            })
            ->withStorefrontSummary()
            ->with(['category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return compact('categories', 'products', 'query');
    }
}
