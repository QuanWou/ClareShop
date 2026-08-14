<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListPublishedProductsAction
{
    /**
     * @return array{categories: Collection<int, Category>, products: LengthAwarePaginator, selectedCategory: ?Category, totalProductCount: int}
     */
    public function execute(?string $categorySlug = null): array
    {
        $categories = Category::query()
            ->visible()
            ->withCount([
                'products as published_products_count' => fn ($query) => $query->published(),
            ])
            ->get();
        $selectedCategory = $categorySlug
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        if ($categorySlug !== null && $selectedCategory === null) {
            abort(404);
        }

        $totalProductCount = Product::query()->published()->count();

        $products = Product::query()
            ->published()
            ->when(
                $selectedCategory,
                fn ($query) => $query->where('category_id', $selectedCategory->getKey()),
            )
            ->withStorefrontSummary()
            ->with(['category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return compact('categories', 'products', 'selectedCategory', 'totalProductCount');
    }
}
